<?php

/** @var app\models\Post $post */
/** @var app\models\Dataset $dataset */
/** @var app\models\Post[] $competitions */
/** @var app\models\Post[] $hackathons */

use yii\helpers\Html;

$this->title = 'Publish Dataset';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">New dataset</div>
        <h1 class="page-title">Publish a Dataset</h1>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<?= $this->render('_form', ['post' => $post, 'dataset' => $dataset, 'isUpdate' => false, 'competitions' => $competitions, 'hackathons' => $hackathons]) ?>