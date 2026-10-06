<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * invite_status:
 *  - invited   : the OWNER invited this person — only the invitee can accept/decline
 *  - requested : this person ASKED to join — only the owner can approve/decline
 *  - consented : member
 *  - declined  : invite/request turned down or cancelled
 *  - left      : left the team, or was removed by the owner
 */
class TeamMembership extends ActiveRecord
{
    const INVITE_INVITED = 'invited';
    const INVITE_REQUESTED = 'requested';
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

    /** Still waiting on someone: an invite or a join request. */
    public function isOpen(): bool
    {
        return in_array($this->invite_status, [self::INVITE_INVITED, self::INVITE_REQUESTED], true);
    }
}
