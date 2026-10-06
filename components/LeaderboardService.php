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

    /**
     * Final placing for team stats, Kaggle-style (scaled by how many entries submitted):
     *  - won:   1st place, and at least 2 entries submitted (winning alone doesn't count)
     *  - top10: inside the top 10% of entries that submitted (at least 1 place), same 2-entry minimum
     * Entries = everyone on the leaderboard (teams and individuals compete together).
     * Returns null if this participant never submitted.
     */
    public static function placement(int $competitionId, Competition $competition, string $participantType, int $participantId): ?array
    {
        $rows = self::build($competitionId, $competition);
        $total = count($rows);
        foreach ($rows as $i => $row) {
            $rowId = $row->participant_type === 'team' ? $row->team_id : $row->user_id;
            if ($row->participant_type === $participantType && (int) $rowId === $participantId) {
                $rank = $i + 1;
                $contested = $total >= 2;
                return [
                    'rank' => $rank,
                    'total' => $total,
                    'won' => $contested && $rank === 1,
                    'top10' => $contested && $rank <= max(1, (int) ceil($total * 0.10)),
                ];
            }
        }
        return null;
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