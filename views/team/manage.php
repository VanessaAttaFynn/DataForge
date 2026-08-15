<?php

/** @var app\models\Team $team */
/** @var bool $isOwner */
/** @var string $q */
/** @var app\models\User[] $searchResults */
/** @var array $stats */
/** @var app\models\TeamCompetitionRegistration[] $registrations */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $team->name;
$memberCount = $team->getConsentedMemberCount();
?>
<div class="topbar" style="margin-bottom: 20px;">
    <div>
        <div class="eyebrow">Team Dashboard</div>
        <h1 class="page-title"><?= Html::encode($team->name) ?></h1>
    </div>
    <?= Html::a('Explore Competitions', ['/competition/index'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<!-- Banner -->
<div style="position: relative; height: 140px; border-radius: 18px; margin-bottom: 20px; overflow: hidden;
            background: linear-gradient(120deg, var(--gold-dim), var(--gold-bright) 45%, var(--gold) 100%);
            background-image:
                radial-gradient(circle at 15% 30%, rgba(255,255,255,0.18) 0, transparent 4%),
                radial-gradient(circle at 35% 70%, rgba(255,255,255,0.14) 0, transparent 3%),
                radial-gradient(circle at 60% 20%, rgba(255,255,255,0.16) 0, transparent 3.5%),
                radial-gradient(circle at 80% 60%, rgba(255,255,255,0.12) 0, transparent 3%),
                radial-gradient(circle at 50% 85%, rgba(255,255,255,0.15) 0, transparent 4%),
                linear-gradient(120deg, var(--gold-dim), var(--gold-bright) 45%, var(--gold) 100%);
            border: 1px solid var(--border-strong);">
    <div style="position: absolute; bottom: 16px; left: 24px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #14110A; border: 3px solid rgba(255,255,255,0.5);
                    display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
            <?php if ($team->avatar_path): ?>
                <img src="<?= Html::encode(\yii\helpers\Url::to(['/site/serve-image', 'path' => $team->avatar_path])) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="color: var(--gold-bright); font-family: 'Fraunces', serif; font-size: 22px; font-weight: 700;"><?= Html::encode(mb_substr($team->name, 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-family: 'Fraunces', serif; font-size: 20px; font-weight: 700; color: #14110A;"><?= Html::encode($team->name) ?></div>
            <div style="font-size: 12px; color: #3A2E12; font-weight: 600;"><?= $memberCount ?>/<?= $team->cap ?> members</div>
        </div>
    </div>
    <?php if ($isOwner): ?>
    <form method="post" action="<?= Url::to(['upload-avatar', 'id' => $team->id]) ?>" enctype="multipart/form-data"
          style="position: absolute; top: 14px; right: 16px;">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
        <label style="background: rgba(20,17,10,0.55); color: #fff; font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 20px; cursor: pointer;">
            Change Logo
            <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()" style="display: none;">
        </label>
    </form>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="stat-row" style="grid-template-columns: repeat(5, 1fr);">
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 20px;"><?= $stats['challenges_participated'] ?></div><div class="stat-label">Challenges Entered</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 20px;"><?= $stats['challenges_won'] ?></div><div class="stat-label">Challenges Won</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 20px;"><?= $stats['hackathons_participated'] ?></div><div class="stat-label">Hackathons Entered</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 20px;"><?= $stats['hackathons_won'] ?></div><div class="stat-label">Hackathons Won</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 20px;"><?= $stats['top_10_count'] ?></div><div class="stat-label">Top 10 Finishes</div></div>
    </div>
</div>
<div style="font-size: 11px; color: var(--text-faint); margin: -8px 0 20px 4px;">Win / top-10 counts are based on final rank once a competition has ended.</div>

<?php if ($isOwner): ?>
<div class="panel">
    <div class="panel-head"><div class="panel-title">Search & Invite</div></div>
    <form method="get" style="margin-bottom: <?= empty($searchResults) ? '0' : '16px' ?>;">
        <div class="search-box" style="width: 100%; max-width: 420px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Search by username or email…">
        </div>
    </form>
    <?php foreach ($searchResults as $user): ?>
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
    <?php if ($q !== '' && empty($searchResults)): ?>
        <p style="color: var(--text-faint); font-size: 12.5px;">No matching users found.</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="panel">
    <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px;">
        <button type="button" class="nav-item active tab-btn" data-tab="members" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('members')">Members (<?= $memberCount ?>)</button>
        <?php if ($isOwner): ?>
        <button type="button" class="nav-item tab-btn" data-tab="pending" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('pending')">Pending Invites (<?= count($team->pendingMemberships) ?>)</button>
        <button type="button" class="nav-item tab-btn" data-tab="history" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('history')">History</button>
        <?php endif; ?>
    </div>

    <div id="tab-members" class="tab-panel">
        <?php foreach ($team->memberships as $m): ?>
            <div class="team-chip" style="justify-content: space-between;">
                <div>
                    <div class="team-name"><?= Html::encode($m->user->username ?? 'Unknown') ?><?= (int) $m->user_id === (int) $team->owner_id ? ' (owner)' : '' ?></div>
                </div>
                <?php if ($isOwner && (int) $m->user_id !== (int) $team->owner_id): ?>
                    <?= Html::a('Remove', ['remove-member', 'membershipId' => $m->id], [
                        'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 6px 14px; color: var(--rose); font-size: 12px;',
                        'data' => ['method' => 'post', 'confirm' => 'Remove this member?'],
                    ]) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($isOwner): ?>
    <div id="tab-pending" class="tab-panel" style="display: none;">
        <?php $pending = $team->pendingMemberships; ?>
        <?php if (empty($pending)): ?>
            <p style="color: var(--text-faint); font-size: 12.5px;">No pending invites or requests right now.</p>
        <?php endif; ?>
        <?php foreach ($pending as $req): ?>
            <div class="team-chip" style="justify-content: space-between; align-items: flex-start;">
                <div>
                    <div class="team-name"><?= Html::encode($req->user->username ?? 'Unknown') ?></div>
                    <?php if ($req->note): ?><div class="team-meta" style="font-style: italic;">"<?= Html::encode($req->note) ?>"</div><?php endif; ?>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?= Html::a('Approve', ['approve-member', 'membershipId' => $req->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 6px 14px; font-size: 12px;', 'data' => ['method' => 'post']]) ?>
                    <?= Html::a('Decline', ['decline-member', 'membershipId' => $req->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 6px 14px; font-size: 12px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-history" class="tab-panel" style="display: none;">
        <?php if (empty($registrations)): ?>
            <p style="color: var(--text-faint); font-size: 12.5px;">This team hasn't registered for any competitions yet.</p>
        <?php endif; ?>
        <?php foreach ($registrations as $reg): ?>
            <?= Html::a(
                Html::tag('div', '<div class="team-name">' . Html::encode($reg->competitionPost->title) . '</div><div class="team-meta">' . ucfirst($reg->competitionPost->type) . '</div>', ['class' => 'team-chip']),
                ['/competition/view', 'id' => $reg->competition_id],
                ['style' => 'text-decoration: none; display: block;']
            ) ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    document.querySelector('.tab-btn[data-tab="' + name + '"]').classList.add('active');
}
</script>