<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * One person on a team's line-up for one competition.
 * Only people listed here count for that competition — joining the team
 * later does not add you to line-ups it has already entered.
 *
 * @property int $id
 * @property int $registration_id
 * @property int $user_id
 * @property int $added_at
 */
class TeamCompetitionMember extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%team_competition_member}}';
    }

    public function rules()
    {
        return [
            [['registration_id', 'user_id', 'added_at'], 'required'],
            [['registration_id', 'user_id', 'added_at'], 'integer'],
        ];
    }

    public function getRegistration()
    {
        return $this->hasOne(TeamCompetitionRegistration::class, ['id' => 'registration_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
