<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var bool $isUpdate */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isUpdate = $isUpdate ?? false;

// Once people have registered, these rules are locked (and deadlines can only move later).
$locked = $isUpdate && $competition->hasEntries();
$lockAttr = $locked ? 'disabled' : '';
$lockNote = '<div style="font-size: 11px; color: var(--text-faint); margin-top: 4px;">🔒 Locked — people have already registered.</div>';

// Shared field-wrapper style, used everywhere below.
$labelStyle = "font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;";
$inputStyle = "width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;";
?>
<!-- Milestone progress bar -->
<div style="display: flex; align-items: center; margin-bottom: 26px; max-width: 520px;">
    <?php foreach (['Basics', 'Dataset', 'Rules & Rewards'] as $i => $label): ?>
        <?php $n = $i + 1; ?>
        <div style="display: flex; flex-direction: column; align-items: center; flex: <?= $n < 3 ? '0 0 auto' : '0 0 auto' ?>;">
            <div id="step-dot-<?= $n ?>" class="wizard-dot <?= $n === 1 ? 'active' : '' ?>"
                 style="width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                        font-size: 12px; font-weight: 700; font-family: 'IBM Plex Mono', monospace;
                        background: <?= $n === 1 ? 'var(--gold)' : 'var(--panel-glass-strong)' ?>;
                        color: <?= $n === 1 ? '#14110A' : 'var(--text-faint)' ?>;
                        border: 1px solid var(--border);">
                <?= $n ?>
            </div>
            <div id="step-label-<?= $n ?>" style="font-size: 10.5px; margin-top: 6px; color: <?= $n === 1 ? 'var(--gold-bright)' : 'var(--text-faint)' ?>; white-space: nowrap;"><?= $label ?></div>
        </div>
        <?php if ($n < 3): ?>
            <div id="step-line-<?= $n ?>" style="flex: 1; height: 2px; background: var(--border); margin: 0 8px 18px;"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="panel" style="max-width: 720px;">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data', 'id' => 'competition-form']]); ?>

    <!-- ===================== STEP 1: BASICS ===================== -->
    <div class="wizard-step" data-step="1">
        <div style="margin-bottom: 18px;">
            <label style="<?= $labelStyle ?>">Type</label>
            <select name="Post[type]" style="<?= $inputStyle ?>">
                <option value="competition" <?= $post->type === 'competition' ? 'selected' : '' ?>>Competition</option>
                <option value="hackathon" <?= $post->type === 'hackathon' ? 'selected' : '' ?>>Hackathon</option>
            </select>
        </div>

        <div style="margin-bottom: 18px;">
            <label style="<?= $labelStyle ?>">Title</label>
            <input type="text" name="Post[title]" value="<?= Html::encode($post->title) ?>" required style="<?= $inputStyle ?>">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="<?= $labelStyle ?>">Description</label>
            <textarea name="Post[body]" rows="4" required style="<?= $inputStyle ?> resize: vertical;"><?= Html::encode($post->body) ?></textarea>
        </div>

        <div style="margin-bottom: 0;">
            <label style="<?= $labelStyle ?>">
                Cover image <?= $isUpdate && $post->cover_image_path ? '(already set — choose a file to replace it)' : '(optional — shown on the browse grid)' ?>
            </label>
            <input type="file" name="cover_image" accept="image/*" style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
        </div>
    </div>

    <!-- ===================== STEP 2: DATASET ===================== -->
    <div class="wizard-step" data-step="2" style="display: none;">
        <hr style="border: none; border-top: 1px solid var(--border); margin: 0 0 22px;">

        <div style="margin-bottom: 18px;">
            <label style="<?= $labelStyle ?>">
                Public dataset <?= $isUpdate && $competition->dataset_file_path ? '(already set — choose a file to replace it)' : '(optional — what participants download to work with)' ?>
            </label>
            <input type="file" name="dataset_file" style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="<?= $labelStyle ?>">Dataset description (optional)</label>
            <textarea name="Competition[dataset_description]" rows="3" placeholder="What's in the files, how they're structured…"
                      style="<?= $inputStyle ?> resize: vertical;"><?= Html::encode($competition->dataset_description) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label style="<?= $labelStyle ?>">Target column (optional)</label>
                <input type="text" name="Competition[dataset_target_column]" value="<?= Html::encode($competition->dataset_target_column) ?>" placeholder="e.g. Survived" style="<?= $inputStyle ?>">
            </div>
            <div>
                <label style="<?= $labelStyle ?>">License (optional)</label>
                <input type="text" name="Competition[dataset_license]" value="<?= Html::encode($competition->dataset_license) ?>" placeholder="e.g. CC BY 4.0" style="<?= $inputStyle ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label style="<?= $labelStyle ?>">Rows (optional)</label>
                <input type="number" name="Competition[dataset_rows]" value="<?= Html::encode($competition->dataset_rows) ?>" placeholder="Auto for CSV" style="<?= $inputStyle ?>">
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Columns (optional)</label>
                <input type="number" name="Competition[dataset_columns]" value="<?= Html::encode($competition->dataset_columns) ?>" placeholder="Auto for CSV" style="<?= $inputStyle ?>">
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Sheets (optional)</label>
                <input type="number" name="Competition[dataset_sheets]" value="<?= Html::encode($competition->dataset_sheets) ?>" placeholder="e.g. 1" style="<?= $inputStyle ?>">
            </div>
        </div>
        <div style="font-size: 11px; color: var(--text-faint); margin-top: -10px; margin-bottom: 18px;">Auto-detected for CSV files — fill these in by hand for XLSX, images, or other formats.</div>

        <div style="margin-bottom: 6px;">
            <label style="<?= $labelStyle ?>">
                Hidden answer key (CSV, used to auto-score submissions — never shown to participants)
                <?= $isUpdate && $competition->answer_key_path ? ' — already uploaded, choose a file to replace it' : '' ?>
            </label>
            <input type="file" name="answer_key" accept=".csv" style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
        </div>
        <div style="font-size: 11px; color: var(--text-faint);">Expected columns: <code>id, target</code></div>
    </div>

    <!-- ===================== STEP 3: RULES & REWARDS ===================== -->
    <div class="wizard-step" data-step="3" style="display: none;">
        <hr style="border: none; border-top: 1px solid var(--border); margin: 0 0 22px;">

        <?php if ($locked): ?>
            <div style="font-size: 12.5px; color: var(--text-dim); background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; margin-bottom: 18px;">
                People have already registered, so the scoring metric, who can enter, team sizes and the daily submission cap are locked.
                Deadlines can only be moved later.
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label style="<?= $labelStyle ?>">Scoring metric</label>
                <select name="Competition[metric]" style="<?= $inputStyle ?>" <?= $lockAttr ?>>
                    <option value="accuracy" <?= $competition->metric === 'accuracy' ? 'selected' : '' ?>>Accuracy (higher is better)</option>
                    <option value="rmse" <?= $competition->metric === 'rmse' ? 'selected' : '' ?>>RMSE (lower is better)</option>
                </select>
                <?= $locked ? $lockNote : '' ?>
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Who can enter</label>
                <select name="Competition[accepts]" style="<?= $inputStyle ?>" <?= $lockAttr ?>>
                    <option value="both" <?= $competition->accepts === 'both' ? 'selected' : '' ?>>Individuals & Teams</option>
                    <option value="individual" <?= $competition->accepts === 'individual' ? 'selected' : '' ?>>Individuals only</option>
                    <option value="team" <?= $competition->accepts === 'team' ? 'selected' : '' ?>>Teams only</option>
                </select>
                <?= $locked ? $lockNote : '' ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 6px;">
            <div>
                <label style="<?= $labelStyle ?>">Min team size</label>
                <input type="number" name="Competition[team_size_min]" min="1" max="<?= \app\models\Team::DEFAULT_CAP ?>" value="<?= Html::encode($competition->team_size_min) ?>" placeholder="No minimum" style="<?= $inputStyle ?>" <?= $lockAttr ?>>
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Max team size</label>
                <input type="number" name="Competition[team_size_limit]" min="1" max="<?= \app\models\Team::DEFAULT_CAP ?>" value="<?= Html::encode($competition->team_size_limit) ?>" placeholder="No maximum" style="<?= $inputStyle ?>" <?= $lockAttr ?>>
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Submission cap / day <span style="color: var(--gold); font-weight: 500;">(5–10)</span></label>
                <input type="number" name="Competition[submission_cap_per_day]" min="1" value="<?= Html::encode($competition->submission_cap_per_day ?: 5) ?>" required style="<?= $inputStyle ?>" <?= $lockAttr ?>>
            </div>
        </div>
        <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 18px;">
            Team sizes apply to team entries only (1–<?= \app\models\Team::DEFAULT_CAP ?>, since teams hold at most <?= \app\models\Team::DEFAULT_CAP ?> members). Each team registers with a line-up of its members — only those people count for this competition.
            <?= $locked ? '<br>🔒 Locked — people have already registered.' : '' ?>
        </div>

        <hr style="border: none; border-top: 1px solid var(--border); margin: 22px 0;">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label style="<?= $labelStyle ?>">Reward type</label>
                <select name="Competition[reward_type]" style="<?= $inputStyle ?>">
                    <?php foreach (['none' => 'None', 'cash' => 'Cash', 'prize' => 'Prize', 'certificate' => 'Certificate', 'points' => 'Platform points', 'other' => 'Other'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $competition->reward_type === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Reward details</label>
                <input type="text" name="Competition[reward_details]" value="<?= Html::encode($competition->reward_details) ?>" placeholder="e.g. GHS 2,000 + certificate" style="<?= $inputStyle ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 10px;">
            <div>
                <label style="<?= $labelStyle ?>">Registration deadline (optional)</label>
                <input type="datetime-local" name="Competition[registration_deadline]" value="<?= Html::encode($competition->registration_deadline ? date('Y-m-d\\TH:i', strtotime($competition->registration_deadline)) : '') ?>" style="<?= $inputStyle ?>"
                       <?= $locked && $competition->registration_deadline ? 'min="' . date('Y-m-d\\TH:i', strtotime($competition->registration_deadline)) . '" required' : '' ?>
                       <?= $locked && !$competition->registration_deadline ? 'disabled' : '' ?>>
            </div>
            <div>
                <label style="<?= $labelStyle ?>">Final deadline (optional — blank means no deadline)</label>
                <input type="datetime-local" name="Competition[deadline]" value="<?= Html::encode($competition->deadline ? date('Y-m-d\\TH:i', strtotime($competition->deadline)) : '') ?>" style="<?= $inputStyle ?>"
                       <?= $locked && $competition->deadline ? 'min="' . date('Y-m-d\\TH:i', strtotime($competition->deadline)) . '"' : '' ?>
                       <?= $locked && !$competition->deadline ? 'disabled' : '' ?>>
            </div>
        </div>
        <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 24px;">
            Setting a registration deadline switches to sequential mode: register beforehand, submissions open only once it passes — so the final deadline must be at least 1 day after it. Leave it blank to let people register and submit at any time.
        </div>
    </div>

    <!-- ===================== NAVIGATION ===================== -->
    <div style="display: flex; gap: 10px; margin-top: 8px;">
        <button type="button" id="wizard-back" class="nav-item" style="display: none; padding: 10px 24px; border: 1px solid var(--border); cursor: pointer;" onclick="wizardGo(-1)">Back</button>
        <button type="button" id="wizard-next" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;" onclick="wizardGo(1)">Next</button>
        <button type="submit" id="wizard-submit" class="nav-item active" style="display: none; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;"><?= $isUpdate ? 'Save Changes' : 'Submit for Review' ?></button>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
(function () {
    var current = 1;
    var total = 3;

    window.wizardGo = function (direction) {
        var next = current + direction;

        // Validate required fields in the current step before advancing.
        if (direction > 0) {
            var currentStepEl = document.querySelector('.wizard-step[data-step="' + current + '"]');
            var requiredFields = currentStepEl.querySelectorAll('[required]');
            for (var i = 0; i < requiredFields.length; i++) {
                if (!requiredFields[i].reportValidity()) return;
            }
        }

        if (next < 1 || next > total) return;

        document.querySelector('.wizard-step[data-step="' + current + '"]').style.display = 'none';
        document.querySelector('.wizard-step[data-step="' + next + '"]').style.display = 'block';

        // Update progress dots.
        for (var s = 1; s <= total; s++) {
            var dot = document.getElementById('step-dot-' + s);
            var label = document.getElementById('step-label-' + s);
            if (s <= next) {
                dot.style.background = 'var(--gold)';
                dot.style.color = '#14110A';
                label.style.color = 'var(--gold-bright)';
            } else {
                dot.style.background = 'var(--panel-glass-strong)';
                dot.style.color = 'var(--text-faint)';
                label.style.color = 'var(--text-faint)';
            }
        }

        current = next;

        document.getElementById('wizard-back').style.display = current > 1 ? 'inline-flex' : 'none';
        document.getElementById('wizard-next').style.display = current < total ? 'inline-flex' : 'none';
        document.getElementById('wizard-submit').style.display = current === total ? 'inline-flex' : 'none';
    };
})();
</script>