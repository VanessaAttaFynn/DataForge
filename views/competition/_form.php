<?php

/** @var \yii\web\View $this */
/** @var app\models\Post $post */
/** @var app\models\Competition $competition */
/** @var bool $isUpdate */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isUpdate = $isUpdate ?? false;
?>
<div class="panel" style="max-width: 720px;">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div style="margin-bottom: 18px;">
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Type</label>
        <select name="Post[type]" style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
            <option value="competition" <?= $post->type === 'competition' ? 'selected' : '' ?>>Competition</option>
            <option value="hackathon" <?= $post->type === 'hackathon' ? 'selected' : '' ?>>Hackathon</option>
        </select>
    </div>

    <div style="margin-bottom: 18px;">
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Title</label>
        <input type="text" name="Post[title]" value="<?= Html::encode($post->title) ?>" required
               style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
    </div>

    <div style="margin-bottom: 18px;">
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Description</label>
        <textarea name="Post[body]" rows="4" required
                  style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif; resize: vertical;"><?= Html::encode($post->body) ?></textarea>
    </div>

    <div style="margin-bottom: 18px;">
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">
            Cover image <?= $isUpdate && $post->cover_image_path ? '(already set — choose a file to replace it)' : '(optional — shown on the browse grid)' ?>
        </label>
        <input type="file" name="cover_image" accept="image/*"
               style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
    </div>

    <hr style="border: none; border-top: 1px solid var(--border); margin: 22px 0;">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Scoring metric</label>
            <select name="Competition[metric]" style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <option value="accuracy" <?= $competition->metric === 'accuracy' ? 'selected' : '' ?>>Accuracy (higher is better)</option>
                <option value="rmse" <?= $competition->metric === 'rmse' ? 'selected' : '' ?>>RMSE (lower is better)</option>
            </select>
        </div>
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Who can enter</label>
            <select name="Competition[accepts]" style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <option value="both" <?= $competition->accepts === 'both' ? 'selected' : '' ?>>Individuals & Teams</option>
                <option value="individual" <?= $competition->accepts === 'individual' ? 'selected' : '' ?>>Individuals only</option>
                <option value="team" <?= $competition->accepts === 'team' ? 'selected' : '' ?>>Teams only</option>
            </select>
        </div>
    </div>

    <div style="margin-bottom: 6px;">
        <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">
            Hidden answer key (CSV, used to auto-score submissions)
            <?= $isUpdate && $competition->answer_key_path ? ' — already uploaded, choose a file to replace it' : '' ?>
        </label>
        <input type="file" name="answer_key" accept=".csv"
               style="width: 100%; color: var(--text-dim); font-size: 12.5px;">
    </div>
    <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 18px;">Expected columns: <code>id, target</code></div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Team size limit</label>
            <input type="number" name="Competition[team_size_limit]" min="1" value="<?= Html::encode($competition->team_size_limit) ?>" placeholder="No limit if left blank"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
        </div>
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">
                Submission cap / day
                <span style="color: var(--gold); font-weight: 500;">(recommended: 5–10)</span>
            </label>
            <input type="number" name="Competition[submission_cap_per_day]" min="1" value="<?= Html::encode($competition->submission_cap_per_day ?: 5) ?>" required
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--border); margin: 22px 0;">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Reward type</label>
            <select name="Competition[reward_type]" style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
                <?php foreach (['none' => 'None', 'cash' => 'Cash', 'prize' => 'Prize', 'certificate' => 'Certificate', 'points' => 'Platform points', 'other' => 'Other'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $competition->reward_type === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Reward details</label>
            <input type="text" name="Competition[reward_details]" value="<?= Html::encode($competition->reward_details) ?>" placeholder="e.g. GHS 2,000 + certificate"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Registration deadline (optional)</label>
            <input type="datetime-local" name="Competition[registration_deadline]" value="<?= Html::encode($competition->registration_deadline) ?>"
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
        </div>
        <div>
            <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Submission deadline</label>
            <input type="datetime-local" name="Competition[deadline]" value="<?= Html::encode($competition->deadline) ?>" required
                   style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif;">
        </div>
    </div>

    <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">
        <?= $isUpdate ? 'Save Changes' : 'Submit for Review' ?>
    </button>

    <?php ActiveForm::end(); ?>
</div>