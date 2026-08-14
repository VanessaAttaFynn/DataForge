<?php

namespace app\components;

class ScoringService
{
    /**
     * Compares a submitted CSV against the hidden answer key and returns a score.
     * Both files are expected to have an 'id' column and a 'target' column.
     */
    public static function score(string $submissionPath, string $answerKeyPath, string $metric): float
    {
        $submitted = self::readCsvAsMap($submissionPath);
        $answers = self::readCsvAsMap($answerKeyPath);

        $matched = [];
        foreach ($answers as $id => $trueValue) {
            $matched[$id] = ['true' => $trueValue, 'pred' => $submitted[$id] ?? null];
        }

        return match ($metric) {
            'accuracy' => self::accuracy($matched),
            'rmse' => self::rmse($matched),
            default => throw new \InvalidArgumentException("Unsupported metric: $metric"),
        };
    }

    private static function accuracy(array $matched): float
    {
        $correct = 0;
        $total = count($matched);
        foreach ($matched as $row) {
            if ($row['pred'] !== null && (string) $row['pred'] === (string) $row['true']) {
                $correct++;
            }
        }
        return $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;
    }

    private static function rmse(array $matched): float
    {
        $sumSquaredError = 0;
        $total = count($matched);
        foreach ($matched as $row) {
            $pred = $row['pred'] !== null ? (float) $row['pred'] : 0.0; // missing prediction penalized
            $true = (float) $row['true'];
            $sumSquaredError += ($pred - $true) ** 2;
        }
        return $total > 0 ? round(sqrt($sumSquaredError / $total), 4) : 0.0;
    }

    private static function readCsvAsMap(string $path): array
    {
        $map = [];
        if (($handle = fopen($path, 'r')) !== false) {
            fgetcsv($handle); // skip header
            while (($row = fgetcsv($handle)) !== false) {
                if (isset($row[0])) {
                    $map[$row[0]] = $row[1] ?? null;
                }
            }
            fclose($handle);
        }
        return $map;
    }
}