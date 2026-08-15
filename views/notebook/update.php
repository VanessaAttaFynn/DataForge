<?php

/** @var app\models\Post $post */
/** @var app\models\Notebook $notebook */
/** @var app\models\Post[] $datasets */
/** @var app\models\Post[] $competitions */
/** @var app\models\Post[] $hackathons */
/** @var string $tagsValue */

use yii\helpers\Html;

$this->title = 'Edit Notebook';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Editing</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<?= $this->render('_form', ['post' => $post, 'notebook' => $notebook, 'tagsValue' => $tagsValue, 'isUpdate' => true, 'datasets' => $datasets, 'competitions' => $competitions, 'hackathons' => $hackathons]) ?>