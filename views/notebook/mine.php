<?php

/** @var app\models\Post[] $published */
/** @var app\models\Post[] $pendingReview */
/** @var app\models\Post[] $rejected */
/** @var array $rejectionReasons */
/** @var string[] $topics */
/** @var string $q */
/** @var string $topic */
/** @var string $sort */
/** @var string $verified */
/** @var array $voteCounts */

use yii\helpers\Html;

$this->title = 'My Notebooks';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Space</div>
        <h1 class="page-title">My Notebooks</h1>
        <div class="page-sub"><?= count($published) ?> published · <?= count($pendingReview) ?> pending review · <?= count($rejected) ?> rejected</div>
    </div>
    <?= Html::a('+ Publish', ['create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
</div>

<?php if (!empty($pendingReview)): ?>
<div class="panel" style="margin-bottom: 20px;">
    <div class="panel-head"><div class="panel-title" style="color: var(--gold-bright);">Pending Review (<?= count($pendingReview) ?>)</div></div>
    <?php foreach ($pendingReview as $post): ?>
        <?= Html::a(
            Html::tag('div', '<div class="comp-icon">📓</div><div style="flex:1;"><div class="comp-name">' . Html::encode($post->title) . '</div><div class="comp-meta">Awaiting moderator review</div></div><span class="nav-item" style="padding: 6px 14px; color: var(--text-faint); border-style: dashed;">⏳ Pending</span>', ['class' => 'comp-item']),
            ['view', 'id' => $post->id],
            ['style' => 'text-decoration: none; display: block;']
        ) ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($rejected)): ?>
<div class="panel" style="margin-bottom: 20px;">
    <div class="panel-head"><div class="panel-title" style="color: var(--rose);">Rejected (<?= count($rejected) ?>)</div></div>
    <?php foreach ($rejected as $post): ?>
        <div class="comp-item" style="align-items: flex-start;">
            <div class="comp-icon">📓</div>
            <div style="flex: 1;">
                <div class="comp-name"><?= Html::encode($post->title) ?></div>
                <div class="comp-meta" style="color: var(--rose);">Reason: <?= Html::encode($rejectionReasons[$post->id] ?? 'Not specified') ?></div>
            </div>
            <div style="display: flex; gap: 8px;">
                <?= Html::a('Edit & Resubmit', ['update', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px;']) ?>
                <?= Html::a('Delete', ['delete', 'id' => $post->id], [
                    'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px; color: var(--rose);',
                    'data' => ['method' => 'post', 'confirm' => "Delete \"{$post->title}\"? This cannot be undone."],
                ]) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel" style="margin-bottom: 20px;">
    <form method="get" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Search title…"
               style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif; flex: 1; min-width: 160px;">

        <select name="topic" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="">All topics</option>
            <?php foreach ($topics as $t): ?>
                <option value="<?= Html::encode($t) ?>" <?= $topic === $t ? 'selected' : '' ?>><?= Html::encode($t) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Newest first</option>
            <option value="votes" <?= $sort === 'votes' ? 'selected' : '' ?>>Most votes</option>
        </select>

        <select name="verified" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="" <?= $verified === '' ? 'selected' : '' ?>>Verified & Unverified</option>
            <option value="verified" <?= $verified === 'verified' ? 'selected' : '' ?>>Verified only</option>
            <option value="unverified" <?= $verified === 'unverified' ? 'selected' : '' ?>>Unverified only</option>
        </select>

        <button type="submit" class="nav-item active" style="padding: 8px 18px; border: none; cursor: pointer;">Filter</button>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><div class="panel-title">Published (<?= count($published) ?>)</div></div>
    <?php if (empty($published)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">Nothing matches yet.</p>
    <?php endif; ?>
    <?php foreach ($published as $post): ?>
        <?= Html::a(
            Html::tag('div',
                '<div class="comp-icon">📓</div>'
                . '<div style="flex:1;"><div class="comp-name">' . Html::encode($post->title)
                . ($post->verified ? ' <span style="color: var(--emerald); font-size: 11px;">✓ Verified</span>' : ' <span style="color: var(--text-faint); font-size: 11px;">◌ Unverified</span>') . '</div>'
                . '<div class="comp-meta">' . date('M j, Y', $post->created_at) . '</div></div>'
                . '<div class="comp-tag tag-live">▲ ' . ($voteCounts[$post->id] ?? 0) . '</div>',
                ['class' => 'comp-item']
            ),
            ['view', 'id' => $post->id],
            ['style' => 'text-decoration: none; display: block;']
        ) ?>
    <?php endforeach; ?>
</div>