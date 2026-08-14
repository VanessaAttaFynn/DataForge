<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\Team[] $ownedTeams */

use yii\helpers\Html;

$this->title = 'Register Team';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Register a Team</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <?php if ($competition->team_size_limit): ?>
            <div class="page-sub">Team cap for this competition: <?= $competition->team_size_limit ?> members</div>
        <?php endif; ?>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel" style="max-width: 500px;">
    <?php if (empty($ownedTeams)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px; margin-bottom: 14px;">You don't own any teams yet.</p>
        <?= Html::a('+ Create a Team', ['/team/create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
    <?php else: ?>
        <form method="post">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 8px;">Which team?</label>
            <?php foreach ($ownedTeams as $team): ?>
                <label class="team-chip" style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                    <input type="radio" name="team_id" value="<?= $team->id ?>" required>
                    <span><?= Html::encode($team->name) ?> · <?= $team->getConsentedMemberCount() ?>/<?= $team->cap ?> members</span>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; margin-top: 14px; border: 1px solid var(--border-strong); cursor: pointer;">
                Register
            </button>
        </form>
    <?php endif; ?>
</div>