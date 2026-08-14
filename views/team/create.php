<?php

/** @var app\models\Team $team */

use yii\helpers\Html;

$this->title = 'Create Team';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">New team</div>
        <h1 class="page-title">Create a Team</h1>
        <div class="page-sub">Standalone — you'll register it for specific competitions later.</div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel" style="max-width: 420px;">
    <form method="post">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Team name</label>
        <input type="text" name="name" required
               style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif; margin-bottom: 18px;">
        <div style="font-size: 11.5px; color: var(--text-faint); margin-bottom: 18px;">Member cap: 10 (fixed for now)</div>
        <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">
            Create Team
        </button>
    </form>
</div>