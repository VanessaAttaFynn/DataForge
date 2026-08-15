<?php

/** @var app\models\Post $post */
/** @var app\models\Notebook $notebook */
/** @var app\models\Post[] $datasets */
/** @var app\models\Post[] $competitions */
/** @var app\models\Post[] $hackathons */
/** @var string $tagsValue */

use yii\helpers\Html;

$this->title = 'Publish Notebook';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">New notebook</div>
        <h1 class="page-title">Publish a Notebook</h1>
        <div class="page-sub">Publishes immediately — no approval needed.</div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<?= $this->render('_form', ['post' => $post, 'notebook' => $notebook, 'tagsValue' => $tagsValue, 'isUpdate' => false, 'datasets' => $datasets, 'competitions' => $competitions, 'hackathons' => $hackathons]) ?>