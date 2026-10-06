<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\TeamCompetitionRegistration[] $registrations */
/** @var int[] $myTeamIds */
/** @var int[] $myPendingTeamIds */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Teams';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Registered Teams</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">
            <?= count($registrations) ?> team(s) registered<?= $competition->teamSizeLabel() ? ' · team size ' . Html::encode($competition->teamSizeLabel()) : '' ?>
        </div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel">
    <?php if (empty($registrations)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">No teams registered yet.</p>
    <?php endif; ?>
    <div style="font-size: 11.5px; color: var(--text-faint); margin-bottom: 12px;">
        Joining a team adds you to the team — the owner then decides whether to put you on this competition's line-up (while registration is open).
    </div>
    <?php foreach ($registrations as $reg): ?>
        <?php
        $team = $reg->team;
        $lineupCount = $reg->getLineupCount();
        $isMember = in_array($team->id, $myTeamIds);
        $isPending = in_array($team->id, $myPendingTeamIds);
        ?>
        <div class="team-chip" style="justify-content: space-between; padding: 14px; align-items: flex-start;">
            <div>
                <div class="team-name" style="font-size: 14px;"><?= Html::encode($team->name) ?></div>
                <div class="team-meta">
                    Line-up: <?= $lineupCount ?><?= $competition->team_size_limit ? '/' . $competition->team_size_limit : '' ?>
                    · team members: <?= $team->getConsentedMemberCount() ?>/<?= $team->cap ?>
                    · owner: <?= Html::encode($team->owner->username ?? 'Unknown') ?>
                </div>
            </div>

            <?php if ($team->isArchived()): ?>
                <span class="comp-tag" style="color: var(--text-faint);">Archived</span>
            <?php elseif (Yii::$app->user->isGuest): ?>
                <?php // nothing for guests ?>
            <?php elseif ($isMember): ?>
                <?= Html::a('Manage', ['/team/manage', 'id' => $team->id, '#' => 'competitions'], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px;']) ?>
            <?php elseif ($isPending): ?>
                <span class="comp-tag tag-new">Pending</span>
            <?php elseif ($team->isFull()): ?>
                <span class="comp-tag" style="color: var(--text-faint);">Full</span>
            <?php else: ?>
                <form method="post" action="<?= Url::to(['/team/request-join', 'id' => $team->id, 'competitionId' => $post->id]) ?>" style="display: flex; gap: 8px; align-items: center;">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="text" name="note" placeholder="Optional note, e.g. 'for this competition'"
                           style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; color: var(--text); font-size: 12px; font-family: 'Inter', sans-serif; width: 220px;">
                    <button type="submit" class="nav-item active" style="display: inline-flex; padding: 7px 16px; border: none; cursor: pointer;">Ask to Join</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
