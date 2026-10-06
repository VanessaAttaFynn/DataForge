<?php

/** @var app\models\TeamMembership[] $memberships */
/** @var app\models\TeamMembership[] $pendingInvites */
/** @var app\models\TeamMembership[] $myRequests */
/** @var app\models\TeamMembership[] $pastTeams */

use yii\helpers\Html;

$this->title = 'My Teams';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Space</div>
        <h1 class="page-title">My Teams</h1>
    </div>
    <?= Html::a('+ Create Team', ['/team/create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 18px;']) ?>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<?php if (!empty($pendingInvites)): ?>
<div class="panel">
    <div class="panel-head"><div class="panel-title">Invites for You (<?= count($pendingInvites) ?>)</div></div>
    <?php foreach ($pendingInvites as $inv): ?>
        <div class="team-chip" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="team-name"><?= Html::encode($inv->team->name) ?></div>
                <div class="team-meta">Invited by <?= Html::encode($inv->team->owner->username ?? 'the owner') ?></div>
            </div>
            <div style="display: flex; gap: 8px;">
                <?= Html::a('Accept', ['accept-invite', 'membershipId' => $inv->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 7px 16px;', 'data' => ['method' => 'post']]) ?>
                <?= Html::a('Decline', ['decline-invite', 'membershipId' => $inv->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($myRequests)): ?>
<div class="panel">
    <div class="panel-head"><div class="panel-title">Your Join Requests (<?= count($myRequests) ?>)</div></div>
    <?php foreach ($myRequests as $req): ?>
        <div class="team-chip" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="team-name"><?= Html::encode($req->team->name) ?></div>
                <div class="team-meta">Waiting for the owner to approve</div>
            </div>
            <?= Html::a('Cancel request', ['decline-invite', 'membershipId' => $req->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head"><div class="panel-title">My Teams (<?= count($memberships) ?>)</div></div>
    <?php if (empty($memberships)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">You're not on any teams yet.</p>
    <?php endif; ?>
    <?php foreach ($memberships as $m): ?>
        <?= Html::a(
            Html::tag('div',
                '<div class="team-name">' . Html::encode($m->team->name)
                . ((int) $m->team->owner_id === (int) Yii::$app->user->id ? ' <span style="font-size: 11px; color: var(--gold);">owner</span>' : '') . '</div>'
                . '<div class="team-meta">' . $m->team->getConsentedMemberCount() . '/' . $m->team->cap . ' members</div>',
                ['class' => 'team-chip']
            ),
            ['/team/manage', 'id' => $m->team->id],
            ['style' => 'text-decoration: none; display: block;']
        ) ?>
    <?php endforeach; ?>
</div>

<?php if (!empty($pastTeams)): ?>
<div class="panel">
    <div class="panel-head"><div class="panel-title">Past Teams (<?= count($pastTeams) ?>)</div></div>
    <div style="font-size: 11.5px; color: var(--text-faint); margin-bottom: 10px;">Teams you left, or that were archived when their last member left. Read-only.</div>
    <?php foreach ($pastTeams as $m): ?>
        <?php $team = $m->team; ?>
        <div class="team-chip" style="justify-content: space-between; opacity: 0.8;">
            <div>
                <div class="team-name"><?= Html::encode($team->name) ?></div>
                <div class="team-meta">
                    <?php if ($team->isArchived()): ?>
                        Archived <?= date('M j, Y', $team->archived_at) ?>
                    <?php else: ?>
                        You left<?= $m->responded_at ? ' ' . date('M j, Y', $m->responded_at) : '' ?>
                    <?php endif; ?>
                    · entered <?= count($team->registrations) ?> competition(s)
                </div>
            </div>
            <span class="comp-tag" style="color: var(--text-faint);"><?= $team->isArchived() ? 'Archived' : 'Left' ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
