<?php

/** @var \yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Log In';
?>
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="panel" style="max-width: 400px; width: 100%;">
        <div style="text-align: center; margin-bottom: 26px;">
            <div class="brand-mark" style="margin: 0 auto 12px; width: 44px; height: 44px; font-size: 20px;">DF</div>
            <div class="brand-name" style="font-size: 22px;">DataForge</div>
            <div class="brand-sub">University of Ghana</div>
        </div>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div style="background: rgba(184,97,90,0.12); border: 1px solid var(--rose); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: var(--rose);">
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div style="background: rgba(74,155,127,0.12); border: 1px solid var(--emerald); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: var(--emerald);">
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

        <div style="margin-bottom: 16px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Username or email</label>
            <input type="text" name="LoginForm[username]" value="<?= Html::encode($model->username) ?>" required autofocus
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <?php foreach ($model->getErrors('username') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom: 10px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Password</label>
            <input type="password" name="LoginForm[password]" required
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <?php foreach ($model->getErrors('password') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-dim); margin-bottom: 20px;">
            <input type="checkbox" name="LoginForm[rememberMe]" value="1" checked> Remember me
        </label>

        <button type="submit" class="nav-item active" style="width: 100%; justify-content: center; padding: 11px; border: 1px solid var(--border-strong); cursor: pointer;">
            Log In
        </button>

        <?php ActiveForm::end(); ?>

        <div style="text-align: center; margin-top: 18px; font-size: 12.5px; color: var(--text-faint);">
            No account? <?= Html::a('Sign up', ['site/signup'], ['style' => 'color: var(--gold);']) ?>
        </div>
    </div>
</div>