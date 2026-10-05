<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$cssVersion = @filemtime(Yii::getAlias('@webroot/css/dataforge.css')) ?: time();
$this->registerCssFile('@web/css/dataforge.css?v=' . $cssVersion);
$this->registerCssFile(
    'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700'
    . '&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap'
);
$this->registerJsFile('@web/js/theme-toggle.js', ['position' => \yii\web\View::POS_HEAD]);
\yii\web\YiiAsset::register($this);

$this->title = $this->title ? $this->title . ' — DataForge' : 'DataForge';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico" type="image/x-icon">
    <?php $this->head() ?>
</head>
<body data-theme="dark">
<?php $this->beginBody() ?>
<header style="position: fixed; top: 0; left: 0; right: 0; display: flex; justify-content: flex-end; padding: 16px 20px; z-index: 10;">
    <div class="theme-toggle" style="width: 170px;">
        <button type="button" id="btn-dark" class="active" onclick="setTheme('dark')">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9c0-.4 0-.8-.1-1.2A7 7 0 0 1 12 3Z"/></svg>
            Dark
        </button>
        <button type="button" id="btn-light" onclick="setTheme('light')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            Light
        </button>
    </div>
</header>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>