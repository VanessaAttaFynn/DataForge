<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\Submission[] $submissions */

use yii\helpers\Html;

$this->title = 'My Submissions';
$metricLabel = $competition->metric === 'accuracy' ? 'Accuracy (%)' : 'RMSE';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Submissions</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub"><?= count($submissions) ?> attempt(s)</div>
    </div>
    <?= Html::a('← Back to Competition', ['view', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
</div>

<div class="panel">
    <?php if (!empty($submissions)): ?>
        <div style="display: flex; justify-content: space-between; padding: 6px 16px 10px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Submitted</span>
            <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Metric: <?= $metricLabel ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($submissions)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">You haven't submitted to this competition yet.</p>
    <?php endif; ?>

    <?php foreach ($submissions as $s): ?>
        <div class="team-chip" style="justify-content: space-between;">
            <div class="team-meta"><?= Yii::$app->formatter->asDatetime($s->submitted_at, 'php:M j, Y H:i') ?></div>
            <strong style="font-family: 'IBM Plex Mono', monospace; color: var(--gold-bright);"><?= $s->score ?></strong>
        </div>
    <?php endforeach; ?>
</div>