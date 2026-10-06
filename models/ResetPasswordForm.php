<?php

namespace app\models;

use yii\base\Model;

/** Page opened from the emailed link — sets the new password. */
class ResetPasswordForm extends Model
{
    public $password;
    public $password_confirm;

    private ?User $_user;

    public function __construct(string $token, array $config = [])
    {
        $this->_user = User::findByPasswordResetToken($token);
        parent::__construct($config);
    }

    public function isTokenValid(): bool
    {
        return $this->_user !== null;
    }

    public function rules()
    {
        return [
            [['password', 'password_confirm'], 'required'],
            ['password', 'string', 'min' => 8],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
        ];
    }

    public function resetPassword(): bool
    {
        if (!$this->validate() || $this->_user === null) {
            return false;
        }

        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();
        $user->generateAuthKey(); // logs out "remember me" sessions that used the old password
        return $user->save(false);
    }
}
