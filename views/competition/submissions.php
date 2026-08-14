<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\Submission[] $submissions */

use yii\helpers\Html;

$this->title = 'All Submissions';
$metricLabel = $competition->metric === 'accuracy' ? 'Accuracy (%)' : 'RMSE';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">All Submissions</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub"><?= count($submissions) ?> total attempt(s) · every submission, not just best-per-participant</div>
    </div>
    <?= Html::a('← Back to Competition', ['view', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
</div>

<div class="panel">
    <div class="team-chip" style="justify-content: space-between; background: transparent; border: none; padding: 6px 14px; margin-bottom: 4px;">
        <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Name</span>
        <span style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Metric: <?= $metricLabel ?></span>
    </div>

    <?php if (empty($submissions)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">No submissions yet.</p>
    <?php endif; ?>

    <?php foreach ($submissions as $s): ?>
        <div class="team-chip" style="justify-content: space-between;">
            <div>
                <div class="team-name"><?= Html::encode($s->getParticipantName()) ?></div>
                <div class="team-meta"><?= ucfirst($s->participant_type) ?> · <?= Yii::$app->formatter->asRelativeTime($s->submitted_at) ?></div>
            </div>
            <strong style="font-family: 'IBM Plex Mono', monospace; color: var(--gold-bright);"><?= $s->score ?></strong>
        </div>
    <?php endforeach; ?>
</div>