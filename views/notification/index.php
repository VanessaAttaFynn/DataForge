<?php

/** @var app\models\Notification[] $notifications */

use yii\helpers\Html;

$this->title = 'Notifications';
$unread = count(array_filter($notifications, fn($n) => !$n->is_read));
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Space</div>
        <h1 class="page-title">Notifications</h1>
        <div class="page-sub"><?= $unread ?> unread</div>
    </div>
    <?php if ($unread > 0): ?>
        <?= Html::a('Mark all as read', ['read-all'], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 18px;', 'data' => ['method' => 'post']]) ?>
    <?php endif; ?>
</div>

<div class="panel">
    <?php if (empty($notifications)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">No notifications yet.</p>
    <?php endif; ?>
    <?php foreach ($notifications as $n): ?>
        <?= Html::a(
            '<div class="feed-dot" style="' . ($n->is_read ? 'background: var(--text-faint);' : '') . '"></div>'
            . '<div style="flex: 1;">'
            . '<div class="feed-text" style="' . ($n->is_read ? 'color: var(--text-dim);' : 'font-weight: 600;') . '">' . Html::encode($n->message) . '</div>'
            . '<div class="feed-time">' . strtoupper(Yii::$app->formatter->asRelativeTime($n->created_at)) . '</div>'
            . '</div>'
            . ($n->is_read ? '' : '<span class="comp-tag" style="align-self: center;">New</span>'),
            ['open', 'id' => $n->id],
            ['class' => 'feed-item', 'style' => 'text-decoration: none; color: inherit; display: flex; gap: 12px;']
        ) ?>
    <?php endforeach; ?>
</div>
