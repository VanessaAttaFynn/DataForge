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
            <div class="brand-mark" style="margin: 0 auto 12px; width: 96px; height: 96px; background: none; border-radius: 0;"><img src="<?= Yii::$app->request->baseUrl ?>/images/logo.png" alt="DataForge" style="width: 100%; height: 100%; object-fit: contain;"></div>
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
            <div style="position: relative;">
                <input type="password" name="SignupForm[password]" required minlength="8"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 40px 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <button type="button" class="toggle-password" aria-label="Show password" tabindex="-1"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-faint); display: flex; align-items: center;"><svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
            </div>
            <?php foreach ($model->getErrors('password') as $error): ?>
                <div style="color: var(--rose); font-size: 11.5px; margin-top: 5px;"><?= Html::encode($error) ?></div>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Confirm password</label>
            <div style="position: relative;">
                <input type="password" name="SignupForm[password_confirm]" required minlength="8"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 10px 40px 10px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <button type="button" class="toggle-password" aria-label="Show password" tabindex="-1"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: var(--text-faint); display: flex; align-items: center;"><svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
            </div>
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

<script>
document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.parentElement.querySelector('input');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('.eye-open').style.display = show ? 'none' : '';
        btn.querySelector('.eye-closed').style.display = show ? '' : 'none';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>
