<?php

namespace app\models;

use yii\db\ActiveRecord;

class Submission extends ActiveRecord
{
    const PARTICIPANT_INDIVIDUAL = 'individual';
    const PARTICIPANT_TEAM = 'team';

    public static function tableName()
    {
        return '{{%submission}}';
    }

    public function rules()
    {
        return [
            [['competition_id', 'participant_type', 'file_path', 'submitted_at'], 'required'],
            [['competition_id', 'user_id', 'team_id', 'submitted_at'], 'integer'],
            [['participant_type'], 'string', 'max' => 20],
            [['file_path'], 'string', 'max' => 255],
            [['score'], 'number'],
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

    public function getParticipantName(): string
    {
        if ($this->participant_type === self::PARTICIPANT_TEAM) {
            return $this->team->name ?? 'Unknown team';
        }
        return $this->user->username ?? 'Unknown';
    }
}