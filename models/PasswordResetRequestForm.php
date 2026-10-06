<?php

namespace app\models;

use Yii;
use yii\base\Model;

/** "Forgot password?" form — takes an email and sends a reset link. */
class PasswordResetRequestForm extends Model
{
    public $email;

    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
        ];
    }

    /**
     * Sends the reset link if an active account has this email.
     * Returns true/false, but the controller shows the same message either way.
     */
    public function sendEmail(): bool
    {
        $user = User::findOne(['email' => $this->email, 'status' => User::STATUS_ACTIVE]);
        if ($user === null) {
            return false;
        }

        // Reuse a still-valid token so double-clicking doesn't invalidate the first email.
        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save(false)) {
                return false;
            }
        }

        $resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);

        try {
            return Yii::$app->mailer->compose()
                ->setTo($user->email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject('Reset your DataForge password')
                ->setTextBody(
                    "Hi {$user->username},\n\n"
                    . "Someone asked to reset the password for your DataForge account.\n"
                    . "To create a new password, open this link (it works for 5 hours):\n{$resetLink}\n\n"
                    . "If you didn't ask for this, ignore this email — your password won't change."
                )
                ->send();
        } catch (\Throwable $e) {
            Yii::error('Password reset email failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
