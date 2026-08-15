<?php

/** @var app\models\Post $post */
/** @var app\models\Dataset $dataset */
/** @var bool $canManage */
/** @var array|null $preview */
/** @var int $voteCount */
/** @var bool $hasVoted */
/** @var app\models\Post[] $associatedNotebooks */

use yii\helpers\Html;

$this->title = $post->title;
$linkedPost = $dataset->linkedPost;
$eyebrowSuffix = $linkedPost !== null ? $linkedPost->title : $dataset->topic;
?>
<div class="topbar">
    <div>
        <div class="eyebrow">
            Dataset · <?= Html::encode($eyebrowSuffix) ?>
            <?= $post->verified ? '<span style="color: var(--emerald);"> · ✓ Verified</span>' : '<span style="color: var(--text-faint);"> · ◌ Unverified</span>' ?>
        </div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">by <?= Html::encode($post->author->username ?? 'Unknown') ?> · <?= date('M j, Y', $post->created_at) ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <?= Html::a('← All Datasets', ['index'], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php if ($canManage): ?>
            <?= Html::a('Edit', ['update', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
            <?= Html::a('Delete', ['delete', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px; color: var(--rose);',
                'data' => ['method' => 'post', 'confirm' => 'Delete this dataset? This cannot be undone.'],
            ]) ?>
        <?php endif; ?>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="display: flex; gap: 10px;">
        <?php if (!Yii::$app->user->isGuest): ?>
            <?= Html::a(($hasVoted ? '✓ Upvoted' : '▲ Upvote') . ' (' . $voteCount . ')', ['upvote', 'id' => $post->id], [
                'class' => $hasVoted ? 'nav-item active' : 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;',
                'data' => ['method' => 'post'],
            ]) ?>
        <?php endif; ?>
        <?= Html::a('⬇ Download', ['download', 'id' => $post->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 8px 18px;']) ?>
        <?php $alreadyUnderReview = $post->status === \app\models\Post::STATUS_PENDING || $dataset->verification_requested_at !== null; ?>
        <?php if ($canManage && !$post->verified && !$alreadyUnderReview): ?>
            <?= Html::a('Submit for Review', ['request-review', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;',
                'data' => ['method' => 'post', 'confirm' => 'Ask a moderator to verify this dataset? It stays live either way — this just requests the ✓ Verified badge.'],
            ]) ?>
        <?php elseif ($canManage && !$post->verified && $alreadyUnderReview): ?>
            <span class="nav-item" style="display: inline-flex; padding: 8px 18px; color: var(--text-faint); cursor: default; border-style: dashed;">⏳ Review Requested</span>
        <?php endif; ?>
        <?php if ($linkedPost !== null): ?>
            <?= Html::a('View ' . ucfirst($linkedPost->type), [$linkedPost->type === 'dataset' ? '/dataset/view' : '/competition/view', 'id' => $linkedPost->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;']) ?>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div>
        <?php if ($post->body): ?>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Description</div></div>
            <p style="font-size: 13.5px; color: var(--text-dim); line-height: 1.6;"><?= nl2br(Html::encode($post->body)) ?></p>
        </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-head"><div class="panel-title"><?= $preview !== null ? 'Preview' : 'Summary Statistics' ?></div></div>
            <?php if ($preview !== null): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; font-family: 'IBM Plex Mono', monospace;">
                        <thead>
                            <tr>
                                <?php foreach ($preview['header'] as $h): ?>
                                    <th style="text-align: left; padding: 8px 12px; border-bottom: 1px solid var(--border-strong); color: var(--gold-bright); white-space: nowrap;"><?= Html::encode($h) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td style="padding: 7px 12px; border-bottom: 1px solid var(--border); color: var(--text-dim); white-space: nowrap;"><?= Html::encode($cell) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="font-size: 11px; color: var(--text-faint); margin-top: 10px;">Showing first <?= count($preview['rows']) ?> rows.</div>
            <?php elseif ($dataset->summary_stats): ?>
                <p style="font-size: 13px; color: var(--text-dim); line-height: 1.6; white-space: pre-wrap;"><?= Html::encode($dataset->summary_stats) ?></p>
            <?php else: ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">This file type can't be previewed, and the creator hasn't added summary statistics yet.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($associatedNotebooks)): ?>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Associated Notebooks</div></div>
            <?php foreach ($associatedNotebooks as $nb): ?>
                <?= Html::a(
                    Html::tag('div', '<div class="team-name">' . Html::encode($nb->title) . '</div><div class="team-meta">by ' . Html::encode($nb->author->username ?? 'Unknown') . '</div>', ['class' => 'team-chip']),
                    ['/notebook/view', 'id' => $nb->id],
                    ['style' => 'text-decoration: none; display: block;']
                ) ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Details</div></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Size</span><strong><?= round($dataset->file_size / 1024, 1) ?> KB</strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>File type</span><strong><?= strtoupper(pathinfo($dataset->file_path, PATHINFO_EXTENSION)) ?></strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Rows</span><strong><?= $dataset->row_count ?? 'Not specified' ?></strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Columns</span><strong><?= $dataset->column_count ?? 'Not specified' ?></strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Target</span><strong><?= Html::encode($dataset->target_column ?: 'Not specified') ?></strong></div>
            <?php if ($dataset->license): ?>
                <div class="team-chip" style="justify-content: space-between;"><span>License</span><strong><?= Html::encode($dataset->license) ?></strong></div>
            <?php endif; ?>
            <div class="team-chip" style="justify-content: space-between;"><span>Downloads</span><strong><?= $dataset->download_count ?></strong></div>
        </div>
    </div>
</div>