<?php

namespace app\components;

use yii\helpers\Html;

/**
 * Parses a .ipynb file (standard Jupyter JSON format) into safe,
 * read-only HTML. Nothing here executes code — it just displays the
 * source and whatever outputs were already cached in the file when the
 * author last ran it, the same way GitHub/nbviewer render notebooks.
 */
class NotebookRenderer
{
    public static function renderFile(string $path): string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return '<p style="color: var(--rose);">Could not read this notebook file.</p>';
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['cells'])) {
            return '<p style="color: var(--rose);">This doesn\'t look like a valid .ipynb file.</p>';
        }

        $language = $data['metadata']['kernelspec']['language'] ?? $data['metadata']['language_info']['name'] ?? '';

        $html = '';
        foreach ($data['cells'] as $cell) {
            $type = $cell['cell_type'] ?? '';
            $source = self::joinSource($cell['source'] ?? '');

            if ($type === 'markdown') {
                $html .= '<div class="nb-cell nb-markdown">' . self::markdownToHtml($source) . '</div>';
            } elseif ($type === 'code') {
                $html .= '<div class="nb-cell nb-code">';
                $html .= '<pre><code>' . Html::encode($source) . '</code></pre>';
                $html .= self::renderOutputs($cell['outputs'] ?? []);
                $html .= '</div>';
            }
        }

        return $html ?: '<p style="color: var(--text-faint);">This notebook has no cells.</p>';
    }

    private static function joinSource($source): string
    {
        return is_array($source) ? implode('', $source) : (string) $source;
    }

    private static function renderOutputs(array $outputs): string
    {
        $html = '';
        foreach ($outputs as $output) {
            $outputType = $output['output_type'] ?? '';

            if ($outputType === 'stream') {
                $text = self::joinSource($output['text'] ?? '');
                $html .= '<pre class="nb-output">' . Html::encode($text) . '</pre>';
            } elseif (in_array($outputType, ['execute_result', 'display_data'], true)) {
                $data = $output['data'] ?? [];
                if (isset($data['image/png'])) {
                    $img = is_array($data['image/png']) ? implode('', $data['image/png']) : $data['image/png'];
                    $html .= '<img class="nb-output-img" src="data:image/png;base64,' . $img . '" alt="notebook output">';
                } elseif (isset($data['text/plain'])) {
                    $html .= '<pre class="nb-output">' . Html::encode(self::joinSource($data['text/plain'])) . '</pre>';
                }
                // text/html outputs are intentionally skipped — not safe to
                // render unsanitized user-controlled HTML.
            } elseif ($outputType === 'error') {
                $traceback = implode("\n", $output['traceback'] ?? []);
                $html .= '<pre class="nb-output nb-error">' . Html::encode($traceback) . '</pre>';
            }
        }
        return $html;
    }

    /** Minimal markdown -> HTML: headers, bold, italic, inline code, links, lists. Everything else is escaped, not raw HTML. */
    private static function markdownToHtml(string $md): string
    {
        $escaped = Html::encode($md);

        $escaped = preg_replace('/^###### (.+)$/m', '<h6>$1</h6>', $escaped);
        $escaped = preg_replace('/^##### (.+)$/m', '<h5>$1</h5>', $escaped);
        $escaped = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $escaped);
        $escaped = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $escaped);
        $escaped = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $escaped);
        $escaped = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $escaped);

        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped);
        $escaped = preg_replace('/`(.+?)`/', '<code>$1</code>', $escaped);
        $escaped = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $escaped);
        $escaped = preg_replace('/^- (.+)$/m', '<li>$1</li>', $escaped);
        $escaped = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $escaped);

        return nl2br($escaped);
    }
}