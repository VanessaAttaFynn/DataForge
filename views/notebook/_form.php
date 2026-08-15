<?php

/** @var app\models\Post $post */
/** @var app\models\Notebook $notebook */
/** @var string $tagsValue */
/** @var bool $isUpdate */
/** @var app\models\Post[] $datasets */
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
        if ($notebook->linked_post_id) {
            $currentAssocType = $notebook->linkedPost->type ?? 'competition';
        }
        ?>
        <div style="display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="topic" onchange="switchAssoc('topic')" <?= $currentAssocType === 'topic' ? 'checked' : '' ?>> Topic
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="dataset" onchange="switchAssoc('dataset')" <?= $currentAssocType === 'dataset' ? 'checked' : '' ?>> Dataset
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="competition" onchange="switchAssoc('competition')" <?= $currentAssocType === 'competition' ? 'checked' : '' ?>> Competition
            </label>
            <label style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--text-dim); cursor: pointer;">
                <input type="radio" name="assoc_type" value="hackathon" onchange="switchAssoc('hackathon')" <?= $currentAssocType === 'hackathon' ? 'checked' : '' ?>> Hackathon
            </label>
        </div>

        <div id="assoc-topic" style="<?= $currentAssocType === 'topic' ? '' : 'display:none;' ?>">
            <input type="text" name="Notebook[topic]" value="<?= Html::encode($notebook->topic) ?>" placeholder="e.g. Agriculture"
                   <?= $currentAssocType === 'topic' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
        </div>

        <div id="assoc-dataset" style="<?= $currentAssocType === 'dataset' ? '' : 'display:none;' ?>">
            <select name="Notebook[linked_post_id]" <?= $currentAssocType === 'dataset' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
                <option value="">Select a dataset…</option>
                <?php foreach ($datasets as $d): ?>
                    <option value="<?= $d->id ?>" <?= (int) $notebook->linked_post_id === $d->id ? 'selected' : '' ?>><?= Html::encode($d->title) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="assoc-competition" style="<?= $currentAssocType === 'competition' ? '' : 'display:none;' ?>">
            <select name="Notebook[linked_post_id]" <?= $currentAssocType === 'competition' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
                <option value="">Select a competition…</option>
                <?php foreach ($competitions as $c): ?>
                    <option value="<?= $c->id ?>" <?= (int) $notebook->linked_post_id === $c->id ? 'selected' : '' ?>><?= Html::encode($c->title) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="assoc-hackathon" style="<?= $currentAssocType === 'hackathon' ? '' : 'display:none;' ?>">
            <select name="Notebook[linked_post_id]" <?= $currentAssocType === 'hackathon' ? '' : 'disabled' ?> style="<?= $inputStyle ?>">
                <option value="">Select a hackathon…</option>
                <?php foreach ($hackathons as $h): ?>
                    <option value="<?= $h->id ?>" <?= (int) $notebook->linked_post_id === $h->id ? 'selected' : '' ?>><?= Html::encode($h->title) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <script>
    function switchAssoc(type) {
        ['topic', 'dataset', 'competition', 'hackathon'].forEach(function (t) {
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

    <div style="margin-bottom: 18px;">
        <label style="<?= $labelStyle ?>">Tags (comma-separated)</label>
        <input type="text" name="tags" value="<?= Html::encode($tagsValue) ?>" placeholder="e.g. regression, NLP" style="<?= $inputStyle ?>">
    </div>

    <div style="margin-bottom: 6px;">
        <label style="<?= $labelStyle ?>">
            Notebook file (.ipynb)
            <?= $isUpdate && $notebook->notebook_file_path ? ' — already uploaded, choose a file to replace it' : '' ?>
        </label>
        <input type="file" name="notebook_file" accept=".ipynb" <?= $isUpdate ? '' : 'required' ?> style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
    </div>
    <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 20px;">Export from Jupyter as .ipynb — code, markdown, and any cached outputs (charts, tables) will render read-only.</div>

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
        <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">Publish Notebook</button>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>
</div>