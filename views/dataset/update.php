<?php

/** @var app\models\Post $post */
/** @var app\models\Dataset $dataset */
/** @var app\models\Post[] $competitions */
/** @var app\models\Post[] $hackathons */

use yii\helpers\Html;

$this->title = 'Edit Dataset';
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

<?= $this->render('_form', ['post' => $post, 'dataset' => $dataset, 'isUpdate' => true, 'competitions' => $competitions, 'hackathons' => $hackathons]) ?>