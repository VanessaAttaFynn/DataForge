<?php

/** @var \yii\web\View $this */
/** @var app\models\User[] $users */
/** @var array $userRoles */
/** @var string[] $availableRoles */

use app\models\User;
use yii\helpers\Html;

$this->title = 'Users & Roles';

$roleCounts = array_count_values(array_filter($userRoles));
$roleTag = [
    'admin' => 'tag-closing',
    'moderator' => 'tag-new',
    'student' => 'tag-live',
];
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Admin</div>
        <h1 class="page-title">Users &amp; Roles</h1>
        <div class="page-sub">Change what each person is allowed to do on DataForge.</div>
    </div>
</div>

<div class="stat-row">
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num"><?= count($users) ?></div><div class="stat-label">Total Users</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num"><?= $roleCounts['admin'] ?? 0 ?></div><div class="stat-label">Admins</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num"><?= $roleCounts['moderator'] ?? 0 ?></div><div class="stat-label">Moderators</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num"><?= $roleCounts['student'] ?? 0 ?></div><div class="stat-label">Students</div></div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div class="panel-title">All Users</div>
        <div class="search-box" style="width: 260px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" id="user-filter" placeholder="Search name or email…">
        </div>
    </div>

    <?php if (empty($users)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">No users yet.</p>
    <?php endif; ?>

    <?php foreach ($users as $user): ?>
        <?php $role = $userRoles[$user->id] ?? null; ?>
        <div class="comp-item user-row" data-search="<?= Html::encode(strtolower($user->username . ' ' . $user->email)) ?>" style="cursor: default;">
            <div class="avatar" style="width: 38px; height: 38px; font-size: 12px; flex-shrink: 0;"><?= Html::encode($user->initials) ?></div>

            <div style="flex: 1; min-width: 0;">
                <div class="comp-name">
                    <?= Html::encode($user->username) ?>
                    <?php if ((int) $user->id === (int) Yii::$app->user->id): ?>
                        <span style="font-size: 11px; color: var(--text-faint); font-weight: 500;">(you)</span>
                    <?php endif; ?>
                </div>
                <div class="comp-meta">
                    <?= Html::encode($user->email) ?>
                    · Joined <?= Yii::$app->formatter->asDate($user->created_at, 'medium') ?>
                    <?php if ((int) $user->status !== User::STATUS_ACTIVE): ?>
                        · <span style="color: var(--rose);">Email not verified</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="comp-tag <?= $roleTag[$role] ?? '' ?>" style="margin-left: 0; margin-right: 6px;">
                <?= $role ? Html::encode(ucfirst($role)) : 'No role' ?>
            </div>

            <?= Html::beginForm(['set-role', 'id' => $user->id], 'post', ['style' => 'display: flex; gap: 8px; align-items: center; margin: 0;']) ?>
                <select name="role"
                        style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 12.5px;">
                    <?php foreach ($availableRoles as $r): ?>
                        <option value="<?= Html::encode($r) ?>" <?= $r === $role ? 'selected' : '' ?>><?= Html::encode(ucfirst($r)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="nav-item active"
                        style="display: inline-flex; padding: 7px 14px; margin: 0; border: none; cursor: pointer; font-size: 12.5px;"
                        data-confirm="Change <?= Html::encode($user->username) ?>'s role?">Save</button>
            <?= Html::endForm() ?>
        </div>
    <?php endforeach; ?>

    <p id="user-filter-empty" style="display: none; color: var(--text-faint); font-size: 12.5px; padding: 10px;">No users match that search.</p>
</div>

<script>
document.getElementById('user-filter').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    var shown = 0;
    document.querySelectorAll('.user-row').forEach(function (row) {
        var match = row.dataset.search.indexOf(q) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    document.getElementById('user-filter-empty').style.display = shown ? 'none' : 'block';
});
</script>
