<?php

/** @var app\models\Team $team */
/** @var string $q */
/** @var app\models\User[] $results */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Search & Invite';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Team: <?= Html::encode($team->name) ?></div>
        <h1 class="page-title">Search & Invite</h1>
    </div>
    <?= Html::a('← All Teams', ['/team/index', 'competitionId' => $team->competition_id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel" style="max-width: 500px;">
    <form method="get">
        <div class="search-box" style="width: 100%; margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Search by username or email…" autofocus>
        </div>
    </form>

    <?php if ($q !== '' && empty($results)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">No matching users found.</p>
    <?php endif; ?>

    <?php foreach ($results as $user): ?>
        <div class="team-chip" style="justify-content: space-between;">
            <div>
                <div class="team-name"><?= Html::encode($user->username) ?></div>
                <div class="team-meta"><?= Html::encode($user->email) ?></div>
            </div>
            <?= Html::a('Send Invite', ['invite', 'teamId' => $team->id, 'userId' => $user->id], [
                'class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 7px 16px;',
                'data' => ['method' => 'post'],
            ]) ?>
        </div>
    <?php endforeach; ?>
</div>