<?php

namespace app\models;

use yii\db\ActiveRecord;

class CompetitionRegistration extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%competition_registration}}';
    }

    public function rules()
    {
        return [
            [['competition_id', 'user_id', 'registered_at'], 'required'],
            [['competition_id', 'user_id', 'registered_at'], 'integer'],
        ];
    }

    public static function isRegistered(int $competitionId, int $userId): bool
    {
        return static::find()->where(['competition_id' => $competitionId, 'user_id' => $userId])->exists();
    }
}