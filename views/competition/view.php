<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var bool $canManage */
/** @var bool $canEdit */
/** @var app\models\TeamCompetitionRegistration|null $myTeamEntry */
/** @var bool $canWithdrawIndividual */
/** @var bool $canWithdrawTeam */
/** @var bool $isRegistered */
/** @var app\models\Team|null $myRegisteredTeam */
/** @var array $datasetSummary */
/** @var array $leaderboardTop */
/** @var int|null $submissionsRemainingToday */

use yii\helpers\Html;

$this->title = $post->title;
$hasAnyRegistration = $isRegistered || ($myRegisteredTeam !== null);
$hasDatasetAccess = $hasAnyRegistration || $canManage;
?>
<div class="topbar">
    <div>
        <div class="eyebrow"><?= ucfirst($post->type) ?><?= $post->status !== 'published' ? ' · ' . ucfirst($post->status) : '' ?></div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub"><?= Html::encode($competition->scheduleLabel()) ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <?php if (!Yii::$app->user->isGuest): ?>
            <?= Html::a('Contribute', ['contribute', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php endif; ?>
        <?php if ($canEdit): ?>
            <?= Html::a('Edit', ['update', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <?= Html::a('Delete', ['delete', 'id' => $post->id], [
                'class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px; color: var(--rose);',
                'data' => ['method' => 'post', 'confirm' => 'Delete this competition and everything tied to it? This cannot be undone.'],
            ]) ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($post->status === 'published' && ($hasAnyRegistration || $canManage || (!$hasAnyRegistration && $competition->accepts !== 'individual'))): ?>
<div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <div style="display: flex; gap: 10px;">
        <?php if ($hasAnyRegistration): ?>
            <?php if ($competition->isSubmissionOpen()): ?>
                <button type="button" class="nav-item active" style="display: inline-flex; padding: 9px 20px; border: 1px solid var(--border-strong); cursor: pointer;"
                        onclick="document.getElementById('submit-modal').classList.add('open')">
                    Submit Prediction
                </button>
            <?php elseif (!$competition->hasEnded()): ?>
                <span class="nav-item" style="display: inline-flex; padding: 9px 20px; color: var(--text-faint); cursor: default; border-style: dashed;">
                    Submissions open <?= date('M j, Y', strtotime($competition->registration_deadline)) ?>
                </span>
            <?php endif; ?>
            <?= Html::a('My Submissions', ['my-submissions', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <?= Html::a('All Submissions', ['submissions', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display:inline-flex; padding: 9px 16px;']) ?>
        <?php endif; ?>
    </div>

    <?php if (!$hasAnyRegistration && $competition->accepts !== 'individual' && $competition->isRegistrationOpen()): ?>
        <div style="display: flex; gap: 10px;">
            <?= Html::a('Browse and Join Existing Teams', ['teams', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<?php if (!Yii::$app->user->isGuest && $post->status === 'published'): ?>
<div class="panel" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
    <?php if (!$competition->isRegistrationOpen() && !$hasAnyRegistration): ?>
        <span class="comp-tag" style="color: var(--text-faint);">
            <?= $competition->hasEnded() ? 'Competition ended' : "Registration closed — reopens for submissions" ?>
        </span>
    <?php endif; ?>

    <?php if ($competition->accepts !== 'team'): ?>
        <?php if ($isRegistered): ?>
            <span class="comp-tag" style="background: linear-gradient(90deg, rgba(212,175,106,0.22), rgba(212,175,106,0.06)); border: 1px solid var(--border-strong); color: var(--gold-bright); font-weight: 700;">✓ Registered as Individual</span>
            <?php if ($canWithdrawIndividual): ?>
                <?= Html::a('Withdraw', ['withdraw', 'id' => $post->id], [
                    'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 18px; color: var(--rose);',
                    'data' => ['method' => 'post', 'confirm' => 'Withdraw from this competition? You can register again while registration is open.'],
                ]) ?>
            <?php endif; ?>
        <?php elseif (!$hasAnyRegistration && $competition->isRegistrationOpen()): ?>
            <?= Html::a('Register as Individual', ['register', 'id' => $post->id], [
                'class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;',
                'data' => ['method' => 'post'],
            ]) ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($competition->accepts !== 'individual'): ?>
        <?php if ($myRegisteredTeam !== null): ?>
            <span class="comp-tag" style="background: linear-gradient(90deg, rgba(212,175,106,0.22), rgba(212,175,106,0.06)); border: 1px solid var(--border-strong); color: var(--gold-bright); font-weight: 700;">✓ Registered — Team <?= Html::encode($myRegisteredTeam->name) ?></span>
            <?php if (!$myRegisteredTeam->isArchived()): ?>
                <?= Html::a('Manage Line-up', ['/team/manage', 'id' => $myRegisteredTeam->id, '#' => 'competitions'], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
            <?php endif; ?>
            <?php if ($canWithdrawTeam): ?>
                <?= Html::a('Withdraw Team', ['withdraw-team', 'id' => $post->id, 'registrationId' => $myTeamEntry->id], [
                    'class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 18px; color: var(--rose);',
                    'data' => ['method' => 'post', 'confirm' => "Withdraw team \"{$myRegisteredTeam->name}\" from this competition? The whole line-up comes off."],
                ]) ?>
            <?php endif; ?>
            <div style="width: 100%; font-size: 12px; color: var(--text-dim);">
                Line-up for this competition:
                <?= Html::encode(implode(', ', array_map(fn($m) => $m->user->username ?? '?', $myTeamEntry->lineup))) ?>
                <?php if (!$myTeamEntry->isLineupLocked()): ?>
                    <span style="color: var(--text-faint);">· can change until registration closes or the first submission</span>
                <?php else: ?>
                    <span style="color: var(--text-faint);">· 🔒 locked (<?= Html::encode($myTeamEntry->lockReason()) ?>)</span>
                <?php endif; ?>
            </div>
        <?php elseif (!$hasAnyRegistration && $competition->isRegistrationOpen()): ?>
            <?= Html::a('Register a Team', ['register-team', 'id' => $post->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 20px;']) ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">About</div></div>
            <p style="font-size: 13.5px; color: var(--text-dim); line-height: 1.6;"><?= nl2br(Html::encode($post->body)) ?></p>
        </div>

        <div class="panel">
            <div class="panel-head"><div class="panel-title">Leaderboard</div></div>
            <?php if (!empty($leaderboardTop)): ?>
                <?php $metricLabel = $competition->metric === 'accuracy' ? 'Accuracy (%)' : 'RMSE'; ?>
                <div style="display: flex; justify-content: space-between; padding: 4px 14px 8px;">
                    <span style="font-size: 10.5px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Name</span>
                    <span style="font-size: 10.5px; font-weight: 700; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.05em;">Metric: <?= $metricLabel ?></span>
                </div>
            <?php endif; ?>
            <?php if (empty($leaderboardTop)): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">No submissions yet.</p>
            <?php else: ?>
                <?php foreach ($leaderboardTop as $i => $row): ?>
                    <div class="team-chip" style="justify-content: space-between;">
                        <span><strong style="color: var(--gold-bright); margin-right: 8px;">#<?= $i + 1 ?></strong><?= Html::encode($row->getParticipantName()) ?></span>
                        <strong><?= $row->score ?></strong>
                    </div>
                <?php endforeach; ?>
                <?= Html::a('View Full Leaderboard →', ['leaderboard', 'id' => $post->id], ['style' => 'display: block; margin-top: 12px; font-size: 12.5px; color: var(--gold);']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Details</div></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Metric</span><strong><?= strtoupper($competition->metric) ?></strong></div>
            <?php $acceptsLabels = ['both' => 'Teams & Individuals', 'individual' => 'Individuals only', 'team' => 'Teams only']; ?>
            <div class="team-chip" style="justify-content: space-between;"><span>Accepts</span><strong><?= $acceptsLabels[$competition->accepts] ?? ucfirst($competition->accepts) ?></strong></div>
            <div class="team-chip" style="justify-content: space-between;"><span>Submission cap</span><strong><?= $competition->submission_cap_per_day ?>/day</strong></div>
            <?php if ($competition->accepts !== 'individual' && $competition->teamSizeLabel()): ?>
                <div class="team-chip" style="justify-content: space-between;"><span>Team size</span><strong><?= Html::encode($competition->teamSizeLabel()) ?></strong></div>
            <?php endif; ?>
            <?php if ($competition->reward_type !== 'none'): ?>
                <div class="team-chip" style="justify-content: space-between; background: linear-gradient(90deg, rgba(212,175,106,0.14), rgba(212,175,106,0.03)); border: 1px solid var(--border-strong);">
                    <span>🏆 Reward</span><strong style="color: var(--gold-bright);"><?= Html::encode($competition->reward_details ?: ucfirst($competition->reward_type)) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head"><div class="panel-title">Dataset</div></div>
            <?php if (!$datasetSummary['exists']): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">No public dataset uploaded for this competition yet.</p>
            <?php else: ?>
                <div class="team-chip" style="justify-content: space-between;"><span>Size</span><strong><?= Html::encode($datasetSummary['size']) ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>File type</span><strong><?= Html::encode($datasetSummary['type']) ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Sheets</span><strong><?= $datasetSummary['sheets'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Rows</span><strong><?= $datasetSummary['rows'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Columns</span><strong><?= $datasetSummary['columns'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Target</span><strong><?= Html::encode($datasetSummary['target'] ?? 'Not specified') ?></strong></div>
                <?php if ($datasetSummary['license']): ?>
                    <div class="team-chip" style="justify-content: space-between;"><span>License</span><strong><?= Html::encode($datasetSummary['license']) ?></strong></div>
                <?php endif; ?>

                <?php if ($hasDatasetAccess): ?>
                    <div style="display: flex; gap: 10px; margin-top: 14px;">
                        <?= Html::a('View Dataset', ['dataset', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px; flex: 1; justify-content: center;', 'target' => '_blank']) ?>
                        <?= Html::a('Download', ['download-dataset', 'id' => $post->id], ['class' => 'nav-item active', 'style' => 'display: inline-flex; padding: 9px 16px; flex: 1; justify-content: center;']) ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-faint); font-size: 11.5px; margin-top: 10px;">Register for this competition to view or download the full dataset.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($hasAnyRegistration && $post->status === 'published'): ?>
<!-- Submit-to-competition modal -->
<div id="submit-modal" class="modal-overlay">
    <div class="modal-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <div style="font-family: 'Fraunces', serif; font-size: 17px; font-weight: 600;">Submit to Competition</div>
            <button type="button" onclick="document.getElementById('submit-modal').classList.remove('open')"
                    style="background: none; border: none; color: var(--text-faint); font-size: 20px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
            <?php if ($post->cover_image_path): ?>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: url('<?= Html::encode(\yii\helpers\Url::to(['/site/serve-image', 'path' => $post->cover_image_path])) ?>') center/cover; flex-shrink: 0;"></div>
            <?php else: ?>
                <div class="comp-icon" style="width: 40px; height: 40px;"><?= $post->type === 'hackathon' ? '⚡' : '🏆' ?></div>
            <?php endif; ?>
            <div>
                <div style="font-size: 13.5px; font-weight: 600;"><?= Html::encode($post->title) ?></div>
                <div style="font-size: 11.5px; color: var(--text-faint);">You have <?= $submissionsRemainingToday ?> submission(s) remaining today. Resets at midnight.</div>
            </div>
        </div>

        <form method="post" action="<?= \yii\helpers\Url::to(['submit', 'id' => $post->id]) ?>" enctype="multipart/form-data" id="submit-form">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

            <div id="dropzone" class="dropzone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 30px; height: 30px; color: var(--gold-bright); margin-bottom: 10px;">
                    <path d="M12 16V4M12 4l-4 4M12 4l4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                </svg>
                <div id="dropzone-text" style="font-size: 13.5px; font-weight: 600;">Drag and drop file to upload</div>
                <div style="font-size: 11px; color: var(--text-faint); margin: 4px 0 14px;">(.csv)</div>
                <label style="background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 20px; padding: 8px 20px; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                    Browse Files
                    <input type="file" name="prediction_file" id="prediction-file-input" accept=".csv" required style="display: none;" onchange="handleFileSelect(this)">
                </label>
            </div>

            <div style="font-size: 11px; color: var(--text-faint); margin: 12px 0 18px;">Expected columns: <code>id, target</code></div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="nav-item" style="padding: 9px 18px; cursor: pointer;" onclick="document.getElementById('submit-modal').classList.remove('open')">Cancel</button>
                <button type="submit" id="modal-submit-btn" class="nav-item active" disabled
                        style="padding: 9px 22px; border: 1px solid var(--border-strong); cursor: not-allowed; opacity: 0.4;">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
function handleFileSelect(input) {
    var btn = document.getElementById('modal-submit-btn');
    var text = document.getElementById('dropzone-text');
    if (input.files && input.files.length > 0) {
        text.textContent = input.files[0].name;
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.4';
        btn.style.cursor = 'not-allowed';
    }
}

(function () {
    var zone = document.getElementById('dropzone');
    var input = document.getElementById('prediction-file-input');
    if (!zone) return;

    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.remove('dragover');
        });
    });
    zone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length > 0) {
            input.files = e.dataTransfer.files;
            handleFileSelect(input);
        }
    });
})();
</script>
<?php endif; ?>