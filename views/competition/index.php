<?php

/** @var app\models\Post[] $posts */
/** @var string $view */
/** @var array $entrantCounts */
/** @var string $q */
/** @var string $type */
/** @var string $reward */
/** @var string $status */
/** @var string $sort */
/** @var string $order */

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
        <?= Html::a('My Participation', ['mine'], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
        <div class="theme-toggle" style="width: auto;">
            <?= Html::a('List', array_merge(['index', 'view' => 'list'], compact('q', 'type', 'reward', 'status', 'sort', 'order')), ['class' => $view === 'list' ? 'active' : '']) ?>
            <?= Html::a('Grid', array_merge(['index', 'view' => 'grid'], compact('q', 'type', 'reward', 'status', 'sort', 'order')), ['class' => $view === 'grid' ? 'active' : '']) ?>
        </div>
        <?= Html::a('+ Create', ['create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
    </div>
</div>

<div class="panel" style="margin-bottom: 20px;">
    <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="view" value="<?= Html::encode($view) ?>">
        <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Search title…"
               style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif; flex: 1; min-width: 140px;">

        <select name="type" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="">Competitions & Hackathons</option>
            <option value="competition" <?= $type === 'competition' ? 'selected' : '' ?>>Competitions only</option>
            <option value="hackathon" <?= $type === 'hackathon' ? 'selected' : '' ?>>Hackathons only</option>
        </select>

        <select name="status" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="">Any status</option>
            <option value="registration" <?= $status === 'registration' ? 'selected' : '' ?>>Registration open</option>
            <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>

        <select name="reward" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="">Any reward</option>
            <?php foreach (['none' => 'None', 'cash' => 'Cash', 'prize' => 'Prize', 'certificate' => 'Certificate', 'points' => 'Platform points', 'other' => 'Other'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $reward === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Sort by date</option>
            <option value="reward" <?= $sort === 'reward' ? 'selected' : '' ?>>Sort by reward</option>
        </select>

        <select name="order" style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
            <option value="desc" <?= $order === 'desc' ? 'selected' : '' ?>>Descending</option>
            <option value="asc" <?= $order === 'asc' ? 'selected' : '' ?>>Ascending</option>
        </select>

        <button type="submit" class="nav-item active" style="padding: 8px 18px; border: none; cursor: pointer;">Filter</button>
        <?php if ($q || $type || $reward || $status || $sort !== 'date' || $order !== 'desc'): ?>
            <?= Html::a('Reset', ['index', 'view' => $view], ['class' => 'nav-item', 'style' => 'padding: 8px 18px;']) ?>
        <?php endif; ?>
    </form>
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
                        ? '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: url(\'' . Html::encode(\yii\helpers\Url::to(['/site/serve-image', 'path' => $post->cover_image_path])) . '\') center/cover;"></div>'
                        : '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: linear-gradient(135deg, rgba(212,175,106,0.25), rgba(212,175,106,0.05)); display:flex; align-items:center; justify-content:center; font-family:\'Fraunces\',serif; font-size:28px; color: var(--gold-bright);">' . Html::encode(mb_substr($post->title, 0, 1)) . '</div>')
                    . '<div class="dataset-title">' . Html::encode($post->title) . '</div>'
                    . '<div class="dataset-meta"><strong style="color: var(--gold);">' . strtoupper($post->type) . '</strong> · ' . Html::encode(entrantLabel($entrantCounts[$post->id])) . '</div>'
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
            <?php $phaseKey = $post->competition->phaseKey(); ?>
            <?php $phaseClass = ['registration' => 'tag-new', 'ongoing' => 'tag-live', 'completed' => ''][$phaseKey]; ?>
            <?php $phaseShort = ['registration' => 'Registration Open', 'ongoing' => 'Ongoing', 'completed' => 'Completed'][$phaseKey]; ?>
            <?= Html::a(
                Html::tag('div',
                    (coverImageExists($post)
                        ? '<div style="width:44px;height:44px;border-radius:10px;flex-shrink:0;background:url(\'' . Html::encode(\yii\helpers\Url::to(['/site/serve-image', 'path' => $post->cover_image_path])) . '\') center/cover; border:1px solid var(--border);"></div>'
                        : '<div class="comp-icon">' . ($post->type === 'hackathon' ? '⚡' : '🏆') . '</div>')
                    . '<div style="flex:1;"><div class="comp-name">' . Html::encode($post->title) . '</div>'
                    . '<div class="comp-meta"><strong style="color: var(--gold);">' . strtoupper($post->type) . '</strong> · ' . Html::encode(entrantLabel($entrantCounts[$post->id])) . '</div></div>'
                    . ($reward ? '<div style="font-weight:700; color: var(--gold-bright); font-size: 13px; white-space: nowrap;">🏆 ' . Html::encode($reward) . '</div>' : '')
                    . '<span class="comp-tag ' . $phaseClass . '" style="margin-left: 14px;' . ($phaseKey === 'completed' ? ' color: var(--text-faint);' : '') . '">' . $phaseShort . '</span>',
                    ['class' => 'comp-item']
                ),
                ['view', 'id' => $post->id],
                ['style' => 'text-decoration: none; display: block;']
            ) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>