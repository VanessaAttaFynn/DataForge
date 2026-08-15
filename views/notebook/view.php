<?php

/** @var app\models\Post $post */
/** @var app\models\Notebook $notebook */
/** @var bool $canManage */
/** @var app\models\Tag[] $tags */
/** @var string $renderedHtml */
/** @var int $voteCount */
/** @var bool $hasVoted */

use yii\helpers\Html;

$this->title = $post->title;

$linkedPost = $notebook->linkedPost;
$eyebrowSuffix = $linkedPost !== null ? $linkedPost->title : $notebook->topic;

$linkedRoute = null;
if ($linkedPost !== null) {
    $linkedRoute = $linkedPost->type === 'dataset' ? '/dataset/view' : '/competition/view';
}
?>
<div class="topbar">
    <div>
        <div class="eyebrow">
            Notebook · <?= Html::encode($eyebrowSuffix) ?>
            <?= $post->verified ? '<span style="color: var(--emerald);"> · ✓ Verified</span>' : '<span style="color: var(--text-faint);"> · ◌ Unverified</span>' ?>
        </div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">by <?= Html::encode($post->author->username ?? 'Unknown') ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <?= Html::a('← All Notebooks', ['index'], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php if ($canManage): ?>
            <?= Html::a('Edit', ['update', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
            <?= Html::a('Delete', ['delete', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px; color: var(--rose);',
                'data' => ['method' => 'post', 'confirm' => 'Delete this notebook? This cannot be undone.'],
            ]) ?>
        <?php endif; ?>
    </div>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <div style="display: flex; gap: 10px; align-items: center;">
        <?php if (!Yii::$app->user->isGuest): ?>
            <?= Html::a(($hasVoted ? '✓ Upvoted' : '▲ Upvote') . ' (' . $voteCount . ')', ['upvote', 'id' => $post->id], [
                'class' => $hasVoted ? 'nav-item active' : 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;',
                'data' => ['method' => 'post'],
            ]) ?>
        <?php endif; ?>
        <?php if ($notebook->notebook_file_path): ?>
            <?= Html::a('⬇ Download', ['download', 'id' => $post->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 8px 18px;']) ?>
        <?php endif; ?>
        <?php $alreadyUnderReview = $post->status === \app\models\Post::STATUS_PENDING || $notebook->verification_requested_at !== null; ?>
        <?php if ($canManage && !$post->verified && !$alreadyUnderReview): ?>
            <?= Html::a('Submit for Review', ['request-review', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;',
                'data' => ['method' => 'post', 'confirm' => 'Ask a moderator to verify this notebook? It stays live either way — this just requests the ✓ Verified badge.'],
            ]) ?>
        <?php elseif ($canManage && !$post->verified && $alreadyUnderReview): ?>
            <span class="nav-item" style="display: inline-flex; padding: 8px 18px; color: var(--text-faint); cursor: default; border-style: dashed;">⏳ Review Requested</span>
        <?php endif; ?>
        <?php if ($linkedPost !== null): ?>
            <?= Html::a('View ' . ucfirst($linkedPost->type), [$linkedRoute, 'id' => $linkedPost->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 8px 18px;']) ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tags)): ?>
<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
    <?php foreach ($tags as $t): ?>
        <?= Html::a('#' . Html::encode($t->name), ['index', 'tag' => $t->slug], ['class' => 'nb-tag', 'style' => 'text-decoration: none;']) ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($post->body): ?>
<div class="panel" style="margin-bottom: 20px;">
    <p style="font-size: 13.5px; color: var(--text-dim); line-height: 1.6;"><?= nl2br(Html::encode($post->body)) ?></p>
</div>
<?php endif; ?>

<div class="panel">
    <?= $renderedHtml ?>
</div>