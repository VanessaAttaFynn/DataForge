<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var app\models\Team[] $ownedTeams */

use app\components\EntryService;
use yii\helpers\Html;

$this->title = 'Register Team';
$sizeLabel = $competition->teamSizeLabel();
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Register a Team</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">
            <?= $sizeLabel ? 'Team size for this competition: ' . Html::encode($sizeLabel) . '. ' : '' ?>
            Pick who's on the line-up — only they count for this competition.
        </div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="panel" style="max-width: 560px;">
    <?php if (empty($ownedTeams)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px; margin-bottom: 14px;">You don't own any teams yet.</p>
        <?= Html::a('+ Create a Team', ['/team/create'], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
    <?php else: ?>
        <form method="post" id="register-team-form">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 8px;">Which team?</label>

            <?php foreach ($ownedTeams as $team): ?>
                <?php $alreadyRegistered = $team->isRegisteredFor($post->id); ?>
                <div class="team-chip" style="flex-direction: column; align-items: stretch; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: <?= $alreadyRegistered ? 'default' : 'pointer' ?>;">
                        <input type="radio" name="team_id" value="<?= $team->id ?>" required <?= $alreadyRegistered ? 'disabled' : '' ?>
                               onchange="showLineup(<?= $team->id ?>)">
                        <span style="font-weight: 600;"><?= Html::encode($team->name) ?></span>
                        <span style="font-size: 11.5px; color: var(--text-faint);">
                            <?= $team->getConsentedMemberCount() ?> member(s)<?= $alreadyRegistered ? ' · already registered' : '' ?>
                        </span>
                    </label>

                    <?php if (!$alreadyRegistered): ?>
                        <div class="lineup" id="lineup-<?= $team->id ?>" style="display: none; padding: 4px 0 2px 26px;">
                            <div style="font-size: 11.5px; color: var(--text-faint); margin-bottom: 6px;">Line-up (tick who's competing):</div>
                            <?php foreach ($team->memberships as $m): ?>
                                <?php
                                $user = $m->user;
                                $taken = EntryService::hasEntry($post->id, (int) $m->user_id);
                                $unverified = $user !== null && $user->isRestrictedStudent();
                                $blocked = $taken || $unverified;
                                ?>
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 3px 0; color: <?= $blocked ? 'var(--text-faint)' : 'var(--text)' ?>;">
                                    <input type="checkbox" class="lineup-box" data-team="<?= $team->id ?>" name="members[<?= $team->id ?>][]" value="<?= $m->user_id ?>"
                                        <?= $blocked ? 'disabled' : 'checked' ?> onchange="countLineup(<?= $team->id ?>)">
                                    <?= Html::encode($user->username ?? 'Unknown') ?>
                                    <?php if ($taken): ?><span style="font-size: 11px;">— already entered in this competition</span><?php endif; ?>
                                    <?php if ($unverified): ?><span style="font-size: 11px;">— student ID not verified</span><?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                            <div id="lineup-count-<?= $team->id ?>" style="font-size: 11.5px; margin-top: 6px;"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; margin-top: 14px; border: 1px solid var(--border-strong); cursor: pointer;">
                Register
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
var sizeMin = <?= $competition->team_size_min !== null ? (int) $competition->team_size_min : 'null' ?>;
var sizeMax = <?= $competition->team_size_limit !== null ? (int) $competition->team_size_limit : 'null' ?>;

function showLineup(teamId) {
    document.querySelectorAll('.lineup').forEach(function (el) { el.style.display = 'none'; });
    var box = document.getElementById('lineup-' + teamId);
    if (box) box.style.display = 'block';
    countLineup(teamId);
}

function countLineup(teamId) {
    var n = document.querySelectorAll('.lineup-box[data-team="' + teamId + '"]:checked').length;
    var el = document.getElementById('lineup-count-' + teamId);
    if (!el) return;
    var ok = (sizeMin === null || n >= sizeMin) && (sizeMax === null || n <= sizeMax) && n > 0;
    el.textContent = n + ' selected' + (ok ? '' : (sizeMin !== null && n < sizeMin ? ' — needs at least ' + sizeMin : (sizeMax !== null && n > sizeMax ? ' — at most ' + sizeMax + ' allowed' : ' — pick at least one')));
    el.style.color = ok ? 'var(--emerald)' : 'var(--rose)';
}
</script>
