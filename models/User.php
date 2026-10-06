<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string $email
 * @property int $status
 * @property string|null $verification_token
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_UNVERIFIED = 9;   // signed up, hasn't clicked the email link yet
    const STATUS_ACTIVE = 10;
    const STATUS_DELETED = 0;

    // Only these two domains are allowed to register.
    const DOMAIN_STAFF = 'ug.edu.gh';
    const DOMAIN_STUDENT = 'st.ug.edu.gh';

    const STUDENT_VERIFICATION_NONE = 'none';
    const STUDENT_VERIFICATION_PENDING = 'pending';
    const STUDENT_VERIFICATION_APPROVED = 'approved';
    const STUDENT_VERIFICATION_REJECTED = 'rejected';

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['username', 'email', 'password_hash', 'auth_key'], 'required'],
            [['username', 'email'], 'unique'],
            [['username'], 'string', 'min' => 3, 'max' => 255],
            [['email'], 'email'],
            [['email'], 'validateUniversityEmail'],
            [['status'], 'integer'],
            [['student_id'], 'string', 'max' => 50],
            [['proof_document_path'], 'string', 'max' => 255],
            [['student_verification_status'], 'string', 'max' => 20],
            [['student_verification_note'], 'string'],
        ];
    }

    /** Only @ug.edu.gh and @st.ug.edu.gh addresses are allowed to register. */
    public function validateUniversityEmail(string $attribute): void
    {
        $email = strtolower($this->$attribute);
        $domain = substr(strrchr($email, '@'), 1);

        if (!in_array($domain, [self::DOMAIN_STAFF, self::DOMAIN_STUDENT], true)) {
            $this->addError($attribute, 'You must sign up with a ug.edu.gh or st.ug.edu.gh email address.');
        }
    }

    public function isStudentEmail(): bool
    {
        $domain = substr(strrchr(strtolower($this->email), '@'), 1);
        return $domain === self::DOMAIN_STUDENT;
    }

    public function isEmailVerified(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isStudentVerified(): bool
    {
        return $this->student_verification_status === self::STUDENT_VERIFICATION_APPROVED;
    }

    /** A student email account that hasn't passed verification yet — staff accounts are never restricted. */
    public function isRestrictedStudent(): bool
    {
        return $this->isStudentEmail() && !$this->isStudentVerified();
    }

    // ---------- IdentityInterface ----------

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new \yii\base\NotSupportedException('Access-token auth is not implemented.');
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    // ---------- Lookups ----------

    public static function findByUsername(string $username): ?self
    {
        return static::find()
            ->where(['status' => self::STATUS_ACTIVE])
            ->andWhere(['or', ['username' => $username], ['email' => $username]])
            ->one();
    }

    /** Includes unverified accounts — used by the login form to give a clear error. */
    public static function findByUsernameAnyStatus(string $username): ?self
    {
        return static::find()
            ->where(['or', ['username' => $username], ['email' => $username]])
            ->one();
    }

    public static function findByVerificationToken(string $token): ?self
    {
        return static::findOne(['verification_token' => $token, 'status' => self::STATUS_UNVERIFIED]);
    }

    // ---------- Password / tokens ----------

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generateVerificationToken(): void
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /** Token is "<random>_<unix time>" so we can tell when it expires. */
    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }

    public static function isPasswordResetTokenValid(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;
        return $timestamp + $expire >= time();
    }

    public static function findByPasswordResetToken(string $token): ?self
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne(['password_reset_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    // ---------- Role display helper ----------

    public function getRoleNames(): array
    {
        return array_keys(Yii::$app->authManager->getRolesByUser($this->id));
    }

    public function getInitials(): string
    {
        $parts = preg_split('/[\s._-]+/', trim($this->username));
        $parts = array_filter($parts);
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        return strtoupper(mb_substr($this->username, 0, 2));
    }
}