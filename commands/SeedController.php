<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Demo data for trying the site out.
 *
 *   php yii seed/demo     adds 10 demo users, 3 teams, 4 competitions/hackathons,
 *                          entries + submissions, datasets, notebooks and votes
 *   php yii seed/more     (after seed/demo) adds 5 more users, 1 more team,
 *                          3 more competitions/hackathons, 5 datasets, 4 notebooks
 *   php yii seed/clean    removes ONLY what seed/demo created (and anything that
 *                          points at it), leaving all real data alone
 *
 * Demo users are recognised by their email ending in ".demo@st.ug.edu.gh" or
 * ".demo@ug.edu.gh". Demo files are named "demo_*".
 */
class SeedController extends Controller
{
    const PASSWORD = 'DataForge@2026';
    const EMAIL_MARK = '.demo@';

    private const LECTURERS = [
        ['kwesi_mensah', 'kwesi.mensah'],
        ['efua_asante', 'efua.asante'],
    ];
    private const STUDENTS = [
        ['ama_owusu', 'ama.owusu'], ['kojo_boateng', 'kojo.boateng'], ['abena_darko', 'abena.darko'],
        ['yaw_osei', 'yaw.osei'], ['akosua_frimpong', 'akosua.frimpong'], ['kwame_adjei', 'kwame.adjei'],
        ['adwoa_sarpong', 'adwoa.sarpong'], ['kobby_ansah', 'kobby.ansah'],
    ];
    private const MORE_LECTURERS = [
        ['nana_agyeman', 'nana.agyeman'],
    ];
    private const MORE_STUDENTS = [
        ['esi_appiah', 'esi.appiah'], ['fiifi_quaye', 'fiifi.quaye'], ['selasi_tetteh', 'selasi.tetteh'], ['naa_lamptey', 'naa.lamptey'],
    ];

    private array $u = [];   // username => id
    private array $written = []; // files written this run (removed again if it fails)
    private int $now;

