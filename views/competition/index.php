<?php

/** @var \yii\web\View $this */
/** @var app\models\Post[] $posts */
/** @var string $view */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Competitions & Hackathons';
$view = $view ?? 'list';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Explore</div>
        <h1 class="page-title">Competitions & Hackathons</h1>
        <div class="page-sub"><?= count($posts) ?> live right now</div>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
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
            <?= Html::a(
                Html::tag('div',
                    ($post->cover_image_path
                        ? '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: url(\'' . Html::encode($post->cover_image_path) . '\') center/cover;"></div>'
                        : '<div style="height: 110px; border-radius: 10px; margin-bottom: 12px; background: linear-gradient(135deg, rgba(212,175,106,0.25), rgba(212,175,106,0.05)); display:flex; align-items:center; justify-content:center; font-family:\'Fraunces\',serif; font-size:28px; color: var(--gold-bright);">' . Html::encode(mb_substr($post->title, 0, 1)) . '</div>')
                    . '<div class="dataset-title">' . Html::encode($post->title) . '</div>'
                    . '<div class="dataset-meta">' . ucfirst($post->type) . ($post->competition->reward_type !== 'none' ? ' · ' . ucfirst($post->competition->reward_type) . ' reward' : '') . '</div>',
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
            <?= Html::a(
                Html::tag('div',
                    '<div class="comp-icon">' . ($post->type === 'hackathon' ? '⚡' : '🏆') . '</div>'
                    . '<div><div class="comp-name">' . Html::encode($post->title) . '</div>'
                    . '<div class="comp-meta">' . ucfirst($post->type)
                    . ($post->competition->reward_type !== 'none' ? ' · ' . ucfirst($post->competition->reward_type) . ' reward' : '')
                    . ' · by ' . Html::encode($post->author->username ?? 'Unknown') . '</div></div>'
                    . '<div class="comp-tag tag-live">Live</div>',
                    ['class' => 'comp-item']
                ),
                ['view', 'id' => $post->id],
                ['style' => 'text-decoration: none; display: block;']
            ) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>