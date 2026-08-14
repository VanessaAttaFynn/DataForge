<?php

/** @var \yii\web\View $this */
/** @var app\models\Post[] $pending */

use yii\helpers\Html;

$this->title = 'Approvals';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Moderation</div>
        <h1 class="page-title">Pending Approvals</h1>
        <div class="page-sub">Competitions and hackathons waiting for review before they go live.</div>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;">
        <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
    </div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;">
        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div class="panel-title">Queue (<?= count($pending) ?>)</div>
    </div>

    <?php if (empty($pending)): ?>
        <p style="color: var(--text-dim); font-size: 13.5px;">Nothing waiting on review right now.</p>
    <?php else: ?>
        <?php foreach ($pending as $post): ?>
            <div class="comp-item" style="align-items: flex-start;">
                <div class="comp-icon"><?= $post->type === 'hackathon' ? '⚡' : '🏆' ?></div>
                <div style="flex: 1;">
                    <div class="comp-name"><?= Html::encode($post->title) ?></div>
                    <div class="comp-meta">
                        <?= Html::encode(ucfirst($post->type)) ?>
                        · submitted by <?= Html::encode($post->author->username ?? 'Unknown') ?>
                        · <?= Yii::$app->formatter->asRelativeTime($post->created_at) ?>
                    </div>

                    <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
                        <?= Html::a('Approve', ['approve', 'id' => $post->id], [
                            'class' => 'nav-item active',
                            'style' => 'display: inline-flex; padding: 7px 16px;',
                            'data' => [
                                'method' => 'post',
                                'confirm' => "Approve \"{$post->title}\"? It will go live immediately.",
                            ],
                        ]) ?>

                        <form method="post" action="<?= \yii\helpers\Url::to(['reject', 'id' => $post->id]) ?>" style="display: flex; gap: 8px; align-items: center; flex: 1;">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            <input type="text" name="note" placeholder="Reason for rejection…" required
                                   style="flex: 1; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px; color: var(--text); font-size: 12.5px; font-family: 'Inter', sans-serif;">
                            <button type="submit" style="background: transparent; border: 1px solid var(--rose); color: var(--rose); border-radius: 8px; padding: 7px 14px; font-size: 12.5px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif;">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
                <div class="comp-tag tag-new">Pending</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>