    // =====================================================================
    public function actionDemo(): int
    {
        $db = Yii::$app->db;
        $this->now = time();

        if ((new Query())->from('{{%user}}')->where(['like', 'email', self::EMAIL_MARK])->exists()) {
            $this->stderr("Demo data already exists. Run `php yii seed/clean` first if you want to recreate it.\n");
            return ExitCode::DATAERR;
        }
        foreach (array_merge(self::LECTURERS, self::STUDENTS) as [$username]) {
            if ((new Query())->from('{{%user}}')->where(['username' => $username])->exists()) {
                $this->stderr("A real user is already called \"{$username}\" — nothing was added.\n");
                return ExitCode::DATAERR;
            }
        }

        $transaction = $db->beginTransaction();
        try {
            $this->createUsers(self::LECTURERS, self::STUDENTS, 40, 0);
            $teams = $this->createTeams();
            $comps = $this->createCompetitions($this->demoCompetitionDefs());
            $this->createEntriesAndSubmissions($teams, $comps);
            $this->createDatasetsNotebooksVotes($comps);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->deleteDemoFiles();
            $this->stderr('Failed, nothing was saved: ' . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\nDemo data added.\n\n", 32);
        $this->stdout("Password for every demo account: " . self::PASSWORD . "\n\n");
        $this->stdout("Lecturers (moderators): kwesi_mensah, efua_asante\n");
        $this->stdout("Students (verified):    " . implode(', ', array_column(self::STUDENTS, 0)) . "\n\n");
        $this->stdout("Teams:\n");
        $this->stdout("  Data Wizards  — ama_owusu (owner), kojo_boateng, abena_darko, yaw_osei\n");
        $this->stdout("  Neural Nomads — akosua_frimpong (owner), kwame_adjei, adwoa_sarpong\n");
        $this->stdout("  Gradient Gang — kobby_ansah (owner), yaw_osei   (yaw is in two teams)\n\n");
        $this->stdout("Competitions:\n");
        $this->stdout("  Accra Traffic Flow Forecast      — ended (teams 2–4, RMSE)\n");
        $this->stdout("  Cocoa Yield Prediction           — submissions open (teams 2–3)\n");
        $this->stdout("  Campus Energy Hackathon          — registration open (teams only, 2–4)\n");
        $this->stdout("  Twi Sentiment Challenge          — individuals only, no registration deadline\n");
        return ExitCode::OK;
    }

    // =====================================================================
    public function actionMore(): int
    {
        $db = Yii::$app->db;
        $this->now = time();

        if (!(new Query())->from('{{%user}}')->where(['username' => 'ama_owusu'])->andWhere(['like', 'email', self::EMAIL_MARK])->exists()) {
            $this->stderr("Run `php yii seed/demo` first — seed/more builds on top of it.\n");
            return ExitCode::DATAERR;
        }
        foreach (array_merge(self::MORE_LECTURERS, self::MORE_STUDENTS) as [$username]) {
            if ((new Query())->from('{{%user}}')->where(['username' => $username])->exists()) {
                $this->stderr("\"{$username}\" already exists — seed/more has already been run (or a real user has that name). Nothing was added.\n");
                return ExitCode::DATAERR;
            }
        }

        // the existing demo users, teams and posts, so the new data can connect to them
        foreach ((new Query())->select(['id', 'username'])->from('{{%user}}')->where(['like', 'email', self::EMAIL_MARK])->all() as $row) {
            $this->u[$row['username']] = (int) $row['id'];
        }
        $teams = (new Query())->select(['id', 'name'])->from('{{%team}}')->where(['owner_id' => array_values($this->u)])->indexBy('name')->column();
        $oldPosts = (new Query())->select('id')->from('{{%post}}')
            ->where(['author_id' => array_values($this->u), 'type' => ['dataset', 'notebook']])->column();
        $twi = (new Query())->select('id')->from('{{%post}}')->where(['like', 'slug', 'demo-twi-%', false])->scalar();
        foreach (['Data Wizards', 'Neural Nomads', 'Gradient Gang'] as $name) {
            if (!isset($teams[$name])) {
                $this->stderr("The demo team \"{$name}\" is missing. Run `php yii seed/clean` then `php yii seed/demo` first.\n");
                return ExitCode::DATAERR;
            }
        }

        $transaction = $db->beginTransaction();
        try {
            $this->createUsers(self::MORE_LECTURERS, self::MORE_STUDENTS, 48, 10);
            $teams = array_map('intval', $teams) + $this->createMoreTeams($teams);
            $comps = $this->createCompetitions($this->moreCompetitionDefs());
            if ($twi) {
                $comps['twi'] = (int) $twi;
            }
            $this->createMoreEntriesAndSubmissions($teams, $comps);
            $this->createMoreDatasetsNotebooksVotes($comps, $oldPosts);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            foreach ($this->written as $file) {
                @unlink($file);
            }
            $this->stderr('Failed, nothing was saved: ' . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\nMore demo data added.\n\n", 32);
        $this->stdout("Password for every demo account: " . self::PASSWORD . "\n\n");
        $this->stdout("New lecturer (moderator): nana_agyeman\n");
        $this->stdout("New students (verified):  " . implode(', ', array_column(self::MORE_STUDENTS, 0)) . "\n\n");
        $this->stdout("Teams:\n");
        $this->stdout("  Bayes Squad (new) — esi_appiah (owner), fiifi_quaye, selasi_tetteh\n");
        $this->stdout("  Gradient Gang     — naa_lamptey joined\n\n");
        $this->stdout("Competitions:\n");
        $this->stdout("  Northern Region Malaria Forecast     — ended (teams 2–3, RMSE) — won by Bayes Squad\n");
        $this->stdout("  Mobile Money Fraud Hackathon         — submissions open (teams only, 2–5)\n");
        $this->stdout("  Accra Rent Price Prediction          — registration open (teams 2–4 or solo, RMSE)\n");
        return ExitCode::OK;
    }

    // =====================================================================
    public function actionClean(): int
    {
        $db = Yii::$app->db;
        $userIds = (new Query())->select('id')->from('{{%user}}')->where(['like', 'email', self::EMAIL_MARK])->column();
        if (empty($userIds)) {
            $this->stdout("No demo data found.\n");
            $this->deleteDemoFiles();
            return ExitCode::OK;
        }

        $postIds = (new Query())->select('id')->from('{{%post}}')->where(['author_id' => $userIds])->column();
        $teamIds = (new Query())->select('id')->from('{{%team}}')->where(['owner_id' => $userIds])->column();
        $regIds = (new Query())->select('id')->from('{{%team_competition_registration}}')
            ->where(['or', ['team_id' => $teamIds ?: [0]], ['competition_id' => $postIds ?: [0]]])->column();

        $transaction = $db->beginTransaction();
        try {
            $cmd = fn() => $db->createCommand();
            $in = fn(array $ids) => $ids ?: [0];

            // anything (real or demo) pointing at demo posts / users / teams
            $cmd()->update('{{%dataset}}', ['linked_post_id' => null], ['linked_post_id' => $in($postIds)])->execute();
            $cmd()->update('{{%notebook}}', ['linked_post_id' => null], ['linked_post_id' => $in($postIds)])->execute();
            $cmd()->delete('{{%vote}}', ['or', ['post_id' => $in($postIds)], ['user_id' => $userIds]])->execute();
            $cmd()->delete('{{%submission}}', ['or', ['competition_id' => $in($postIds)], ['user_id' => $userIds], ['team_id' => $in($teamIds)]])->execute();
            $cmd()->delete('{{%team_competition_member}}', ['or', ['registration_id' => $in($regIds)], ['user_id' => $userIds]])->execute();
            $cmd()->delete('{{%team_competition_registration}}', ['id' => $in($regIds)])->execute();
            $cmd()->delete('{{%competition_registration}}', ['or', ['competition_id' => $in($postIds)], ['user_id' => $userIds]])->execute();
            $cmd()->delete('{{%team_membership}}', ['or', ['team_id' => $in($teamIds)], ['user_id' => $userIds]])->execute();
            $cmd()->delete('{{%team}}', ['id' => $in($teamIds)])->execute();
            foreach (['{{%comment}}', '{{%bookmark}}', '{{%post_tag}}', '{{%post_status_history}}'] as $table) {
                if ($db->getTableSchema($table) !== null) {
                    $cmd()->delete($table, ['post_id' => $in($postIds)])->execute();
                }
            }
            $cmd()->delete('{{%post_status_history}}', ['changed_by' => $userIds])->execute();
            $cmd()->delete('{{%dataset}}', ['post_id' => $in($postIds)])->execute();
            $cmd()->delete('{{%notebook}}', ['post_id' => $in($postIds)])->execute();
            $cmd()->delete('{{%competition}}', ['post_id' => $in($postIds)])->execute();
            $cmd()->delete('{{%post}}', ['id' => $in($postIds)])->execute();
            if ($db->getTableSchema('{{%notification}}') !== null) {
                $cmd()->delete('{{%notification}}', ['user_id' => $userIds])->execute();
            }
            if ($db->getTableSchema('{{%follow}}') !== null) {
                $cmd()->delete('{{%follow}}', ['or', ['follower_id' => $userIds], ['followed_id' => $userIds]])->execute();
            }
            foreach ($userIds as $id) {
                Yii::$app->authManager->revokeAll($id);
            }
            $cmd()->delete('{{%user}}', ['id' => $userIds])->execute();
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr('Failed, nothing was removed: ' . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $files = $this->deleteDemoFiles();
        $this->stdout('Removed ' . count($userIds) . ' demo users, ' . count($teamIds) . ' teams, ' . count($postIds) . " posts and {$files} files.\n", 32);
        return ExitCode::OK;
    }

    // =====================================================================
    private function createUsers(array $lecturers, array $students, int $joinedDaysAgo, int $i): void
    {
        $hash = Yii::$app->security->generatePasswordHash(self::PASSWORD);
        $auth = Yii::$app->authManager;
        $first = $i;
        foreach ([[$lecturers, 'ug.edu.gh', 'moderator'], [$students, 'st.ug.edu.gh', 'student']] as [$people, $domain, $role]) {
            foreach ($people as [$username, $local]) {
                $joined = $this->now - ($joinedDaysAgo - ($i++ - $first)) * 86400;
                $row = [
                    'username' => $username,
                    'auth_key' => Yii::$app->security->generateRandomString(),
                    'password_hash' => $hash,
                    'email' => "{$local}.demo@{$domain}",
                    'status' => 10, // verified email
                    'created_at' => $joined,
                    'updated_at' => $joined,
                ];
                if ($role === 'student') {
                    $row['student_id'] = (string) (10950000 + $i * 137);
                    $row['student_verification_status'] = 'approved';
                }
                $this->u[$username] = (int) $this->insert('{{%user}}', $row);
                $auth->assign($auth->getRole($role), $this->u[$username]);
            }
        }
    }

    private function createTeams(): array
    {
        $teams = [];
        $defs = [
            'Data Wizards' => ['ama_owusu', ['kojo_boateng', 'abena_darko', 'yaw_osei']],
            'Neural Nomads' => ['akosua_frimpong', ['kwame_adjei', 'adwoa_sarpong']],
            'Gradient Gang' => ['kobby_ansah', ['yaw_osei']],
        ];
        $day = 30;
        foreach ($defs as $name => [$owner, $members]) {
            $created = $this->now - $day-- * 86400;
            $id = (int) $this->insert('{{%team}}', ['name' => $name, 'owner_id' => $this->u[$owner], 'created_at' => $created, 'cap' => 10]);
            $teams[$name] = $id;
            foreach (array_merge([$owner], $members) as $k => $member) {
                $this->insert('{{%team_membership}}', [
                    'team_id' => $id, 'user_id' => $this->u[$member], 'invite_status' => 'consented',
                    'invited_at' => $created + $k * 3600, 'responded_at' => $created + $k * 3600 + 600,
                ]);
            }
        }
        // something waiting in each direction, so those tabs aren't empty
        $this->insert('{{%team_membership}}', ['team_id' => $teams['Data Wizards'], 'user_id' => $this->u['kwame_adjei'],
            'invite_status' => 'requested', 'invited_at' => $this->now - 7200, 'note' => 'I can help with feature engineering!']);
        $this->insert('{{%team_membership}}', ['team_id' => $teams['Gradient Gang'], 'user_id' => $this->u['adwoa_sarpong'],
            'invite_status' => 'invited', 'invited_at' => $this->now - 3600]);
        return $teams;
    }

    private function demoCompetitionDefs(): array
    {
        $d = fn(int $days) => date('Y-m-d H:i:s', $this->now + $days * 86400);
        return [
            'traffic' => ['competition', 'Accra Traffic Flow Forecast', 'kwesi_mensah', -35,
                "Predict hourly vehicle counts at 12 junctions in Accra from road-sensor and weather data.\n\nScored on RMSE — lower is better.",
                ['metric' => 'rmse', 'accepts' => 'both', 'team_size_min' => 2, 'team_size_limit' => 4, 'registration_deadline' => $d(-20), 'deadline' => $d(-2),
                 'reward_type' => 'certificate', 'reward_details' => 'Certificates + department feature'],
                ['junction_id', 'hour', 'rain_mm', 'temp_c', 'vehicles'], 'vehicles'],
            'cocoa' => ['competition', 'Cocoa Yield Prediction', 'efua_asante', -25,
                "Classify cocoa farms as high or low yield from soil, rainfall and farm-practice data from the Ashanti and Western regions.",
                ['metric' => 'accuracy', 'accepts' => 'both', 'team_size_min' => 2, 'team_size_limit' => 3, 'registration_deadline' => $d(-3), 'deadline' => $d(20),
                 'reward_type' => 'cash', 'reward_details' => 'GHS 1,500 for 1st place'],
                ['farm_id', 'region', 'soil_ph', 'rain_mm', 'tree_age', 'high_yield'], 'high_yield'],
            'energy' => ['hackathon', 'Campus Energy Hackathon', 'kwesi_mensah', -6,
                "48-hour hackathon: predict which UG buildings will exceed their daily power budget, using smart-meter readings.",
                ['metric' => 'accuracy', 'accepts' => 'team', 'team_size_min' => 2, 'team_size_limit' => 4, 'registration_deadline' => $d(7), 'deadline' => $d(21),
                 'reward_type' => 'prize', 'reward_details' => 'Laptops for the winning team'],
                ['building', 'day', 'kwh', 'occupancy', 'over_budget'], 'over_budget'],
            'twi' => ['competition', 'Twi Sentiment Challenge', 'efua_asante', -10,
                "Label short Twi social-media posts as positive or negative. Individuals only — show what you can do solo.",
                ['metric' => 'accuracy', 'accepts' => 'individual', 'registration_deadline' => null, 'deadline' => $d(30),
                 'reward_type' => 'points', 'reward_details' => '500 platform points'],
                ['post_id', 'text', 'label'], 'label'],
        ];
    }

    private function moreCompetitionDefs(): array
    {
        $d = fn(int $days) => date('Y-m-d H:i:s', $this->now + $days * 86400);
        return [
            'malaria' => ['competition', 'Northern Region Malaria Forecast', 'nana_agyeman', -40,
                "Forecast weekly malaria cases for districts in the Northern Region from rainfall, temperature and past case counts.\n\nScored on RMSE — lower is better.",
                ['metric' => 'rmse', 'accepts' => 'both', 'team_size_min' => 2, 'team_size_limit' => 3, 'registration_deadline' => $d(-25), 'deadline' => $d(-5),
                 'reward_type' => 'cash', 'reward_details' => 'GHS 2,000 for 1st place'],
                ['district', 'week', 'rain_mm', 'temp_c', 'cases'], 'cases'],
            'momo' => ['hackathon', 'Mobile Money Fraud Hackathon', 'efua_asante', -14,
                "Spot fraudulent mobile money transactions before they go through. Teams only — 2 to 5 people.",
                ['metric' => 'accuracy', 'accepts' => 'team', 'team_size_min' => 2, 'team_size_limit' => 5, 'registration_deadline' => $d(-4), 'deadline' => $d(10),
                 'reward_type' => 'prize', 'reward_details' => 'Internship interviews for the winning team'],
                ['txn_id', 'amount_ghs', 'hour', 'channel', 'is_fraud'], 'is_fraud'],
            'rent' => ['competition', 'Accra Rent Price Prediction', 'nana_agyeman', -3,
                "Predict the monthly rent of a listing in Accra from its neighbourhood, size and number of bedrooms.\n\nScored on RMSE — lower is better.",
                ['metric' => 'rmse', 'accepts' => 'both', 'team_size_min' => 2, 'team_size_limit' => 4, 'registration_deadline' => $d(10), 'deadline' => $d(35),
                 'reward_type' => 'certificate', 'reward_details' => 'Certificates for the top 3'],
                ['listing_id', 'neighbourhood', 'bedrooms', 'sqm', 'rent_ghs'], 'rent_ghs'],
        ];
    }

    private function createCompetitions(array $defs): array
    {
        $comps = [];
        foreach ($defs as $key => [$type, $title, $author, $createdDays, $body, $rules, $columns, $target]) {
            $created = $this->now + $createdDays * 86400;
            $id = (int) $this->insert('{{%post}}', [
                'type' => $type, 'title' => $title, 'slug' => 'demo-' . $key . '-' . substr(md5($title), 0, 6), 'body' => $body,
                'author_id' => $this->u[$author], 'status' => 'published', 'verified' => 0, 'created_at' => $created, 'updated_at' => $created,
            ]);
            $datasetPath = $this->writeCsv("datasets/demo_{$key}_train.csv", $columns, 120, $key);
            $keyPath = $this->writeCsv("answer-keys/demo_{$key}_key.csv", ['id', 'target'], 40, $key . '_key');
            $this->insert('{{%competition}}', array_merge([
                'post_id' => $id, 'submission_cap_per_day' => 5, 'answer_key_path' => $keyPath, 'dataset_file_path' => $datasetPath,
                'dataset_description' => "Training data for {$title}. One row per observation.", 'dataset_target_column' => $target,
                'dataset_license' => 'CC BY 4.0',
            ], $rules));
            $comps[$key] = $id;
        }
        return $comps;
    }

    private function createEntriesAndSubmissions(array $teams, array $comps): void
    {
        [$teamEntry, $solo, $submit] = $this->entryHelpers($teams, $comps);

        // Accra Traffic (ended, RMSE: lower wins) — Data Wizards win, Nomads 2nd, kobby solo 3rd
        $teamEntry('Data Wizards', 'traffic', ['ama_owusu', 'kojo_boateng', 'abena_darko'], 28);
        $teamEntry('Neural Nomads', 'traffic', ['akosua_frimpong', 'kwame_adjei'], 27);
        $solo('kobby_ansah', 'traffic', 25);
        $submit('traffic', 'Data Wizards', null, [41.2, 35.8, 31.4], 19);
        $submit('traffic', 'Neural Nomads', null, [44.9, 38.1], 18);
        $submit('traffic', null, 'kobby_ansah', [52.3, 47.6], 17);

        // Cocoa (submissions open, accuracy) — Gradient Gang leading
        $teamEntry('Gradient Gang', 'cocoa', ['kobby_ansah', 'yaw_osei'], 10);
        $teamEntry('Neural Nomads', 'cocoa', ['akosua_frimpong', 'adwoa_sarpong'], 9);
        $solo('abena_darko', 'cocoa', 8);
        $submit('cocoa', 'Gradient Gang', null, [78.5, 84.0], 2);
        $submit('cocoa', 'Neural Nomads', null, [81.2], 1);
        $submit('cocoa', null, 'abena_darko', [72.4, 76.9], 2);

        // Campus Energy hackathon (registration open, teams only) — no submissions yet
        $teamEntry('Data Wizards', 'energy', ['ama_owusu', 'kojo_boateng'], 2);
        $teamEntry('Neural Nomads', 'energy', ['akosua_frimpong', 'kwame_adjei', 'adwoa_sarpong'], 1);

        // Twi Sentiment (individuals only, no registration deadline)
        foreach (['kwame_adjei' => [68.0, 74.5], 'adwoa_sarpong' => [79.1], 'kojo_boateng' => [71.3, 73.0, 77.8]] as $person => $scores) {
            $solo($person, 'twi', 6);
            $submit('twi', null, $person, $scores, 4);
        }
    }

    /** Closures for team entries, solo entries and submissions. */
    private function entryHelpers(array $teams, array $comps): array
    {
        $u = $this->u;
        $teamEntry = function (string $team, string $comp, array $lineup, int $daysAgo) use ($teams, $comps, $u) {
            $at = $this->now - $daysAgo * 86400;
            $regId = (int) $this->insert('{{%team_competition_registration}}', ['team_id' => $teams[$team], 'competition_id' => $comps[$comp], 'registered_at' => $at]);
            foreach ($lineup as $person) {
                $this->insert('{{%team_competition_member}}', ['registration_id' => $regId, 'user_id' => $u[$person], 'added_at' => $at]);
            }
        };
        $solo = function (string $person, string $comp, int $daysAgo) use ($comps, $u) {
            $this->insert('{{%competition_registration}}', ['competition_id' => $comps[$comp], 'user_id' => $u[$person], 'registered_at' => $this->now - $daysAgo * 86400]);
        };
        $submit = function (string $comp, ?string $team, ?string $person, array $scores, int $startDaysAgo) use ($teams, $comps, $u) {
            foreach ($scores as $k => $score) {
                $at = $this->now - ($startDaysAgo - $k) * 86400 + 3600 * ($k + 1);
                $file = $this->writeCsv('submissions/demo_' . $comp . '_' . ($team ? 't' . $teams[$team] : 'u' . $u[$person]) . "_{$k}.csv", ['id', 'target'], 40, "sub{$comp}{$k}");
                $this->insert('{{%submission}}', [
                    'competition_id' => $comps[$comp], 'participant_type' => $team ? 'team' : 'individual',
                    'user_id' => $team ? null : $u[$person], 'team_id' => $team ? $teams[$team] : null,
                    'file_path' => $file, 'score' => $score, 'submitted_at' => $at,
                ]);
            }
        };
        return [$teamEntry, $solo, $submit];
    }

    private function createDatasetsNotebooksVotes(array $comps): void
    {
        $datasets = $this->createDatasets([
            ['accra_sensors', 'Accra Road Sensor Readings (Jan–Mar 2026)', 'ama_owusu', 1, ['traffic', null], ['sensor_id', 'timestamp', 'vehicles', 'avg_speed_kmh'], 'vehicles'],
            ['ghana_rainfall', 'Ghana Monthly Rainfall 2000–2025', 'kwesi_mensah', 1, [null, 'Climate'], ['region', 'year', 'month', 'rain_mm'], 'rain_mm'],
            ['cocoa_soil', 'Ashanti Cocoa Farm Soil Samples', 'kobby_ansah', 0, ['cocoa', null], ['farm_id', 'soil_ph', 'nitrogen', 'potassium'], null],
            ['twi_posts', 'Twi Social Posts — Labelled Sample', 'adwoa_sarpong', 1, ['twi', null], ['post_id', 'text', 'label'], 'label'],
            ['ug_power', 'UG Smart-Meter Readings (Legon)', 'efua_asante', 0, ['energy', null], ['building', 'hour', 'kwh'], 'kwh'],
            ['kumasi_prices', 'Kumasi Market Food Prices', 'yaw_osei', 0, [null, 'Economics'], ['market', 'item', 'week', 'price_ghs'], 'price_ghs'],
        ], $comps, 20);

        $notebooks = $this->createNotebooks([
            ['traffic_baseline', 'Traffic Forecast: Gradient Boosting Baseline', 'kojo_boateng', 1, $comps['traffic']],
            ['cocoa_eda', 'Cocoa Yield — EDA and Feature Ideas', 'yaw_osei', 0, $comps['cocoa']],
            ['twi_tfidf', 'Twi Sentiment with TF-IDF + Logistic Regression', 'adwoa_sarpong', 1, $comps['twi']],
            ['rainfall_trends', 'Rainfall Trends Across Ghana’s Regions', 'akosua_frimpong', 0, $datasets['ghana_rainfall']],
            ['energy_starter', 'Campus Energy Starter Notebook', 'efua_asante', 0, $comps['energy']],
        ], 15);

        // upvotes so "trending" has an order
        $this->addVotes(array_merge(array_values($datasets), $notebooks), array_values($this->u));
    }

    /** @return array key => post id */
    private function createDatasets(array $defs, array $comps, int $startDaysAgo): array
    {
        $datasets = [];
        foreach ($defs as $k => [$key, $title, $author, $verified, [$linkComp, $topic], $columns, $target]) {
            $created = $this->now - ($startDaysAgo - $k * 3) * 86400;
            $id = (int) $this->insert('{{%post}}', [
                'type' => 'dataset', 'title' => $title, 'slug' => 'demo-ds-' . $key, 'author_id' => $this->u[$author],
                'body' => "Sample dataset for the DataForge demo: {$title}.", 'status' => 'published', 'verified' => $verified,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            $rows = 150 + $k * 40;
            $path = $this->writeCsv("standalone-datasets/demo_{$key}.csv", $columns, $rows, $key);
            $this->insert('{{%dataset}}', [
                'post_id' => $id, 'file_path' => $path, 'file_size' => filesize(Yii::getAlias('@app/web') . $path),
                'row_count' => $rows, 'column_count' => count($columns), 'license' => 'CC BY 4.0', 'download_count' => 3 + $k * 4,
                'topic' => $topic, 'linked_post_id' => $linkComp ? $comps[$linkComp] : null,
                'description' => "{$title}. Synthetic sample generated for the demo.", 'target_column' => $target,
            ]);
            $datasets[$key] = $id;
        }
        return $datasets;
    }

    /** @return int[] post ids */
    private function createNotebooks(array $defs, int $startDaysAgo): array
    {
        $notebooks = [];
        foreach ($defs as $k => [$key, $title, $author, $verified, $linked]) {
            $created = $this->now - ($startDaysAgo - $k * 2) * 86400;
            $id = (int) $this->insert('{{%post}}', [
                'type' => 'notebook', 'title' => $title, 'slug' => 'demo-nb-' . $key, 'author_id' => $this->u[$author],
                'body' => "Walkthrough notebook: {$title}.", 'status' => 'published', 'verified' => $verified,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            $this->insert('{{%notebook}}', [
                'post_id' => $id, 'language' => 'python', 'linked_post_id' => $linked,
                'notebook_file_path' => $this->writeNotebook("notebooks/demo_{$key}.ipynb", $title),
            ]);
            $notebooks[] = $id;
        }
        return $notebooks;
    }

    /** Upvotes: the first post gets $max votes, the next one fewer, and so on (never below 1). */
    private function addVotes(array $postIds, array $voters, int $max = 8): void
    {
        foreach (array_values($postIds) as $t => $postId) {
            $howMany = $max - ($t % $max);
            foreach (array_slice($voters, 0, $howMany) as $v => $voter) {
                $this->insert('{{%vote}}', ['post_id' => $postId, 'user_id' => $voter, 'value' => 1, 'created_at' => $this->now - $v * 3600]);
            }
        }
    }

    // ---- seed/more -------------------------------------------------------
    private function createMoreTeams(array $existing): array
    {
        $created = $this->now - 38 * 86400;
        $id = (int) $this->insert('{{%team}}', ['name' => 'Bayes Squad', 'owner_id' => $this->u['esi_appiah'], 'created_at' => $created, 'cap' => 10]);
        foreach (['esi_appiah', 'fiifi_quaye', 'selasi_tetteh'] as $k => $member) {
            $this->insert('{{%team_membership}}', [
                'team_id' => $id, 'user_id' => $this->u[$member], 'invite_status' => 'consented',
                'invited_at' => $created + $k * 3600, 'responded_at' => $created + $k * 3600 + 600,
            ]);
        }
        // naa joins Gradient Gang
        $this->insert('{{%team_membership}}', ['team_id' => (int) $existing['Gradient Gang'], 'user_id' => $this->u['naa_lamptey'], 'invite_status' => 'consented',
            'invited_at' => $this->now - 12 * 86400, 'responded_at' => $this->now - 12 * 86400 + 900]);
        // waiting: kojo asks to join Bayes Squad, Bayes Squad invites naa
        $this->insert('{{%team_membership}}', ['team_id' => $id, 'user_id' => $this->u['kojo_boateng'],
            'invite_status' => 'requested', 'invited_at' => $this->now - 5400, 'note' => 'I did well on the Traffic forecast, happy to help with time series.']);
        $this->insert('{{%team_membership}}', ['team_id' => $id, 'user_id' => $this->u['naa_lamptey'],
            'invite_status' => 'invited', 'invited_at' => $this->now - 1800]);
        return ['Bayes Squad' => $id];
    }

    private function createMoreEntriesAndSubmissions(array $teams, array $comps): void
    {
        [$teamEntry, $solo, $submit] = $this->entryHelpers($teams, $comps);

        // Malaria (ended, RMSE) — Bayes Squad win, abena 2nd, kwame 3rd
        $teamEntry('Bayes Squad', 'malaria', ['esi_appiah', 'fiifi_quaye', 'selasi_tetteh'], 33);
        $solo('abena_darko', 'malaria', 30);
        $solo('kwame_adjei', 'malaria', 29);
        $submit('malaria', 'Bayes Squad', null, [18.4, 15.2, 12.9], 20);
        $submit('malaria', null, 'abena_darko', [16.1, 14.0], 19);
        $submit('malaria', null, 'kwame_adjei', [21.5], 18);

        // Mobile Money hackathon (submissions open, teams only)
        $teamEntry('Bayes Squad', 'momo', ['esi_appiah', 'fiifi_quaye'], 8);
        $teamEntry('Gradient Gang', 'momo', ['kobby_ansah', 'yaw_osei', 'naa_lamptey'], 7);
        $teamEntry('Data Wizards', 'momo', ['ama_owusu', 'kojo_boateng', 'abena_darko'], 6);
        $submit('momo', 'Bayes Squad', null, [88.1, 91.4], 3);
        $submit('momo', 'Gradient Gang', null, [86.7, 89.9, 90.2], 3);
        $submit('momo', 'Data Wizards', null, [92.5], 1);

        // Accra Rent (registration open) — entries, no submissions yet
        $teamEntry('Neural Nomads', 'rent', ['akosua_frimpong', 'kwame_adjei'], 1);
        $solo('selasi_tetteh', 'rent', 2);
        $solo('fiifi_quaye', 'rent', 1);

        // Twi Sentiment (individuals only) — naa joins in
        if (isset($comps['twi'])) {
            $solo('naa_lamptey', 'twi', 3);
            $submit('twi', null, 'naa_lamptey', [80.4], 2);
        }
    }

    private function createMoreDatasetsNotebooksVotes(array $comps, array $oldPosts): void
    {
        $datasets = $this->createDatasets([
            ['northern_malaria', 'Northern Region Weekly Malaria Cases', 'nana_agyeman', 1, ['malaria', null], ['district', 'week', 'rain_mm', 'cases'], 'cases'],
            ['momo_txns', 'Synthetic Mobile Money Transactions', 'esi_appiah', 0, ['momo', null], ['txn_id', 'amount_ghs', 'hour', 'channel', 'is_fraud'], 'is_fraud'],
            ['accra_rents', 'Accra Rental Listings 2026', 'fiifi_quaye', 0, ['rent', null], ['listing_id', 'neighbourhood', 'bedrooms', 'sqm', 'rent_ghs'], 'rent_ghs'],
            ['trotro_fares', 'Accra Trotro Routes and Fares', 'selasi_tetteh', 1, [null, 'Transport'], ['route_id', 'origin', 'destination', 'distance_km', 'fare_ghs'], 'fare_ghs'],
            ['ug_courses', 'UG Course Pass Rates (Anonymised)', 'naa_lamptey', 0, [null, 'Education'], ['course_code', 'year', 'enrolled', 'pass_rate'], 'pass_rate'],
        ], $comps, 12);

        $notebooks = $this->createNotebooks([
            ['malaria_1st', 'Malaria Forecast — 1st Place Solution (Bayes Squad)', 'esi_appiah', 1, $comps['malaria']],
            ['momo_xgb', 'Fraud Detection with XGBoost and Class Weights', 'fiifi_quaye', 0, $comps['momo']],
            ['rent_eda', 'Accra Rents: Which Neighbourhoods Cost the Most?', 'naa_lamptey', 0, $datasets['accra_rents']],
            ['trotro_fares', 'Trotro Fares vs Distance', 'selasi_tetteh', 0, $datasets['trotro_fares']],
        ], 9);

        // everyone votes on the new posts; the 5 new users also vote on the older demo posts
        $this->addVotes(array_merge(array_values($datasets), $notebooks), array_values($this->u));
        $newVoters = array_map(fn($p) => $this->u[$p[0]], array_merge(self::MORE_LECTURERS, self::MORE_STUDENTS));
        $this->addVotes($oldPosts, $newVoters, 5);
    }

    // =====================================================================
    private function insert(string $table, array $row)
    {
        $pk = Yii::$app->db->getSchema()->insert($table, $row);
        if ($pk === false) {
            throw new \RuntimeException("Insert into {$table} failed.");
        }
        return $pk['id'] ?? $pk['post_id'] ?? reset($pk);
    }

    /** Small synthetic CSV, deterministic per $seed. Returns the web path ("/uploads/..."). */
    private function writeCsv(string $relative, array $columns, int $rows, string $seed): string
    {
        mt_srand(crc32($seed));
        $lines = [implode(',', $columns)];
        $words = ['akwaaba', 'me pɛ', 'ɛyɛ', 'mepa wo kyɛw', 'ayeeko', 'ɛnyɛ', 'medaase', 'ɛyɛ dɛ'];
        $regions = ['Ashanti', 'Western', 'Eastern', 'Greater Accra', 'Volta', 'Central'];
        for ($i = 1; $i <= $rows; $i++) {
            $cells = [];
            foreach ($columns as $c => $col) {
                $cells[] = match (true) {
                    $col === 'id' || str_ends_with($col, '_id') => $col === 'sensor_id' || $col === 'junction_id' ? mt_rand(1, 12) : $i,
                    $col === 'text' => '"' . $words[mt_rand(0, 7)] . ' ' . $words[mt_rand(0, 7)] . '"',
                    $col === 'region' => $regions[mt_rand(0, 5)],
                    $col === 'year' => mt_rand(2000, 2025),
                    $col === 'month' => mt_rand(1, 12),
                    $col === 'hour' => mt_rand(0, 23),
                    in_array($col, ['label', 'high_yield', 'over_budget', 'target'], true) => mt_rand(0, 1),
                    in_array($col, ['enrolled', 'cases', 'sqm'], true) => mt_rand(20, 400),
                    $col === 'distance_km' => round(mt_rand(20, 350) / 10, 1),
                    in_array($col, ['timestamp', 'day', 'week'], true) => date('Y-m-d', $this->now - mt_rand(0, 90) * 86400),
                    $col === 'district' => ['Tamale', 'Savelugu', 'Yendi', 'Tolon', 'Kumbungu', 'Gushegu'][mt_rand(0, 5)],
                    $col === 'channel' => ['USSD', 'App', 'Agent', 'Merchant'][mt_rand(0, 3)],
                    $col === 'neighbourhood' => ['East Legon', 'Osu', 'Madina', 'Spintex', 'Adenta', 'Dansoman'][mt_rand(0, 5)],
                    in_array($col, ['origin', 'destination'], true) => ['Circle', 'Madina', 'Kaneshie', 'Achimota', 'Lapaz', 'Tema Station'][mt_rand(0, 5)],
                    $col === 'course_code' => ['DCIT 101', 'DCIT 203', 'STAT 111', 'MATH 121', 'ECON 101', 'UGRC 110'][mt_rand(0, 5)],
                    $col === 'bedrooms' => mt_rand(1, 5),
                    $col === 'is_fraud' => mt_rand(0, 9) === 0 ? 1 : 0,
                    $col === 'pass_rate' => round(mt_rand(550, 980) / 10, 1),
                    $col === 'building' => ['Balme Library', 'JQB', 'Akuafo Hall', 'Legon Hall', 'CS Department', 'Great Hall'][mt_rand(0, 5)],
                    $col === 'market' => ['Kejetia', 'Adum', 'Bantama', 'Asafo'][mt_rand(0, 3)],
                    $col === 'item' => ['Maize', 'Plantain', 'Tomatoes', 'Rice', 'Yam', 'Cassava'][mt_rand(0, 5)],
                    $col === 'soil_ph' => round(mt_rand(45, 75) / 10, 1),
                    in_array($col, ['temp_c'], true) => round(mt_rand(220, 340) / 10, 1),
                    in_array($col, ['occupancy', 'tree_age', 'nitrogen', 'potassium'], true) => mt_rand(1, 80),
                    default => round(mt_rand(10, 9000) / 10, 1),
                };
            }
            $lines[] = implode(',', $cells);
        }
        return $this->writeFile($relative, implode("\n", $lines) . "\n");
    }

    private function writeNotebook(string $relative, string $title): string
    {
        $md = fn(string $s) => ['cell_type' => 'markdown', 'metadata' => (object) [], 'source' => [$s]];
        $code = fn(string $src, string $out) => ['cell_type' => 'code', 'execution_count' => 1, 'metadata' => (object) [], 'source' => [$src],
            'outputs' => [['output_type' => 'stream', 'name' => 'stdout', 'text' => [$out]]]];
        $nb = [
            'nbformat' => 4, 'nbformat_minor' => 5,
            'metadata' => ['kernelspec' => ['name' => 'python3', 'display_name' => 'Python 3'], 'language_info' => ['name' => 'python']],
            'cells' => [
                $md("# {$title}\n\nA short walkthrough made for the DataForge demo."),
                $code("import pandas as pd\ndf = pd.read_csv('train.csv')\nprint(df.shape)", "(120, 5)\n"),
                $md("## Quick look\nCheck missing values and the target balance before modelling."),
                $code("print(df.isna().sum().sum())\nprint(df.iloc[:, -1].value_counts(normalize=True).round(2))", "0\n1    0.52\n0    0.48\n"),
                $md("## Next steps\n- Try gradient boosting\n- Tune on a validation split\n- Submit and compare on the leaderboard"),
            ],
        ];
        return $this->writeFile($relative, json_encode($nb, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function writeFile(string $relative, string $content): string
    {
        $full = Yii::getAlias('@app/web/uploads/') . $relative;
        if (!is_dir(dirname($full))) {
            mkdir(dirname($full), 0775, true);
        }
        file_put_contents($full, $content);
        $this->written[] = $full;
        return '/uploads/' . $relative;
    }

    private function deleteDemoFiles(): int
    {
        $count = 0;
        foreach (glob(Yii::getAlias('@app/web/uploads') . '/*/demo_*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $count++;
            }
        }
        return $count;
    }
}
