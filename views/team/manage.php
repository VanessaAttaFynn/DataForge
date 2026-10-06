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
    <div style="display: flex; gap: 10px;">
        <?= Html::a('Explore Competitions', ['/competition/index'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
        <?php $soleMember = $memberCount === 1; ?>
        <?php if (!$isOwner || $soleMember): ?>
            <?= Html::a('Leave Team', ['leave', 'id' => $team->id], [
                'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 18px; color: var(--rose);',
                'data' => ['method' => 'post', 'confirm' => $soleMember
                    ? "You're the only member. Leaving will archive \"{$team->name}\" (it moves to Past teams and can't be changed). Continue?"
                    : "Leave \"{$team->name}\"? You'll also come off any line-up whose registration is still open."],
            ]) ?>
        <?php else: ?>
            <span class="nav-item" title="Hand ownership to another member first (Members tab → Make owner)" style="display: inline-flex; padding: 9px 18px; color: var(--text-faint); cursor: not-allowed;">Leave Team</span>
        <?php endif; ?>
    </div>
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
<?php
$requests = $isOwner ? $team->joinRequests : [];
$invites = $isOwner ? $team->pendingMemberships : [];
$members = $team->memberships;
$myId = (int) Yii::$app->user->id;
?>
<div class="panel">
    <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 14px; flex-wrap: wrap;">
        <button type="button" class="nav-item active tab-btn" data-tab="members" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('members')">Members (<?= $memberCount ?>)</button>
        <?php if ($isOwner): ?>
        <button type="button" class="nav-item tab-btn" data-tab="requests" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('requests')">Join Requests (<?= count($requests) ?>)</button>
        <button type="button" class="nav-item tab-btn" data-tab="invites" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('invites')">Invites Sent (<?= count($invites) ?>)</button>
        <?php endif; ?>
        <button type="button" class="nav-item tab-btn" data-tab="competitions" style="display: inline-flex; padding: 8px 18px; cursor: pointer; border: none;" onclick="switchTab('competitions')">Competitions (<?= count($registrations) ?>)</button>
    </div>

    <!-- Members -->
    <div id="tab-members" class="tab-panel">
        <?php foreach ($members as $m): ?>
            <?php $isThisOwner = (int) $m->user_id === (int) $team->owner_id; ?>
            <div class="team-chip" style="justify-content: space-between;">
                <div class="team-name"><?= Html::encode($m->user->username ?? 'Unknown') ?><?= $isThisOwner ? ' (owner)' : '' ?><?= (int) $m->user_id === $myId ? ' · you' : '' ?></div>
                <?php if ($isOwner && !$isThisOwner): ?>
                    <div style="display: flex; gap: 6px;">
                        <?= Html::a('Make owner', ['transfer-ownership', 'id' => $team->id, 'userId' => $m->user_id], [
                            'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 6px 14px; font-size: 12px;',
                            'data' => ['method' => 'post', 'confirm' => 'Make ' . ($m->user->username ?? 'this member') . ' the owner? You will stay on the team as a member.'],
                        ]) ?>
                        <?= Html::a('Remove', ['remove-member', 'membershipId' => $m->id], [
                            'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 6px 14px; color: var(--rose); font-size: 12px;',
                            'data' => ['method' => 'post', 'confirm' => 'Remove this member? They also come off any line-up whose registration is still open.'],
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($isOwner && $memberCount > 1): ?>
            <div style="font-size: 11.5px; color: var(--text-faint); margin-top: 8px;">To leave, first make another member the owner.</div>
        <?php endif; ?>
    </div>

    <?php if ($isOwner): ?>
    <!-- Join requests: people who asked to join. Only you can approve. -->
    <div id="tab-requests" class="tab-panel" style="display: none;">
        <?php if (empty($requests)): ?>
            <p style="color: var(--text-faint); font-size: 12.5px;">No one is waiting to join right now.</p>
        <?php endif; ?>
        <?php foreach ($requests as $req): ?>
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

    <!-- Invites you sent: waiting on the other person. -->
    <div id="tab-invites" class="tab-panel" style="display: none;">
        <?php if (empty($invites)): ?>
            <p style="color: var(--text-faint); font-size: 12.5px;">No invites waiting for an answer.</p>
        <?php endif; ?>
        <?php foreach ($invites as $inv): ?>
            <div class="team-chip" style="justify-content: space-between;">
                <div>
                    <div class="team-name"><?= Html::encode($inv->user->username ?? 'Unknown') ?></div>
                    <div class="team-meta">Waiting for them to accept</div>
                </div>
                <?= Html::a('Cancel invite', ['decline-member', 'membershipId' => $inv->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 6px 14px; font-size: 12px; color: var(--rose);', 'data' => ['method' => 'post']]) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Competitions + line-ups -->
    <div id="tab-competitions" class="tab-panel" style="display: none;">
        <?php if (empty($registrations)): ?>
            <p style="color: var(--text-faint); font-size: 12.5px;">This team hasn't registered for any competitions yet.</p>
        <?php endif; ?>
        <?php foreach ($registrations as $reg): ?>
            <?php
            $compPost = $reg->competitionPost;
            $comp = $compPost->competition;
            $open = $comp->isRegistrationOpen();
            $lineup = $reg->lineup;
            $lineupIds = array_map(fn($l) => (int) $l->user_id, $lineup);
            $addable = array_filter($members, fn($m) => !in_array((int) $m->user_id, $lineupIds, true));
            $atMax = $comp->team_size_limit !== null && count($lineup) >= (int) $comp->team_size_limit;
            ?>
            <div class="team-chip" style="flex-direction: column; align-items: stretch; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <?= Html::a(Html::encode($compPost->title), ['/competition/view', 'id' => $compPost->id], ['class' => 'team-name', 'style' => 'color: var(--text);']) ?>
                        <div class="team-meta">
                            <?= ucfirst($compPost->type) ?> · line-up <?= count($lineup) ?><?= $comp->team_size_limit ? '/' . $comp->team_size_limit : '' ?>
                            <?= $comp->teamSizeLabel() ? ' (allowed: ' . Html::encode($comp->teamSizeLabel()) . ')' : '' ?>
                            · <?= $open ? 'can change until registration closes' : '🔒 locked' ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    <?php foreach ($lineup as $l): ?>
                        <?php $isMe = (int) $l->user_id === $myId; ?>
                        <span class="comp-tag" style="margin-left: 0; display: inline-flex; gap: 6px; align-items: center; background: var(--panel-glass-strong); border: 1px solid var(--border); color: var(--text);">
                            <?= Html::encode($l->user->username ?? '?') ?>
                            <?php if ($open && ($isOwner || $isMe)): ?>
                                <?= Html::a($isMe && !$isOwner ? 'drop out' : '✕', ['lineup-remove', 'registrationId' => $reg->id, 'userId' => $l->user_id], [
                                    'style' => 'color: var(--rose); text-decoration: none;',
                                    'title' => $isMe ? 'Drop out of this line-up' : 'Take off this line-up',
                                    'data' => ['method' => 'post', 'confirm' => $isMe ? 'Drop out of this line-up?' : 'Take ' . ($l->user->username ?? 'them') . ' off this line-up?'],
                                ]) ?>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <?php if ($isOwner && $open && !empty($addable)): ?>
                    <?php if ($atMax): ?>
                        <div style="font-size: 11.5px; color: var(--text-faint);">Line-up is at the competition's maximum.</div>
                    <?php else: ?>
                        <form method="post" action="<?= Url::to(['lineup-add', 'registrationId' => $reg->id, 'userId' => 0]) ?>"
                              onsubmit="this.action = this.action.replace(/userId=\d+/, 'userId=' + this.querySelector('select').value);"
                              style="display: flex; gap: 8px; align-items: center;">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            <select style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 6px 10px; color: var(--text); font-size: 12.5px;">
                                <?php foreach ($addable as $m): ?>
                                    <option value="<?= $m->user_id ?>"><?= Html::encode($m->user->username ?? 'Unknown') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="nav-item" style="display: inline-flex; padding: 6px 14px; font-size: 12px; cursor: pointer;">Add to line-up</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function switchTab(name) {
    if (!document.getElementById('tab-' + name)) return;
    document.querySelectorAll('.tab-panel').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    document.querySelector('.tab-btn[data-tab="' + name + '"]').classList.add('active');
}
// Open the tab named in the URL (e.g. #competitions after changing a line-up).
if (location.hash) { switchTab(location.hash.slice(1)); }
</script>