<?php

/** @var app\models\Post[] $posts */
/** @var string[] $topics */
/** @var string $q */
/** @var string $topic */
/** @var string $sort */
/** @var string $verified */
/** @var array $voteCounts */

use yii\helpers\Html;

$this->title = 'Notebooks';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Explore</div>
        <h1 class="page-title">Notebooks</h1>
        <div class="page-sub"><?= count($posts) ?> published</div>
    </div>
    <?= Html::a('+ Publish', ['create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
</div>

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
        <?php if ($q || $topic || $sort !== 'date' || $verified !== ''): ?>
            <?= Html::a('Reset', ['index'], ['class' => 'nav-item', 'style' => 'padding: 8px 18px;']) ?>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <?php if (empty($posts)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">No notebooks match.</p>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
        <?php $tags = \app\models\Tag::forPost($post->id); ?>
        <?php $linkedLabel = $post->notebook->linkedTypeLabel; ?>
        <?= Html::a(
            Html::tag('div',
                '<div class="comp-icon">📓</div>'
                . '<div style="flex:1;"><div class="comp-name">' . Html::encode($post->title)
                . ($post->verified ? ' <span style="color: var(--emerald); font-size: 11px;">✓ Verified</span>' : ' <span style="color: var(--text-faint); font-size: 11px;">◌ Unverified</span>') . '</div>'
                . '<div class="comp-meta">' . date('M j, Y', $post->created_at)
                . (!empty($tags) ? ' · ' . implode(', ', array_map(fn($t) => Html::encode($t->name), $tags)) : '') . '</div></div>'
                . '<div style="font-size: 11.5px; color: var(--text-faint); white-space: nowrap; margin-right: 14px;">'
                . ($linkedLabel ? Html::encode($linkedLabel) : 'Topic: ' . Html::encode($post->notebook->topic ?? '')) . '</div>'
                . '<div class="comp-tag tag-live">▲ ' . ($voteCounts[$post->id] ?? 0) . '</div>',
                ['class' => 'comp-item']
            ),
            ['view', 'id' => $post->id],
            ['style' => 'text-decoration: none; display: block;']
        ) ?>
    <?php endforeach; ?>
</div>