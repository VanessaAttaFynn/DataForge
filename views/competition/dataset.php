<?php

/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var array $summary */

use yii\helpers\Html;

$this->title = $post->title . ' — Dataset';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Dataset</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
    </div>
</div>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Data Description</div></div>
            <?php if ($summary['exists']): ?>
                <p style="font-size: 13.5px; color: var(--text-dim); line-height: 1.6;"><?= Html::encode($summary['filename']) ?></p>
                <?php if ($summary['description']): ?>
                    <p style="font-size: 13px; color: var(--text-dim); line-height: 1.6; margin-top: 10px;"><?= nl2br(Html::encode($summary['description'])) ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: var(--text-dim); font-size: 13.5px;">The competition creator hasn't uploaded a public dataset for this one yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Summary</div></div>
            <?php if ($summary['exists']): ?>
                <div class="team-chip" style="justify-content: space-between;"><span>Size</span><strong><?= Html::encode($summary['size']) ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>File type</span><strong><?= Html::encode($summary['type']) ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Sheets</span><strong><?= $summary['sheets'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Rows</span><strong><?= $summary['rows'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Columns</span><strong><?= $summary['columns'] ?? 'Not specified' ?></strong></div>
                <div class="team-chip" style="justify-content: space-between;"><span>Target</span><strong><?= $summary['target'] ?? 'Not specified' ?></strong></div>
                <?php if ($summary['license']): ?>
                    <div class="team-chip" style="justify-content: space-between;"><span>License</span><strong><?= Html::encode($summary['license']) ?></strong></div>
                <?php endif; ?>

                <?= Html::a('⬇ Download Dataset', $competition->dataset_file_path, [
                    'class' => 'nav-item active',
                    'style' => 'display: flex; justify-content: center; padding: 10px 24px; margin-top: 16px;',
                    'download' => true,
                ]) ?>
            <?php else: ?>
                <p style="color: var(--text-faint); font-size: 12.5px;">Nothing to show yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>