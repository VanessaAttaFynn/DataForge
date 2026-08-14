<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Competition;
use app\models\Team;
use app\models\TeamMembership;
use app\models\TeamCompetitionRegistration;
use app\models\CompetitionRegistration;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

class CompetitionController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'view', 'teams', 'leaderboard'], 'roles' => ['?', '@']],
                    ['allow' => true, 'actions' => ['create', 'update', 'delete', 'mine', 'dataset', 'register-team', 'submit', 'submissions', 'my-submissions'], 'roles' => ['@']],
                ],
            ],
        ];
    }

    /** Published competitions/hackathons only — the public browse page. */
    public function actionIndex(string $view = 'list')
    {
        $posts = Post::find()
            ->where(['status' => Post::STATUS_PUBLISHED])
            ->andWhere(['type' => Post::TYPES_REQUIRING_APPROVAL])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'posts' => $posts,
            'view' => $view,
            'entrantCounts' => $this->buildEntrantCounts($posts),
        ]);
    }

    /** Everything the current user created, plus everything they've registered/joined a team for. */
    public function actionMine()
    {
        $userId = Yii::$app->user->id;

        $created = Post::find()
            ->where(['author_id' => $userId])
            ->andWhere(['type' => Post::TYPES_REQUIRING_APPROVAL])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $registeredIds = CompetitionRegistration::find()
            ->select('competition_id')->where(['user_id' => $userId])->column();

        $myTeamIds = TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();

        $teamCompetitionIds = empty($myTeamIds) ? [] : TeamCompetitionRegistration::find()
            ->select('competition_id')->where(['in', 'team_id', $myTeamIds])->column();

        $joinedIds = array_unique(array_merge($registeredIds, $teamCompetitionIds));
        $createdIds = array_map(fn($p) => $p->id, $created);
        $joinedIds = array_diff($joinedIds, $createdIds);

        $joined = empty($joinedIds) ? [] : Post::find()->where(['id' => $joinedIds])->all();

        return $this->render('mine', [
            'created' => $created,
            'joined' => $joined,
            'entrantCounts' => $this->buildEntrantCounts(array_merge($created, $joined)),
        ]);
    }

    /** Dataset info + download — only visible once registered (individual or via a registered team). */
    public function actionDataset(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = Yii::$app->user->id;

        $myTeamIds = TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();

        $isRegistered = CompetitionRegistration::isRegistered($post->id, $userId)
            || (!empty($myTeamIds) && TeamCompetitionRegistration::find()
                ->where(['competition_id' => $post->id])->andWhere(['in', 'team_id', $myTeamIds])->exists());

        if (!$isRegistered && !Yii::$app->user->can('moderateContent') && (int) $post->author_id !== (int) $userId) {
            throw new ForbiddenHttpException('Register for this competition to access the dataset.');
        }

        return $this->render('dataset', ['post' => $post, 'competition' => $competition, 'summary' => $this->datasetSummary($competition->dataset_file_path, $competition)]);
    }

    /** Browse teams registered for THIS specific competition — ask to join one. */
    public function actionTeams(int $id)
    {
        $post = $this->findPost($id);
        $userId = Yii::$app->user->id;

        $registrations = TeamCompetitionRegistration::find()->where(['competition_id' => $id])->all();
        $teams = array_map(fn($r) => $r->team, $registrations);

        $myTeamIds = TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();
        $myPendingTeamIds = TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'invited'])->column();

        return $this->render('teams', [
            'post' => $post,
            'teams' => $teams,
            'myTeamIds' => $myTeamIds,
            'myPendingTeamIds' => $myPendingTeamIds,
        ]);
    }

    /**
     * Register one of the user's own teams for this competition.
     * Validates: team fits the competition's team_size_limit, and no
     * member is already committed elsewhere in this same competition
     * (as an individual, or via another team already registered here).
     */
    public function actionRegisterTeam(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = Yii::$app->user->id;

        $ownedTeamIds = Team::find()->select('id')->where(['owner_id' => $userId])->column();

        if (Yii::$app->request->isPost) {
            $teamId = (int) Yii::$app->request->post('team_id');
            $team = Team::findOne($teamId);

            if ($team === null || (int) $team->owner_id !== (int) $userId) {
                throw new ForbiddenHttpException('You can only register a team you own.');
            }

            // Cap check.
            $memberCount = $team->getConsentedMemberCount();
            if ($competition->team_size_limit !== null && $memberCount > $competition->team_size_limit) {
                Yii::$app->session->setFlash('error', "Team too large — this competition caps teams at {$competition->team_size_limit} members (yours has {$memberCount}). Remove members from the team, then try again.");
                return $this->redirect(['register-team', 'id' => $id]);
            }

            // Conflict check: is any member already an individual registrant,
            // or already on another team registered for this same competition?
            $memberIds = TeamMembership::find()->select('user_id')
                ->where(['team_id' => $team->id, 'invite_status' => 'consented'])->column();

            $conflictNames = [];
            foreach ($memberIds as $memberId) {
                $individualConflict = CompetitionRegistration::isRegistered($id, $memberId);

                $otherTeamConflict = TeamCompetitionRegistration::find()
                    ->where(['competition_id' => $id])
                    ->andWhere(['in', 'team_id', TeamMembership::find()->select('team_id')
                        ->where(['user_id' => $memberId, 'invite_status' => 'consented'])
                        ->andWhere(['!=', 'team_id', $team->id])])
                    ->exists();

                if ($individualConflict || $otherTeamConflict) {
                    $user = \app\models\User::findOne($memberId);
                    $conflictNames[] = $user->username ?? "user #{$memberId}";
                }
            }

            if (!empty($conflictNames)) {
                Yii::$app->session->setFlash('error', 'Cannot register — already linked to this competition (another team or as an individual): '
                    . implode(', ', $conflictNames) . '. They need to withdraw from their existing entry first (past submissions stay intact).');
                return $this->redirect(['register-team', 'id' => $id]);
            }

            if ($team->isRegisteredFor($id)) {
                Yii::$app->session->setFlash('error', 'This team is already registered for this competition.');
                return $this->redirect(['view', 'id' => $id]);
            }

            $registration = new TeamCompetitionRegistration([
                'team_id' => $team->id,
                'competition_id' => $id,
                'registered_at' => time(),
            ]);

            if ($registration->save()) {
                Yii::$app->session->setFlash('success', "\"{$team->name}\" is registered for \"{$post->title}\".");
            } else {
                Yii::$app->session->setFlash('error', 'Could not register — please try again.');
            }
            return $this->redirect(['view', 'id' => $id]);
        }

        $ownedTeams = empty($ownedTeamIds) ? [] : Team::find()->where(['id' => $ownedTeamIds])->all();
        return $this->render('register-team', ['post' => $post, 'competition' => $competition, 'ownedTeams' => $ownedTeams]);
    }

    /** teams-registered count + individual-registration count per post, for card display. */
    private function buildEntrantCounts(array $posts): array
    {
        $counts = [];
        foreach ($posts as $post) {
            $teams = (int) TeamCompetitionRegistration::find()->where(['competition_id' => $post->id])->count();
            $individuals = (int) CompetitionRegistration::find()->where(['competition_id' => $post->id])->count();
            $counts[$post->id] = ['teams' => $teams, 'individuals' => $individuals];
        }
        return $counts;
    }

    public function actionView(int $id)
    {
        $post = $this->findPost($id);
        $canManage = (int) Yii::$app->user->id === (int) $post->author_id || Yii::$app->user->can('moderateContent');

        $isRegistered = !Yii::$app->user->isGuest
            && CompetitionRegistration::isRegistered($post->id, Yii::$app->user->id);

        $myRegisteredTeam = null;
        $myOwnedTeamsForPrompt = [];
        if (!Yii::$app->user->isGuest) {
            $myTeamIds = TeamMembership::find()
                ->select('team_id')->where(['user_id' => Yii::$app->user->id, 'invite_status' => 'consented'])->column();

            if (!empty($myTeamIds)) {
                $reg = TeamCompetitionRegistration::find()
                    ->where(['competition_id' => $post->id])->andWhere(['in', 'team_id', $myTeamIds])->one();
                if ($reg !== null) {
                    $myRegisteredTeam = $reg->team;
                }
            }
        }

        $submissionsRemainingToday = null;
        if (!Yii::$app->user->isGuest && ($isRegistered || $myRegisteredTeam !== null)) {
            $todayStart = strtotime('today');
            $q = \app\models\Submission::find()->where(['competition_id' => $post->id])->andWhere(['>=', 'submitted_at', $todayStart]);
            $q = $myRegisteredTeam !== null
                ? $q->andWhere(['team_id' => $myRegisteredTeam->id])
                : $q->andWhere(['user_id' => Yii::$app->user->id, 'participant_type' => 'individual']);
            $submissionsRemainingToday = max(0, $post->competition->submission_cap_per_day - (int) $q->count());
        }

        return $this->render('view', [
            'post' => $post,
            'competition' => $post->competition,
            'canManage' => $canManage,
            'isRegistered' => $isRegistered,
            'myRegisteredTeam' => $myRegisteredTeam,
            'datasetSummary' => $this->datasetSummary($post->competition->dataset_file_path, $post->competition),
            'leaderboardTop' => $this->buildLeaderboard($post->id, $post->competition, 5),
            'submissionsRemainingToday' => $submissionsRemainingToday,
        ]);
    }

    /** Register as an individual for a competition that accepts individuals. */
    public function actionRegister(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;

        if ($competition->accepts === 'team') {
            Yii::$app->session->setFlash('error', 'This competition only accepts team entries.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if (CompetitionRegistration::isRegistered($post->id, Yii::$app->user->id)) {
            Yii::$app->session->setFlash('error', 'You are already registered.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $registration = new CompetitionRegistration([
            'competition_id' => $post->id,
            'user_id' => Yii::$app->user->id,
            'registered_at' => time(),
        ]);

        if ($registration->save()) {
            Yii::$app->session->setFlash('success', "You're registered for \"{$post->title}\".");
        } else {
            Yii::$app->session->setFlash('error', 'Could not register — please try again.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionCreate()
    {
        // if (!Yii::$app->user->can('createCompetition')) {
        //     throw new ForbiddenHttpException('You do not have permission to create a competition.');
        // }

        $post = new Post();
        $competition = new Competition();

        if (Yii::$app->request->isPost) {
            [$post, $competition, $success] = $this->saveFromRequest($post, $competition, true);
            if ($success) {
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('create', ['post' => $post, 'competition' => $competition]);
    }

    public function actionUpdate(int $id)
    {
        $post = $this->findPost($id);

        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only edit your own competitions.');
        }

        $competition = $post->competition;

        if (Yii::$app->request->isPost) {
            $wasRejected = $post->status === Post::STATUS_REJECTED;

            [$post, $competition, $success] = $this->saveFromRequest($post, $competition, false);

            if ($success) {
                if ($wasRejected) {
                    $post->status = Post::STATUS_PENDING;
                    $post->save(false);
                }
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('update', ['post' => $post, 'competition' => $competition]);
    }

    /**
     * NO ACTION foreign keys mean SQL Server won't cascade for us — every
     * dependent row has to go first, in dependency order, inside one transaction.
     * Teams themselves are NOT deleted (they're independent entities that
     * may be registered for other competitions) — only this competition's
     * registration link to them.
     */
    public function actionDelete(int $id)
    {
        $post = $this->findPost($id);

        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only delete your own competitions.');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('{{%team_competition_registration}}', ['competition_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%submission}}', ['competition_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%competition_registration}}', ['competition_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%comment}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%vote}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%bookmark}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post_tag}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post_status_history}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%competition}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post}}', ['id' => $post->id])->execute();

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Competition deleted.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not delete — check the logs.');
        }

        return $this->redirect(['index']);
    }

    private function saveFromRequest(Post $post, Competition $competition, bool $isNew): array
    {
        $postData = Yii::$app->request->post('Post', []);
        $competitionData = Yii::$app->request->post('Competition', []);

        $post->title = $postData['title'] ?? $post->title;
        $post->body = $postData['body'] ?? $post->body;
        $post->type = ($postData['type'] ?? 'competition') === 'hackathon' ? Post::TYPE_HACKATHON : Post::TYPE_COMPETITION;

        if ($isNew) {
            $post->slug = $this->slugify($post->title) . '-' . substr(uniqid(), -6);
            $post->author_id = Yii::$app->user->id;
        }

        $competition->attributes = $competitionData;

        foreach (['registration_deadline', 'deadline'] as $dateField) {
            if (!empty($competition->$dateField)) {
                $normalized = str_replace('T', ' ', $competition->$dateField);
                if (strlen($normalized) === 16) {
                    $normalized .= ':00';
                }
                $competition->$dateField = $normalized;
            }
        }

        $postValid = $post->validate();
        $competitionValid = $competition->validate([
            'metric', 'accepts', 'team_size_limit', 'submission_cap_per_day',
            'reward_type', 'reward_details', 'registration_deadline', 'deadline',
            'dataset_description', 'dataset_target_column', 'dataset_license',
            'dataset_rows', 'dataset_columns', 'dataset_sheets',
        ]);
        $isValid = $postValid && $competitionValid;
        $success = false;

        if (!$isValid) {
            $errors = array_merge($post->getFirstErrors(), $competition->getFirstErrors());
            Yii::$app->session->setFlash('error', 'Please fix the following: ' . implode(' · ', $errors));
        }

        if ($isValid) {
            $transaction = Post::getDb()->beginTransaction();
            try {
                if ($isNew) {
                    $post->applyInitialStatus();
                }
                if (!$post->save(false)) {
                    throw new \RuntimeException('Failed to save post.');
                }

                $coverImage = UploadedFile::getInstanceByName('cover_image');
                if ($coverImage !== null) {
                    $coverDir = Yii::getAlias('@webroot/uploads/covers');
                    if (!is_dir($coverDir)) {
                        mkdir($coverDir, 0775, true);
                    }
                    $coverFilename = $post->id . '.' . $coverImage->extension;
                    $coverImage->saveAs("$coverDir/$coverFilename");
                    $post->cover_image_path = "/uploads/covers/$coverFilename";
                    $post->save(false);
                }

                $answerKey = UploadedFile::getInstanceByName('answer_key');
                if ($answerKey !== null) {
                    $answerDir = Yii::getAlias('@webroot/uploads/answer-keys');
                    if (!is_dir($answerDir)) {
                        mkdir($answerDir, 0775, true);
                    }
                    $answerFilename = $post->id . '.' . $answerKey->extension;
                    $answerKey->saveAs("$answerDir/$answerFilename");
                    $competition->answer_key_path = "/uploads/answer-keys/$answerFilename";
                }

                $datasetFile = UploadedFile::getInstanceByName('dataset_file');
                if ($datasetFile !== null) {
                    $datasetDir = Yii::getAlias('@webroot/uploads/datasets');
                    if (!is_dir($datasetDir)) {
                        mkdir($datasetDir, 0775, true);
                    }
                    $datasetFilename = $post->id . '_' . $datasetFile->baseName . '.' . $datasetFile->extension;
                    $datasetFile->saveAs("$datasetDir/$datasetFilename");
                    $competition->dataset_file_path = "/uploads/datasets/$datasetFilename";
                }

                $competition->post_id = $post->id;
                if (!$competition->save(false)) {
                    throw new \RuntimeException('Failed to save competition details.');
                }

                $transaction->commit();
                $success = true;

                Yii::$app->session->setFlash('success', $isNew && $post->requiresApproval()
                    ? "\"{$post->title}\" was submitted and is now awaiting approval."
                    : "\"{$post->title}\" saved.");
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Save failed: ' . $e->getMessage());
            }
        }

        return [$post, $competition, $success];
    }

    /** File size, type, and (for CSV) row/column counts — everything else honestly "not specified" rather than guessed. */
    private function datasetSummary(?string $relativePath, ?Competition $competition = null): array
    {
        $summary = [
            'exists' => false, 'size' => null, 'type' => null, 'rows' => null, 'columns' => null,
            'sheets' => null, 'target' => $competition->dataset_target_column ?? null,
            'license' => $competition->dataset_license ?? null,
            'description' => $competition->dataset_description ?? null,
            'filename' => null,
        ];

        if (empty($relativePath)) {
            return $summary;
        }

        $fullPath = Yii::getAlias('@webroot') . $relativePath;
        if (!file_exists($fullPath)) {
            return $summary;
        }

        $summary['exists'] = true;
        $summary['filename'] = basename($relativePath);

        $bytes = filesize($fullPath);
        $summary['size'] = $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 1) . ' KB';

        $ext = strtoupper(pathinfo($fullPath, PATHINFO_EXTENSION));
        $summary['type'] = $ext ?: 'Unknown';

        if (strtolower($ext) === 'csv') {
            $handle = @fopen($fullPath, 'r');
            if ($handle) {
                $header = fgetcsv($handle, 0, ',', '"', '\\');
                $summary['columns'] = $header ? count($header) : null;
                $rowCount = 0;
                while (fgetcsv($handle, 0, ',', '"', '\\') !== false) {
                    $rowCount++;
                }
                fclose($handle);
                $summary['rows'] = $rowCount;
                $summary['sheets'] = 1;
            }
        }

        // Manual overrides always win — needed for non-CSV formats
        // (XLSX, images, etc.) that can't be auto-parsed here.
        if ($competition !== null) {
            if ($competition->dataset_rows !== null) $summary['rows'] = $competition->dataset_rows;
            if ($competition->dataset_columns !== null) $summary['columns'] = $competition->dataset_columns;
            if ($competition->dataset_sheets !== null) $summary['sheets'] = $competition->dataset_sheets;
        }

        return $summary;
    }

    /** Upload a prediction file, score it against the hidden answer key, save the result. */
    public function actionSubmit(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = Yii::$app->user->id;

        if (empty($competition->answer_key_path)) {
            Yii::$app->session->setFlash('error', 'This competition has no scoring key set up yet — submissions can\'t be scored.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Determine participant identity: individual registration or a registered team.
        $isIndividual = \app\models\CompetitionRegistration::isRegistered($id, $userId);
        $myTeamIds = \app\models\TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();
        $myTeamReg = empty($myTeamIds) ? null : \app\models\TeamCompetitionRegistration::find()
            ->where(['competition_id' => $id])->andWhere(['in', 'team_id', $myTeamIds])->one();

        if (!$isIndividual && $myTeamReg === null) {
            Yii::$app->session->setFlash('error', 'Register for this competition before submitting.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $participantType = $myTeamReg !== null ? \app\models\Submission::PARTICIPANT_TEAM : \app\models\Submission::PARTICIPANT_INDIVIDUAL;
        $teamId = $myTeamReg?->team_id;

        // Daily submission cap.
        $todayStart = strtotime('today');
        $todayCountQuery = \app\models\Submission::find()->where(['competition_id' => $id])->andWhere(['>=', 'submitted_at', $todayStart]);
        $todayCountQuery = $participantType === 'team'
            ? $todayCountQuery->andWhere(['team_id' => $teamId])
            : $todayCountQuery->andWhere(['user_id' => $userId, 'participant_type' => 'individual']);

        if ((int) $todayCountQuery->count() >= $competition->submission_cap_per_day) {
            Yii::$app->session->setFlash('error', "Daily submission cap reached ({$competition->submission_cap_per_day}/day). Try again tomorrow.");
            return $this->redirect(['view', 'id' => $id]);
        }

        $file = UploadedFile::getInstanceByName('prediction_file');
        if ($file === null) {
            Yii::$app->session->setFlash('error', 'Choose a CSV file to submit.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $subDir = Yii::getAlias('@webroot/uploads/submissions');
        if (!is_dir($subDir)) {
            mkdir($subDir, 0775, true);
        }
        $filename = $id . '_' . $userId . '_' . time() . '.csv';
        $file->saveAs("$subDir/$filename");
        $relativePath = "/uploads/submissions/$filename";

        try {
            $score = \app\components\ScoringService::score(
                Yii::getAlias('@webroot') . $relativePath,
                Yii::getAlias('@webroot') . $competition->answer_key_path,
                $competition->metric
            );
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            // TODO: swap back to a generic message once this is stable —
            // showing the raw exception is just for testing right now.
            Yii::$app->session->setFlash('error', 'Could not score this submission: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }

        $submission = new \app\models\Submission([
            'competition_id' => $id,
            'participant_type' => $participantType,
            'user_id' => $participantType === 'individual' ? $userId : null,
            'team_id' => $teamId,
            'file_path' => $relativePath,
            'score' => $score,
            'submitted_at' => time(),
        ]);

        if ($submission->save()) {
            Yii::$app->session->setFlash('success', "Submitted! Score: {$score} ({$competition->metric}).");
        } else {
            Yii::$app->session->setFlash('error', 'Could not save the submission — please try again.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Full ranked leaderboard — best score per participant. */
    /** The current user's own submission history for this competition (individual or via their registered team). */
    public function actionMySubmissions(int $id)
    {
        $post = $this->findPost($id);
        $userId = Yii::$app->user->id;

        $myTeamIds = \app\models\TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();
        $myRegisteredTeamIds = empty($myTeamIds) ? [] : \app\models\TeamCompetitionRegistration::find()
            ->select('team_id')->where(['competition_id' => $id])->andWhere(['in', 'team_id', $myTeamIds])->column();

        $submissions = \app\models\Submission::find()
            ->where(['competition_id' => $id])
            ->andWhere(['or',
                ['user_id' => $userId, 'participant_type' => 'individual'],
                empty($myRegisteredTeamIds) ? ['0=1'] : ['team_id' => $myRegisteredTeamIds],
            ])
            ->orderBy(['submitted_at' => SORT_DESC])
            ->all();

        return $this->render('my-submissions', ['post' => $post, 'competition' => $post->competition, 'submissions' => $submissions]);
    }

    /** Every submission attempt for this competition (not deduped to best-per-participant) — owner/moderator only. */
    public function actionSubmissions(int $id)
    {
        $post = $this->findPost($id);

        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('Only the competition owner can view all submissions.');
        }

        $submissions = \app\models\Submission::find()
            ->where(['competition_id' => $id])
            ->orderBy(['submitted_at' => SORT_DESC])
            ->all();

        return $this->render('submissions', ['post' => $post, 'competition' => $post->competition, 'submissions' => $submissions]);
    }

    public function actionLeaderboard(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;

        $rows = $this->buildLeaderboard($id, $competition, null);

        return $this->render('leaderboard', ['post' => $post, 'competition' => $competition, 'rows' => $rows]);
    }

    /** Best score per participant (individual or team), sorted per the metric's direction. limit=null for full list. */
    private function buildLeaderboard(int $competitionId, Competition $competition, ?int $limit): array
    {
        $submissions = \app\models\Submission::find()->where(['competition_id' => $competitionId])->all();

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

    private function findPost(int $id): Post
    {
        $post = Post::find()->where(['id' => $id, 'type' => Post::TYPES_REQUIRING_APPROVAL])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Competition not found.');
        }
        return $post;
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        return $text ?: 'untitled';
    }
}