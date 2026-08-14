<?php

namespace app\models;

use yii\db\ActiveRecord;

class TeamCompetitionRegistration extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%team_competition_registration}}';
    }

    public function rules()
    {
        return [
            [['team_id', 'competition_id', 'registered_at'], 'required'],
            [['team_id', 'competition_id', 'registered_at'], 'integer'],
        ];
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    public function getCompetitionPost()
    {
        return $this->hasOne(Post::class, ['id' => 'competition_id']);
    }
}