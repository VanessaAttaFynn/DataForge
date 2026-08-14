<?php

/** @var app\models\Post[] $posts */
/** @var string $view */
/** @var array $entrantCounts */

use yii\helpers\Html;

$this->title = 'Competitions & Hackathons';
$view = $view ?? 'list';

if (!function_exists('coverImageExists')) {
    function coverImageExists($post): bool
    {
        if (empty($post->cover_image_path)) return false;
        return file_exists(Yii::getAlias('@webroot') . $post->cover_image_path);
    }
}

if (!function_exists('entrantLabel')) {
    function entrantLabel(array $counts): string
    {
        $parts = [];
        if ($counts['teams'] > 0) $parts[] = $counts['teams'] . ' team' . ($counts['teams'] === 1 ? '' : 's');
        if ($counts['individuals'] > 0) $parts[] = $counts['individuals'] . ' individual' . ($counts['individuals'] === 1 ? '' : 's');
        return $parts ? implode(' · ', $parts) : 'No entrants yet';
    }
}

if (!function_exists('rewardLabel')) {
    function rewardLabel($competition): ?string
    {
        if ($competition->reward_type === 'none') return null;
        if (!empty($competition->reward_details)) return $competition->reward_details;
        return ucfirst($competition->reward_type);
    }
}
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Explore</div>
        <h1 class="page-title">Competitions & Hackathons</h1>
        <div class="page-sub"><?= count($posts) ?> live right now</div>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <?= Html::a('My Competitions', ['mine'], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
        <div class="theme-toggle" style="width: auto;">
            <?= Html::a('List', ['index', 'view' => 'list'], ['class' => $view === 'list' ? 'active' : '']) ?>
            <?= Html::a('Grid', ['index', 'view' => 'grid'], ['class' => $view === 'grid' ? 'active' : '']) ?>
        </div>
        <?= Html::a('+ Create', ['create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="panel"><p style="color: var(--text-dim); font-size: 13.5px;">Nothing published yet.</p></div>
<?php elseif ($view === 'grid'): ?>
    <div class="dataset-row" style="grid-template-columns: repeat(3, 1fr);">
        <?php foreach ($posts as $post): ?>
            <?php $reward = rewardLabel($post->competition); ?>
            <?= Html::a(
                Html::tag('div',
                    (coverImageExists($post)
                        ? '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: url(\'' . Html::encode($post->cover_image_path) . '\') center/cover;"></div>'
                        : '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: linear-gradient(135deg, rgba(212,175,106,0.25), rgba(212,175,106,0.05)); display:flex; align-items:center; justify-content:center; font-family:\'Fraunces\',serif; font-size:28px; color: var(--gold-bright);">' . Html::encode(mb_substr($post->title, 0, 1)) . '</div>')
                    . '<div class="dataset-title">' . Html::encode($post->title) . '</div>'
                    . '<div class="dataset-meta">' . Html::encode(entrantLabel($entrantCounts[$post->id])) . '</div>'
                    . ($reward ? '<div style="margin-top: 6px; font-weight: 700; color: var(--gold-bright); font-size: 13px;">🏆 ' . Html::encode($reward) . '</div>' : ''),
                    ['class' => 'dataset-card']
                ),
                ['view', 'id' => $post->id],
                ['style' => 'text-decoration: none;']
            ) ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="panel">
        <?php foreach ($posts as $post): ?>
            <?php $reward = rewardLabel($post->competition); ?>
            <?= Html::a(
                Html::tag('div',
                    (coverImageExists($post)
                        ? '<div style="width:44px;height:44px;border-radius:10px;flex-shrink:0;background:url(\'' . Html::encode($post->cover_image_path) . '\') center/cover; border:1px solid var(--border);"></div>'
                        : '<div class="comp-icon">' . ($post->type === 'hackathon' ? '⚡' : '🏆') . '</div>')
                    . '<div style="flex:1;"><div class="comp-name">' . Html::encode($post->title) . '</div>'
                    . '<div class="comp-meta">' . Html::encode(entrantLabel($entrantCounts[$post->id])) . '</div></div>'
                    . ($reward ? '<div style="font-weight:700; color: var(--gold-bright); font-size: 13px; white-space: nowrap;">🏆 ' . Html::encode($reward) . '</div>' : '')
                    . '<span class="comp-tag tag-live" style="margin-left: 14px;">Ongoing</span>',
                    ['class' => 'comp-item']
                ),
                ['view', 'id' => $post->id],
                ['style' => 'text-decoration: none; display: block;']
            ) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>