<?php

/** @var \yii\web\View $this */
/** @var app\models\SignupForm $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Sign Up';
?>
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="panel" style="max-width: 420px; width: 100%;">
        <div style="text-align: center; margin-bottom: 22px;">
            <div class="brand-mark" style="margin: 0 auto 12px; width: 44px; height: 44px; font-size: 20px;">DF</div>
            <div class="brand-name" style="font-size: 22px;">Create your account</div>
            <div class="brand-sub">Use your ug.edu.gh or st.ug.edu.gh email</div>
        </div>

        <?php $form = ActiveForm::begin(['id' => 'signup-form', 'options' => ['method' => 'post']]); ?>

        <?php foreach (['username' => 'Username', 'email' => 'University email'] as $field => $label): ?>
            <div style="margin-bottom: 16px;">
                <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;"><?= $label ?></label>
                <input type="text" name="SignupForm[<?= $field ?>]" value="<?= Html::encode($model->$field) ?>" required
                       placeholder="<?= $field === 'email' ? 'name@st.ug.edu.gh' : '' ?>"
                       style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <?php foreach ($model->getErrors($field) as $error): ?>
                    <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-bottom: 16px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Password</label>
            <input type="password" name="SignupForm[password]" required minlength="8"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <?php foreach ($model->getErrors('password') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Confirm password</label>
            <input type="password" name="SignupForm[password_confirm]" required minlength="8"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <?php foreach ($model->getErrors('password_confirm') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="nav-item active" style="width: 100%; justify-content: center; padding: 11px; border: 1px solid var(--border-strong); cursor: pointer;">
            Create Account
        </button>

        <?php ActiveForm::end(); ?>

        <div style="text-align: center; margin-top: 18px; font-size: 12.5px; color: var(--text-faint);">
            Already have an account? <?= Html::a('Log in', ['site/login'], ['style' => 'color: var(--gold);']) ?>
        </div>
    </div>
</div>