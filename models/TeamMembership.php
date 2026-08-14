<?php

namespace app\models;

use yii\db\ActiveRecord;

class TeamMembership extends ActiveRecord
{
    const INVITE_INVITED = 'invited';
    const INVITE_CONSENTED = 'consented';
    const INVITE_DECLINED = 'declined';
    const INVITE_LEFT = 'left';

    public static function tableName()
    {
        return '{{%team_membership}}';
    }

    public function rules()
    {
        return [
            [['team_id', 'user_id', 'invite_status', 'invited_at'], 'required'],
            [['team_id', 'user_id', 'invited_at', 'responded_at'], 'integer'],
            [['invite_status'], 'string', 'max' => 20],
            [['conflict_status'], 'string', 'max' => 20],
            [['note'], 'string'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }
}