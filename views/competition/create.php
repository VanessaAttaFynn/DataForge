<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */

use yii\helpers\Html;

$this->title = 'Create Competition';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">New submission</div>
        <h1 class="page-title">Create a Competition or Hackathon</h1>
        <div class="page-sub">This goes to moderator review before it goes live — you'll be notified once it's approved or rejected.</div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;">
        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<?= $this->render('_form', ['post' => $post, 'competition' => $competition, 'isUpdate' => false]) ?>