<?php

/** @var \yii\web\View $this */
/** @var app\models\PasswordResetRequestForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Forgot Password';
?>
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="panel" style="max-width: 400px; width: 100%;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="brand-mark" style="margin: 0 auto 12px; width: 96px; height: 96px; background: none; border-radius: 0;"><img src="<?= Yii::$app->request->baseUrl ?>/images/logo.png" alt="DataForge" style="width: 100%; height: 100%; object-fit: contain;"></div>
            <div class="brand-name" style="font-size: 22px;">Forgot your password?</div>
            <div style="font-size: 13px; color: var(--text-dim); margin-top: 8px; line-height: 1.5;">Enter the email you signed up with and we'll send you a link to choose a new one.</div>
        </div>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div style="background: rgba(184,97,90,0.12); border: 1px solid var(--rose); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: var(--rose);">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

        <div style="margin-bottom: 20px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Email</label>
            <input type="email" name="PasswordResetRequestForm[email]" value="<?= Html::encode($model->email) ?>" required autofocus
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <?php foreach ($model->getErrors('email') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="nav-item active" style="width: 100%; justify-content: center; padding: 11px; border: 1px solid var(--border-strong); cursor: pointer;">
            Send Reset Link
        </button>

        <?php ActiveForm::end(); ?>

        <div style="text-align: center; margin-top: 18px; font-size: 12.5px; color: var(--text-faint);">
            Remembered it? <?= Html::a('Back to log in', ['site/login'], ['style' => 'color: var(--gold);']) ?>
        </div>
    </div>
</div>
