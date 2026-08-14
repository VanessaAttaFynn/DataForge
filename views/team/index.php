<?php

/** @var app\models\Post $post */
/** @var app\models\Team[] $teams */
/** @var int[] $myMembershipTeamIds */
/** @var int[] $myPendingTeamIds */

use yii\helpers\Html;

$this->title = 'Teams';
$userId = Yii::$app->user->id;
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Teams</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <?php $registeredCount = count(array_filter($teams, fn($t) => $t->isRegistered())); ?>
        <div class="page-sub"><?= count($teams) ?> team(s) formed · <?= $registeredCount ?> registered</div>
    </div>
    <?= Html::a('+ Create Team', ['create', 'competitionId' => $post->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel">
    <?php if (empty($teams)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">No teams yet — be the first to create one.</p>
    <?php endif; ?>

    <?php foreach ($teams as $team): ?>
        <?php
        $limit = $post->competition->team_size_limit;
        $count = $team->getConsentedMemberCount();
        $capacityLabel = $limit !== null ? "{$count}/{$limit}" : "{$count}";
        $isOwner = (int) $team->owner_id === (int) $userId;
        $isMember = in_array($team->id, $myMembershipTeamIds);
        $isPending = in_array($team->id, $myPendingTeamIds);
        ?>
        <div class="team-chip" style="justify-content: space-between; padding: 14px; align-items: flex-start; flex-wrap: wrap;">
            <div>
                <div class="team-name" style="font-size: 14px;">
                    <?= Html::encode($team->name) ?>
                    <?php if ($team->isRegistered()): ?><span style="color: var(--gold-bright); font-size: 11px; margin-left: 6px;">✓ Registered</span><?php endif; ?>
                </div>
                <div class="team-meta"><?= $capacityLabel ?> members · owner: <?= Html::encode($team->owner->username ?? 'Unknown') ?></div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <?php if ($isOwner): ?>
                    <?= Html::a('Search & Invite', ['search-users', 'teamId' => $team->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 14px; font-size: 12px;']) ?>
                    <?php if (!$team->isRegistered()): ?>
                        <?= Html::a('Register Team', ['register', 'id' => $team->id], [
                            'class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 7px 14px; font-size: 12px;',
                            'data' => ['method' => 'post', 'confirm' => 'Register this team for the competition?'],
                        ]) ?>
                    <?php endif; ?>

                    <?php $pending = $team->pendingMemberships; ?>
                    <?php if (!empty($pending)): ?>
                        <div style="width: 100%; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border);">
                            <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 6px;">PENDING REQUESTS</div>
                            <?php foreach ($pending as $req): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0;">
                                    <span style="font-size: 12.5px;"><?= Html::encode($req->user->username ?? 'Unknown') ?></span>
                                    <div style="display: flex; gap: 6px;">
                                        <?= Html::a('Approve', ['approve-member', 'membershipId' => $req->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 5px 12px; font-size: 11.5px;', 'data' => ['method' => 'post']]) ?>
                                        <?= Html::a('Decline', ['decline-member', 'membershipId' => $req->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 5px 12px; font-size: 11.5px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif ($isMember): ?>
                    <?= Html::a('Leave', ['leave', 'id' => $team->id], [
                        'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px; color: var(--rose);',
                        'data' => ['method' => 'post', 'confirm' => "Leave \"{$team->name}\"?"],
                    ]) ?>
                <?php elseif ($isPending): ?>
                    <span class="comp-tag tag-new">Request pending</span>
                <?php elseif ($team->isFull()): ?>
                    <span class="comp-tag" style="color: var(--text-faint);">Full</span>
                <?php else: ?>
                    <?= Html::a('Ask to Join', ['request-join', 'id' => $team->id], [
                        'class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 7px 16px;',
                        'data' => ['method' => 'post'],
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>