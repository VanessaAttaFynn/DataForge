<?php

namespace app\models;

use yii\db\ActiveRecord;
use app\models\TeamCompetitionRegistration;

class Team extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%team}}';
    }

    public function rules()
    {
        return [
            [['name', 'owner_id'], 'required'],
            [['owner_id', 'created_at', 'cap'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['avatar_path'], 'string', 'max' => 255],
        ];
    }

    public function getOwner()
    {
        return $this->hasOne(User::class, ['id' => 'owner_id']);
    }

    public function getMemberships()
    {
        return $this->hasMany(TeamMembership::class, ['team_id' => 'id'])
            ->where(['invite_status' => 'consented']);
    }

    public function getPendingMemberships()
    {
        return $this->hasMany(TeamMembership::class, ['team_id' => 'id'])
            ->where(['invite_status' => 'invited']);
    }

    public function getRegistrations()
    {
        return $this->hasMany(TeamCompetitionRegistration::class, ['team_id' => 'id']);
    }

    public function getConsentedMemberCount(): int
    {
        return (int) $this->getMemberships()->count();
    }

    /** Generic capacity check (against the team's own cap, not any competition's). */
    public function isFull(): bool
    {
        return $this->getConsentedMemberCount() >= $this->cap;
    }

    public function isRegisteredFor(int $competitionId): bool
    {
        return $this->getRegistrations()->where(['competition_id' => $competitionId])->exists();
    }
}