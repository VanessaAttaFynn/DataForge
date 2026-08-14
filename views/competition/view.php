<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var bool $canManage */

use yii\helpers\Html;

$this->title = $post->title;
?>
<div class="topbar">
    <div>
        <div class="eyebrow"><?= ucfirst($post->type) ?><?= $post->status !== 'published' ? ' · ' . ucfirst($post->status) : '' ?></div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">by <?= Html::encode($post->author->username ?? 'Unknown') ?> · deadline <?= Html::encode($competition->deadline) ?></div>
    </div>
    <?php if ($canManage): ?>
        <div style="display: flex; gap: 10px;">
            <?= Html::a('Edit', ['update', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
            <?= Html::a('Delete', ['delete', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px; color: var(--rose);',
                'data' => ['method' => 'post', 'confirm' => 'Delete this competition and everything tied to it (teams, submissions, comments)? This cannot be undone.'],
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">About</div></div>
            <p style="font-size: 13.5px; color: var(--text-dim); line-height: 1.6;"><?= nl2br(Html::encode($post->body)) ?></p>
        </div>

        <div class="panel">
            <div class="panel-head"><div class="panel-title">Leaderboard</div></div>
            <p style="color: var(--text-faint); font-size: 12.5px;">No submissions yet.</p>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Details</div></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Metric</span><strong><?= strtoupper($competition->metric) ?></strong></div>
            <?php $acceptsLabels = ['both' => 'Teams & Individuals', 'individual' => 'Individuals only', 'team' => 'Teams only']; ?>
            <div class="team-chip" style="justify-content: space-between;"><span>Accepts</span><strong><?= $acceptsLabels[$competition->accepts] ?? ucfirst($competition->accepts) ?></strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Submission cap</span><strong><?= $competition->submission_cap_per_day ?>/day</strong></div>
            <?php if ($competition->reward_type !== 'none'): ?>
                <div class="team-chip" style="justify-content: space-between;"><span>Reward</span><strong><?= Html::encode($competition->reward_details ?: ucfirst($competition->reward_type)) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>