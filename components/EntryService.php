<?php

namespace app\components;

use app\models\CompetitionRegistration;
use app\models\Submission;
use app\models\TeamCompetitionMember;
use app\models\TeamCompetitionRegistration;

/**
 * One place that answers "how is this person entered in this competition?"
 * A person can have at most ONE entry per competition: either as an
 * individual, or on exactly one team's line-up.
 */
class EntryService
{
    /** The team registration whose line-up includes this user for this competition, or null. */
    public static function teamEntryFor(int $competitionId, int $userId, ?int $exceptRegistrationId = null): ?TeamCompetitionRegistration
    {
        $query = TeamCompetitionRegistration::find()
            ->alias('r')
            ->innerJoin(TeamCompetitionMember::tableName() . ' m', 'm.registration_id = r.id')
            ->where(['r.competition_id' => $competitionId, 'm.user_id' => $userId]);
        if ($exceptRegistrationId !== null) {
            $query->andWhere(['!=', 'r.id', $exceptRegistrationId]);
        }
        return $query->one();
    }

    public static function isIndividual(int $competitionId, int $userId): bool
    {
        return CompetitionRegistration::isRegistered($competitionId, $userId);
    }

    /** True if the user already has any entry here (other than the given team registration). */
    public static function hasEntry(int $competitionId, int $userId, ?int $exceptRegistrationId = null): bool
    {
        return self::isIndividual($competitionId, $userId)
            || self::teamEntryFor($competitionId, $userId, $exceptRegistrationId) !== null;
    }

    /** Competition ids (post ids) this user is entered in, as an individual or on a line-up. */
    public static function competitionIdsFor(int $userId): array
    {
        $individual = CompetitionRegistration::find()->select('competition_id')->where(['user_id' => $userId])->column();
        $team = TeamCompetitionRegistration::find()
            ->alias('r')
            ->select('r.competition_id')
            ->innerJoin(TeamCompetitionMember::tableName() . ' m', 'm.registration_id = r.id')
            ->where(['m.user_id' => $userId])
            ->column();
        return array_values(array_unique(array_map('intval', array_merge($individual, $team))));
    }

    public static function individualHasSubmissions(int $competitionId, int $userId): bool
    {
        return Submission::find()
            ->where(['competition_id' => $competitionId, 'user_id' => $userId, 'participant_type' => Submission::PARTICIPANT_INDIVIDUAL])
            ->exists();
    }
}
