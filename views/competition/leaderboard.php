<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\Submission[] $rows */

use yii\helpers\Html;

$this->title = $post->title . ' — Leaderboard';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Leaderboard</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub"><?= count($rows) ?> ranked entries · <?= strtoupper($competition->metric) ?> (<?= $competition->metric === 'rmse' ? 'lower is better' : 'higher is better' ?>)</div>
    </div>
    <?= Html::a('← Back to Competition', ['view', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
</div>

<div class="panel">
    <?php if (!empty($rows)): ?>
        <div style="display: flex; justify-content: space-between; padding: 6px 16px 10px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Name</span>
            <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Metric: <?= $competition->metric === 'accuracy' ? 'Accuracy (%)' : 'RMSE' ?></span>
        </div>
    <?php endif; ?>
    <?php if (empty($rows)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">No submissions yet.</p>
    <?php endif; ?>
    <?php foreach ($rows as $i => $row): ?>
        <div class="comp-item">
            <div class="comp-icon" style="font-family: 'IBM Plex Mono', monospace; font-size: 13px;">#<?= $i + 1 ?></div>
            <div style="flex: 1;">
                <div class="comp-name"><?= Html::encode($row->getParticipantName()) ?></div>
                <div class="comp-meta"><?= ucfirst($row->participant_type) ?> · submitted <?= Yii::$app->formatter->asRelativeTime($row->submitted_at) ?></div>
            </div>
            <div style="font-family: 'IBM Plex Mono', monospace; font-weight: 700; color: var(--gold-bright); font-size: 15px;"><?= $row->score ?></div>
        </div>
    <?php endforeach; ?>
</div>