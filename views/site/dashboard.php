<?php

/** @var int $competitionsJoinedCount */
/** @var int $datasetsPublishedCount */
/** @var int $notebooksPublishedCount */
/** @var int $teamsCount */
/** @var app\models\Post[] $activeCompetitions */
/** @var app\models\Post[] $trendingDatasets */
/** @var array $datasetVotes */
/** @var app\models\Notification[] $recentNotifications */
/** @var app\models\Team[] $myTeams */
/** @var app\models\TeamMembership[] $myPendingInvites */

use yii\helpers\Html;

$this->title = 'Dashboard';
$user = Yii::$app->user->identity;
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Welcome back</div>
        <h1 class="page-title"><?= Html::encode($user->username) ?></h1>
        <div class="page-sub">Here's what's happening across DataForge.</div>
    </div>
</div>

<div class="stat-row" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 22px;"><?= $competitionsJoinedCount ?></div><div class="stat-label">Competitions Joined</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 22px;"><?= $datasetsPublishedCount ?></div><div class="stat-label">Datasets Published</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 22px;"><?= $notebooksPublishedCount ?></div><div class="stat-label">Notebooks Published</div></div>
    </div>
    <div class="stat-card" style="justify-content: center; text-align: center;">
        <div><div class="stat-num" style="font-size: 22px;"><?= $teamsCount ?></div><div class="stat-label">Teams</div></div>
    </div>
</div>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">Active Competitions & Hackathons</div>
                <?= Html::a('View all →', ['/competition/mine'], ['class' => 'panel-link']) ?>
            </div>

            <?php if (empty($activeCompetitions)): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">You haven't joined any active competitions yet.</p>
            <?php else: ?>
                <?php foreach ($activeCompetitions as $post): ?>
                    <?= Html::a(
                        Html::tag('div',
                            '<div class="comp-icon">' . ($post->type === 'hackathon' ? '⚡' : '🏆') . '</div>'
                            . '<div><div class="comp-name">' . Html::encode($post->title) . '</div>'
                            . '<div class="comp-meta">' . strtoupper($post->type) . '</div></div>'
                            . '<div class="comp-tag tag-live">' . Html::encode($post->competition->phaseLabel()) . '</div>',
                            ['class' => 'comp-item']
                        ),
                        ['/competition/view', 'id' => $post->id],
                        ['style' => 'text-decoration: none; display: block;']
                    ) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">Trending Datasets</div>
                <?= Html::a('Browse all →', ['/dataset/index'], ['class' => 'panel-link']) ?>
            </div>
            <?php if (empty($trendingDatasets)): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">No datasets published yet.</p>
            <?php else: ?>
                <div class="dataset-row">
                    <?php foreach ($trendingDatasets as $post): ?>
                        <?= Html::a(
                            Html::tag('div',
                                '<div class="dataset-verified"' . ($post->verified ? '' : ' style="color: var(--text-faint);"') . '>'
                                . ($post->verified ? '✓ Verified' : '◌ Unverified') . '</div>'
                                . '<div class="dataset-title">' . Html::encode($post->title) . '</div>'
                                . '<div class="dataset-meta">▲ ' . ($datasetVotes[$post->id] ?? 0) . ' votes</div>',
                                ['class' => 'dataset-card']
                            ),
                            ['/dataset/view', 'id' => $post->id],
                            ['style' => 'text-decoration: none;']
                        ) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Recent Activity</div></div>
            <?php if (empty($recentNotifications)): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">Nothing yet.</p>
            <?php else: ?>
                <?php foreach ($recentNotifications as $n): ?>
                    <div class="feed-item">
                        <div class="feed-dot" style="<?= !$n->is_read ? '' : 'background: var(--text-faint);' ?>"></div>
                        <div>
                            <div class="feed-text"><?= Html::encode($n->message) ?></div>
                            <div class="feed-time"><?= strtoupper(Yii::$app->formatter->asRelativeTime($n->created_at)) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">My Teams</div>
                <?= Html::a('Manage →', ['/team/my'], ['class' => 'panel-link']) ?>
            </div>
            <?php if (empty($myTeams) && empty($myPendingInvites)): ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">You're not on any teams yet.</p>
            <?php endif; ?>
            <?php foreach ($myTeams as $team): ?>
                <div class="team-chip">
                    <div class="avatar" style="margin-left: 0;"><?= Html::encode(mb_substr($team->name, 0, 2)) ?></div>
                    <div class="team-info">
                        <div class="team-name"><?= Html::encode($team->name) ?></div>
                        <div class="team-meta"><?= $team->getConsentedMemberCount() ?>/<?= $team->cap ?> members</div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach ($myPendingInvites as $inv): ?>
                <div class="team-chip">
                    <div class="avatar" style="margin-left: 0;"><?= Html::encode(mb_substr($inv->team->name, 0, 2)) ?></div>
                    <div class="team-info">
                        <div class="team-name"><?= Html::encode($inv->team->name) ?> (invited)</div>
                        <div class="team-meta">Awaiting your consent</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php $this->registerJsFile('@web/js/tour.js?v=' . (@filemtime(Yii::getAlias('@webroot/js/tour.js')) ?: time()), ['position' => \yii\web\View::POS_END]); ?>