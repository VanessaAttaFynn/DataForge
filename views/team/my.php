<?php

/** @var app\models\TeamMembership[] $memberships */
/** @var app\models\TeamMembership[] $pendingInvites */

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

<?php if (!empty($pendingInvites)): ?>
<div class="panel">
    <div class="panel-head"><div class="panel-title">Pending Invites (<?= count($pendingInvites) ?>)</div></div>
    <?php foreach ($pendingInvites as $inv): ?>
        <div class="team-chip" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="team-name"><?= Html::encode($inv->team->name) ?></div>
                <?php if ($inv->note): ?><div class="team-meta" style="font-style: italic;">"<?= Html::encode($inv->note) ?>"</div><?php endif; ?>
            </div>
            <div style="display: flex; gap: 8px;">
                <?= Html::a('Accept', ['accept-invite', 'membershipId' => $inv->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 7px 16px;', 'data' => ['method' => 'post']]) ?>
                <?= Html::a('Decline', ['decline-invite', 'membershipId' => $inv->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 7px 16px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
            </div>
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
                '<div class="team-name">' . Html::encode($m->team->name) . '</div>'
                . '<div class="team-meta">' . $m->team->getConsentedMemberCount() . '/' . $m->team->cap . ' members</div>',
                ['class' => 'team-chip']
            ),
            ['/team/manage', 'id' => $m->team->id],
            ['style' => 'text-decoration: none; display: block;']
        ) ?>
    <?php endforeach; ?>
</div>