<?php

namespace app\models;

use Yii;
use yii\base\Model;

class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $password_confirm;

    public function rules()
    {
        return [
            [['username', 'email', 'password', 'password_confirm'], 'required'],
            ['username', 'string', 'min' => 3, 'max' => 255],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'This username is already taken.'],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'An account with this email already exists.'],
            ['email', 'validateUniversityEmail'],
            ['password', 'string', 'min' => 8],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
        ];
    }

    public function validateUniversityEmail(string $attribute): void
    {
        $email = strtolower($this->$attribute);
        $domain = substr(strrchr($email, '@'), 1);
        if (!in_array($domain, [User::DOMAIN_STAFF, User::DOMAIN_STUDENT], true)) {
            $this->addError($attribute, 'You must sign up with a ug.edu.gh or st.ug.edu.gh email address.');
        }
    }

    /**
     * Creates the user (unverified), assigns the RBAC role based on email
     * domain, and sends the verification email. Returns the saved User,
     * or null if anything failed.
     */
    public function signup(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateVerificationToken();
        $user->status = User::STATUS_UNVERIFIED;

        if (!$user->save()) {
            return null;
        }

        // Domain decides the starting role: st.ug.edu.gh -> student.
        // ug.edu.gh (staff) also starts as student — an admin promotes
        // them to moderator manually via the permissions page.
        $auth = Yii::$app->authManager;
        $studentRole = $auth->getRole('student');
        if ($studentRole !== null) {
            $auth->assign($studentRole, $user->id);
        }

        $this->sendVerificationEmail($user);

        return $user;
    }

    private function sendVerificationEmail(User $user): bool
    {
        $verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);

        // Requires a configured 'mailer' component in config/web.php.
        // See config/mailer_config_snippet.php for SMTP setup.
        return Yii::$app->mailer->compose()
            ->setTo($user->email)
            ->setFrom([Yii::$app->params['adminEmail'] ?? 'no-reply@dataforge.world' => 'DataForge'])
            ->setSubject('Verify your DataForge account')
            ->setTextBody("Hi {$user->username},\n\nVerify your account by visiting this link:\n{$verifyLink}\n\nIf you didn't sign up, ignore this email.")
            ->send();
    }
}