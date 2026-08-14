<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

// Register the stylesheet + Google fonts.
// Cache-bust with the file's actual modification time, so browsers always
// fetch a fresh copy the moment this file changes on disk — no more
// stale-CSS confusion from browser caching.
$cssVersion = @filemtime(Yii::getAlias('@webroot/css/dataforge.css')) ?: time();
$this->registerCssFile('@web/css/dataforge.css?v=' . $cssVersion, ['depends' => []]);
$this->registerCssFile(
    'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700'
    . '&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap'
);

// Theme toggle script — register in HEAD so it runs before first paint (avoids a flash of the wrong theme).
$this->registerJsFile('@web/js/theme-toggle.js', ['position' => \yii\web\View::POS_HEAD]);

// Registers yii.js — powers Html::a's data-method / data-confirm
// attributes (used by Logout, Approve/Reject, Delete buttons, etc.)
\yii\web\YiiAsset::register($this);

$this->title = $this->title ? $this->title . ' — DataForge' : 'DataForge — University of Ghana';

$currentUser = Yii::$app->user->identity ?? null;
$unreadCount = $currentUser
    ? \app\models\Notification::find()->where(['user_id' => $currentUser->id, 'is_read' => 0])->count()
    : 0;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body data-theme="dark">
<?php $this->beginBody() ?>

<?= $this->render('_sidebar') ?>

<?php if ($currentUser !== null): ?>
<div class="account-bar">
    <button type="button" class="icon-btn" title="Notifications">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M9.5 20a2.5 2.5 0 0 0 5 0"/></svg>
            <?php if ($unreadCount > 0): ?><span class="icon-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span><?php endif; ?>
    </button>

    <div class="account-menu-wrap">
        <button type="button" class="avatar account-trigger" onclick="document.getElementById('account-dropdown').classList.toggle('open')">
            <?= \yii\helpers\Html::encode($currentUser->initials) ?>
        </button>
        <div id="account-dropdown" class="account-dropdown">
            <div class="account-dropdown-name"><?= \yii\helpers\Html::encode($currentUser->username) ?></div>
            <div class="account-dropdown-email"><?= \yii\helpers\Html::encode($currentUser->email) ?></div>
            <hr>
            <?= \yii\helpers\Html::a('Profile', ['/user/profile'], ['class' => 'account-dropdown-link']) ?>
            <?php $csrfParam = Yii::$app->request->csrfParam; $csrfToken = Yii::$app->request->csrfToken; ?>
            <form method="post" action="<?= \yii\helpers\Url::to(['/site/logout']) ?>" style="margin: 0;">
                <?= \yii\helpers\Html::hiddenInput($csrfParam, $csrfToken) ?>
                <button type="submit" class="account-dropdown-link" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; color: var(--rose); font-family: 'Inter', sans-serif;">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<main class="main">
    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>