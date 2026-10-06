<?php

namespace app\models;

use yii\db\ActiveRecord;

class Team extends ActiveRecord
{
    /** Every team's member cap (fixed for now). Competition team sizes can't exceed it. */
    const DEFAULT_CAP = 10;

    public static function tableName()
    {
        return '{{%team}}';
    }

    public function rules()
    {
        return [
            [['name', 'owner_id'], 'required'],
            [['owner_id', 'created_at', 'cap', 'archived_at'], 'integer'],
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

    /** Invites the owner has sent that haven't been answered yet. */
    public function getPendingMemberships()
    {
        return $this->hasMany(TeamMembership::class, ['team_id' => 'id'])
            ->where(['invite_status' => TeamMembership::INVITE_INVITED]);
    }

    /** People who asked to join and are waiting for the owner. */
    public function getJoinRequests()
    {
        return $this->hasMany(TeamMembership::class, ['team_id' => 'id'])
            ->where(['invite_status' => TeamMembership::INVITE_REQUESTED]);
    }

    /** Last member left — kept for history, read-only, no dashboard. */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isMember(int $userId): bool
    {
        return TeamMembership::find()
            ->where(['team_id' => $this->id, 'user_id' => $userId, 'invite_status' => TeamMembership::INVITE_CONSENTED])
            ->exists();
    }

    public function getRegistrations()
    {
        return $this->hasMany(TeamCompetitionRegistration::class, ['team_id' => 'id']);
    }

    public function getConsentedMemberCount(): int
    {
        return (int) $this->getMemberships()->count();
    }

    /**
     * Frozen roster as of a point in time — used once a competition's
     * registration_deadline has passed, so the team can keep growing
     * for other purposes without changing who counts toward that
     * specific competition. $timestamp === null means "live", no freeze.
     */
    public function consentedMembershipsAsOf(?int $timestamp): array
    {
        $query = $this->getMemberships();
        if ($timestamp !== null) {
            $query->andWhere(['<=', 'responded_at', $timestamp]);
        }
        return $query->all();
    }

    public function consentedMemberCountAsOf(?int $timestamp): int
    {
        return count($this->consentedMembershipsAsOf($timestamp));
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