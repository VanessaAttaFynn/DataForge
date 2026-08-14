<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$this->registerCssFile('@web/css/dataforge.css');
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
    <?php $this->head() ?>
</head>
<body data-theme="dark">
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>