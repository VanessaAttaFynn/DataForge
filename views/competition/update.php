<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */

use yii\helpers\Html;

$this->title = 'Edit Competition';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Editing</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">
            <?php if ($post->status === 'published'): ?>
                This is already live — changes save immediately, no re-review needed.
            <?php elseif ($post->status === 'pending'): ?>
                Still awaiting approval — edits here don't reset the queue position.
            <?php elseif ($post->status === 'rejected'): ?>
                This was rejected. Saving changes moves it back to pending for another review.
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;">
        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<?= $this->render('_form', ['post' => $post, 'competition' => $competition, 'isUpdate' => true]) ?>