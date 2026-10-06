<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property int $competition_id  FK to post.id
 * @property int $registered_at
 */
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

    /** The line-up this team entered the competition with. */
    public function getLineup()
    {
        return $this->hasMany(TeamCompetitionMember::class, ['registration_id' => 'id']);
    }

    public function getLineupUserIds(): array
    {
        return array_map('intval', TeamCompetitionMember::find()
            ->select('user_id')->where(['registration_id' => $this->id])->column());
    }

    public function getLineupCount(): int
    {
        return (int) TeamCompetitionMember::find()->where(['registration_id' => $this->id])->count();
    }

    public function hasSubmissions(): bool
    {
        return Submission::find()
            ->where(['competition_id' => $this->competition_id, 'team_id' => $this->team_id])
            ->exists();
    }
}
