<?php

namespace app\components;

use app\models\Competition;
use app\models\Submission;

class LeaderboardService
{
    /** Best score per participant (individual or team), sorted correctly for the competition's metric. */
    public static function build(int $competitionId, Competition $competition, ?int $limit = null): array
    {
        $submissions = Submission::find()->where(['competition_id' => $competitionId])->all();

        $best = []; // key: "individual:userId" or "team:teamId" => Submission
        foreach ($submissions as $s) {
            $key = $s->participant_type . ':' . ($s->participant_type === 'team' ? $s->team_id : $s->user_id);
            if (!isset($best[$key])) {
                $best[$key] = $s;
                continue;
            }
            $better = $competition->metric === 'rmse' ? $s->score < $best[$key]->score : $s->score > $best[$key]->score;
            if ($better || ($s->score == $best[$key]->score && $s->submitted_at < $best[$key]->submitted_at)) {
                $best[$key] = $s;
            }
        }

        $rows = array_values($best);
        usort($rows, function ($a, $b) use ($competition) {
            if ($a->score == $b->score) return $a->submitted_at <=> $b->submitted_at; // earlier submission wins ties
            return $competition->metric === 'rmse' ? $a->score <=> $b->score : $b->score <=> $a->score;
        });

        return $limit !== null ? array_slice($rows, 0, $limit) : $rows;
    }

    /** 1-indexed rank for a specific individual (userId) or team (teamId) — null if they haven't submitted. */
    public static function rankFor(int $competitionId, Competition $competition, string $participantType, int $participantId): ?int
    {
        $rows = self::build($competitionId, $competition);

        foreach ($rows as $i => $row) {
            $rowId = $row->participant_type === 'team' ? $row->team_id : $row->user_id;
            if ($row->participant_type === $participantType && (int) $rowId === $participantId) {
                return $i + 1;
            }
        }

        return null;
    }
}