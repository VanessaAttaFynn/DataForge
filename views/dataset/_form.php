<?php

/** @var app\models\Post $post */
/** @var app\models\Dataset $dataset */
/** @var bool $isUpdate */
/** @var app\models\Post[] $competitions */
/** @var app\models\Post[] $hackathons */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isUpdate = $isUpdate ?? false;
$labelStyle = "font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;";
$inputStyle = "width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;";
?>
<div class="panel" style="max-width: 640px;">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div style="margin-bottom: 18px;">
        <label style="<?= $labelStyle ?>">Title</label>
        <input type="text" name="Post[title]" value="<?= Html::encode($post->title) ?>" required style="<?= $inputStyle ?>">
    </div>

    <div style="margin-bottom: 18px;">
        <label style="<?= $labelStyle ?>">Description</label>
        <textarea name="Post[body]" rows="3" style="<?= $inputStyle ?> resize: vertical;"><?= Html::encode($post->body) ?></textarea>
    </div>

    <div style="margin-bottom: 18px;">
        <label style="<?= $labelStyle ?>">Associated with</label>
        <?php
        $currentAssocType = 'topic';
        if ($dataset->linked_post_id) {
            $linkedType = $dataset->linkedPost->type ?? 'competition';
            $currentAssocType = $linkedType;
        }
        ?>
        <div style="display: flex; gap: 16px; margin-bottom: 12px;">
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="topic" onchange="switchAssoc('topic')" <?= $currentAssocType === 'topic' ? 'checked' : '' ?>> Topic
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="competition" onchange="switchAssoc('competition')" <?= $currentAssocType === 'competition' ? 'checked' : '' ?>> Competition
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="hackathon" onchange="switchAssoc('hackathon')" <?= $currentAssocType === 'hackathon' ? 'checked' : '' ?>> Hackathon
            </label>
        </div>

        <div id="assoc-topic" style="<?= $currentAssocType === 'topic' ? '' : 'display:none;' ?>">
            <input type="text" name="Dataset[topic]" value="<?= Html::encode($dataset->topic) ?>" placeholder="e.g. Agriculture"
                   <?= $currentAssocType === 'topic' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
        </div>

        <div id="assoc-competition" style="<?= $currentAssocType === 'competition' ? '' : 'display:none;' ?>">
            <select name="Dataset[linked_post_id]" <?= $currentAssocType === 'competition' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
                <option value="">Select a competition…</option>
                <?php foreach ($competitions as $c): ?>
                    <option value="<?= $c->id ?>" <?= (int) $dataset->linked_post_id === $c->id ? 'selected' : '' ?>><?= Html::encode($c->title) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="assoc-hackathon" style="<?= $currentAssocType === 'hackathon' ? '' : 'display:none;' ?>">
            <select name="Dataset[linked_post_id]" <?= $currentAssocType === 'hackathon' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
                <option value="">Select a hackathon…</option>
                <?php foreach ($hackathons as $h): ?>
                    <option value="<?= $h->id ?>" <?= (int) $dataset->linked_post_id === $h->id ? 'selected' : '' ?>><?= Html::encode($h->title) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <script>
    function switchAssoc(type) {
        ['topic', 'competition', 'hackathon'].forEach(function (t) {
            var el = document.getElementById('assoc-' + t);
            var input = el.querySelector('input, select');
            if (t === type) {
                el.style.display = '';
                if (input) input.disabled = false;
            } else {
                el.style.display = 'none';
                if (input) input.disabled = true;
            }
        });
    }
    </script>

    <div style="margin-bottom: 6px;">
        <label style="<?= $labelStyle ?>">
            Dataset file <?= $isUpdate && $dataset->file_path ? '(already set — choose a file to replace it)' : '' ?>
        </label>
        <input type="file" name="dataset_file" <?= $isUpdate ? '' : 'required' ?> style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
    </div>
    <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 18px;">CSV gets a live preview once published. Other formats show only the summary below — raw binary content is never displayed.</div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
        <div>
            <label style="<?= $labelStyle ?>">Target column (optional)</label>
            <input type="text" name="Dataset[target_column]" value="<?= Html::encode($dataset->target_column) ?>" placeholder="e.g. Survived" style="<?= $inputStyle ?>">
        </div>
        <div>
            <label style="<?= $labelStyle ?>">License (optional)</label>
            <input type="text" name="Dataset[license]" value="<?= Html::encode($dataset->license) ?>" placeholder="e.g. CC BY 4.0" style="<?= $inputStyle ?>">
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <label style="<?= $labelStyle ?>">Summary statistics (optional)</label>
        <textarea name="Dataset[summary_stats]" rows="3" placeholder="Mean, std dev, class balance, whatever's worth sharing — shown as-is for file types we can't preview."
                  style="<?= $inputStyle ?> resize: vertical;"><?= Html::encode($dataset->summary_stats) ?></textarea>
    </div>

    <?php if ($isUpdate): ?>
        <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">Save Changes</button>
    <?php else: ?>
        <div style="display: flex; gap: 16px; margin-bottom: 18px; align-items: center;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="submit_for_review" value="0" checked> Publish now (unverified)
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="submit_for_review" value="1"> Submit for review (becomes verified once approved)
            </label>
        </div>
        <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">Publish Dataset</button>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>
</div>