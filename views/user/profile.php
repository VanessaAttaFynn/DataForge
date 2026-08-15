<?php

/** @var app\models\User $user */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Profile';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">My Space</div>
        <h1 class="page-title">Profile</h1>
    </div>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="panel" style="border-color: var(--emerald); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="panel" style="border-color: var(--rose); margin-bottom: 20px;"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Account</div></div>
            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                <div class="avatar" style="width: 52px; height: 52px; font-size: 16px; margin-left: 0;"><?= Html::encode($user->initials) ?></div>
                <div>
                    <div style="font-family: 'Fraunces', serif; font-size: 18px; font-weight: 600;"><?= Html::encode($user->username) ?></div>
                    <div style="font-size: 12.5px; color: var(--text-faint);"><?= Html::encode($user->email) ?></div>
                </div>
            </div>

            <div class="team-chip" style="justify-content: space-between;">
                <span>Type</span>
                <strong><?= $user->isStudentEmail() ? 'Student' : 'Staff' ?></strong>
            </div>
            <div class="team-chip" style="justify-content: space-between;">
                <span>Email</span>
                <strong style="color: <?= $user->isEmailVerified() ? 'var(--emerald)' : 'var(--text-faint)' ?>;">
                    <?= $user->isEmailVerified() ? '✓ Verified' : '◌ Unverified' ?>
                </strong>
            </div>
            <?php if ($user->isStudentEmail()): ?>
                <div class="team-chip" style="justify-content: space-between;">
                    <span>Student Status</span>
                    <strong style="color: <?= $user->isStudentVerified() ? 'var(--emerald)' : 'var(--text-faint)' ?>;">
                        <?php if ($user->isStudentVerified()): ?>
                            ✓ Verified Student
                        <?php elseif ($user->student_verification_status === 'pending'): ?>
                            ⏳ Pending Review
                        <?php elseif ($user->student_verification_status === 'rejected'): ?>
                            ✗ Rejected
                        <?php else: ?>
                            ◌ Not Verified
                        <?php endif; ?>
                    </strong>
                </div>
                <?php if ($user->isStudentVerified()): ?>
                    <div class="team-chip" style="justify-content: space-between;">
                        <span>Student ID</span>
                        <strong><?= Html::encode($user->student_id) ?></strong>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <?php if ($user->isStudentEmail() && !$user->isStudentVerified()): ?>
        <div class="panel">
            <div class="panel-head"><div class="panel-title">Student Verification</div></div>

            <?php if ($user->student_verification_status === 'pending'): ?>
                <p style="color: var(--text-dim); font-size: 13px; line-height: 1.6;">Your submission is awaiting review — you'll be notified once it's checked.</p>
                <div class="team-chip" style="justify-content: space-between; margin-top: 12px;">
                    <span>Student ID</span><strong><?= Html::encode($user->student_id) ?></strong>
                </div>
            <?php else: ?>
                <?php if ($user->student_verification_status === 'rejected' && $user->student_verification_note): ?>
                    <div style="background: rgba(184,97,90,0.12); border: 1px solid var(--rose); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 12.5px; color: var(--rose);">
                        Previously rejected: <?= Html::encode($user->student_verification_note) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Url::to(['submit-student-verification']) ?>" enctype="multipart/form-data">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                    <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Student ID</label>
                    <input type="text" name="student_id" required
                           style="width: 100%; background: var(--panel-glass-strong); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; color: var(--text); font-family: 'Inter', sans-serif; margin-bottom: 16px;">

                    <label style="font-size: 12.5px; font-weight: 600; color: var(--text-dim); display: block; margin-bottom: 6px;">Proof of Registration</label>
                    <input type="file" name="proof_document" required style="width: 100%; color: var(--text-dim); font-size: 12.5px; margin-bottom: 8px;">
                    <div style="font-size: 11px; color: var(--text-faint); margin-bottom: 18px;">
                        Get this from MIS → Registration → Proof of Registration.
                    </div>

                    <button type="submit" class="nav-item active" style="display: inline-flex; padding: 10px 24px; border: 1px solid var(--border-strong); cursor: pointer;">
                        Submit for Review
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>