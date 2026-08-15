<?php

/** @var app\models\Post[] $created */
/** @var app\models\Post[] $joined */
/** @var array $entrantCounts */
/** @var array $ranks */

use yii\helpers\Html;

$this->title = 'My Participation';

if (!function_exists('renderCompRow')) {
    function renderCompRow($post, $entrantCounts, $rank = null)
    {
        $reward = $post->competition->reward_type !== 'none'
            ? ($post->competition->reward_details ?: ucfirst($post->competition->reward_type))
            : null;
        $counts = $entrantCounts[$post->id];
        $entrants = trim(($counts['teams'] > 0 ? $counts['teams'] . ' teams · ' : '') . ($counts['individuals'] > 0 ? $counts['individuals'] . ' individuals' : ''), ' ·');

        return Html::a(
            Html::tag('div',
                '<div class="comp-icon">' . ($post->type === 'hackathon' ? '⚡' : '🏆') . '</div>'
                . '<div style="flex:1;"><div class="comp-name">' . Html::encode($post->title) . '</div>'
                . '<div class="comp-meta"><strong style="color: var(--gold);">' . strtoupper($post->type) . '</strong> · ' . ucfirst($post->status) . ($entrants ? ' · ' . Html::encode($entrants) : '') . '</div></div>'
                . ($rank !== null ? '<div style="font-family: \'IBM Plex Mono\', monospace; font-weight: 700; color: var(--gold-bright); font-size: 13px; white-space: nowrap; margin-right: 10px;">Rank #' . $rank . '</div>' : '')
                . ($reward ? '<div style="font-weight:700; color: var(--gold-bright); font-size: 13px;">🏆 ' . Html::encode($reward) . '</div>' : ''),
                ['class' => 'comp-item']
            ),
            ['view', 'id' => $post->id],
            ['style' => 'text-decoration: none; display: block;']
        );
    }
}
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Space</div>
        <h1 class="page-title">My Participation</h1>
        <div class="page-sub">Created and joined, in one place</div>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><div class="panel-title">Created by me (<?= count($created) ?>)</div></div>
    <?php if (empty($created)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">You haven't created any yet.</p>
    <?php else: ?>
        <?php foreach ($created as $post) echo renderCompRow($post, $entrantCounts); ?>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head"><div class="panel-title">Joined (<?= count($joined) ?>)</div></div>
    <?php if (empty($joined)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">You haven't registered or joined a team for any yet.</p>
    <?php else: ?>
        <?php foreach ($joined as $post) echo renderCompRow($post, $entrantCounts, $ranks[$post->id] ?? null); ?>
    <?php endif; ?>
</div>