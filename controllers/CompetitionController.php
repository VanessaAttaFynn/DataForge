<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Competition;
use app\models\Team;
use app\models\TeamMembership;
use app\models\TeamCompetitionRegistration;
use app\models\CompetitionRegistration;
use app\models\TeamCompetitionMember;
use app\components\EntryService;
use yii\filters\VerbFilter;
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
                    ['allow' => true, 'actions' => ['index', 'view', 'teams', 'leaderboard', 'download-dataset'], 'roles' => ['?', '@']],
                    ['allow' => true, 'actions' => ['create', 'update', 'delete', 'mine', 'dataset', 'register', 'register-team', 'submit', 'submissions', 'my-submissions', 'contribute', 'withdraw', 'withdraw-team'], 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'register' => ['post'], 'withdraw' => ['post'], 'withdraw-team' => ['post'],
                    'delete' => ['post'], 'submit' => ['post'],
                ],
            ],
        ];
    }

    /** Published competitions/hackathons only — the public browse page. */
    /** Browse — search, filter by type/reward/status, sort by date or reward, asc/desc. */
    public function actionIndex(string $view = 'list', string $q = '', string $type = '', string $reward = '', string $status = '', string $sort = 'date', string $order = 'desc')
    {
        $query = Post::find()->where(['status' => Post::STATUS_PUBLISHED])
            ->andWhere(['type' => Post::TYPES_REQUIRING_APPROVAL])
            ->innerJoinWith('competition');

        if (trim($q) !== '') {
            $query->andWhere(['like', '{{%post}}.title', $q]);
        }
        if (in_array($type, ['competition', 'hackathon'], true)) {
            $query->andWhere(['{{%post}}.type' => $type]);
        }
        if (trim($reward) !== '') {
            $query->andWhere(['{{%competition}}.reward_type' => $reward]);
        }

        $sortColumn = $sort === 'reward' ? '{{%competition}}.reward_type' : '{{%post}}.created_at';
        $sortDir = $order === 'asc' ? SORT_ASC : SORT_DESC;
        $posts = $query->orderBy([$sortColumn => $sortDir])->all();

        // Phase (registration/ongoing/completed) is computed from dates, not
        // a stored column, so this filter runs in PHP after loading.
        if (in_array($status, ['registration', 'ongoing', 'completed'], true)) {
            $posts = array_values(array_filter($posts, fn($p) => $p->competition->phaseKey() === $status));
        }

        return $this->render('index', [
            'posts' => $posts,
            'view' => $view,
            'entrantCounts' => $this->buildEntrantCounts($posts),
            'q' => $q, 'type' => $type, 'reward' => $reward, 'status' => $status, 'sort' => $sort, 'order' => $order,
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

        $joinedIds = EntryService::competitionIdsFor((int) $userId);
        $createdIds = array_map(fn($p) => $p->id, $created);
        $joinedIds = array_diff($joinedIds, $createdIds);

        $joined = empty($joinedIds) ? [] : Post::find()->where(['id' => $joinedIds])->all();

        // Rank on the leaderboard for each joined competition — as an individual,
        // or via the team line-up I'm on (a person has at most one entry per competition).
        $ranks = [];
        foreach ($joined as $post) {
            $competition = $post->competition;
            if (EntryService::isIndividual($post->id, (int) $userId)) {
                $ranks[$post->id] = \app\components\LeaderboardService::rankFor($post->id, $competition, 'individual', $userId);
            } elseif ($myReg = EntryService::teamEntryFor($post->id, (int) $userId)) {
                $ranks[$post->id] = \app\components\LeaderboardService::rankFor($post->id, $competition, 'team', $myReg->team_id);
            }
        }

        return $this->render('mine', [
            'created' => $created,
            'joined' => $joined,
            'entrantCounts' => $this->buildEntrantCounts(array_merge($created, $joined)),
            'ranks' => $ranks,
        ]);
    }

    /** Dataset info + download — only visible once registered (individual or via a registered team). */
    /** Streams the competition's attached dataset through PHP, rather than a raw static-file link — sidesteps any IIS-level file-extension restrictions on the uploads folder. */
    public function actionDownloadDataset(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;

        if (empty($competition->dataset_file_path)) {
            throw new NotFoundHttpException('No dataset file for this competition.');
        }

        $fullPath = Yii::getAlias('@webroot') . $competition->dataset_file_path;
        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('Dataset file is missing on the server.');
        }

        return Yii::$app->response->sendFile($fullPath);
    }

    public function actionDataset(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = Yii::$app->user->id;

        $isRegistered = EntryService::hasEntry($post->id, (int) $userId);

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

        $myTeamIds = $userId === null ? [] : TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => TeamMembership::INVITE_CONSENTED])->column();
        $myPendingTeamIds = $userId === null ? [] : TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => [TeamMembership::INVITE_INVITED, TeamMembership::INVITE_REQUESTED]])->column();

        return $this->render('teams', [
            'post' => $post,
            'competition' => $post->competition,
            'registrations' => $registrations,
            'myTeamIds' => $myTeamIds,
            'myPendingTeamIds' => $myPendingTeamIds,
        ]);
    }

    /**
     * Register one of the user's own teams for this competition, with a chosen line-up.
     * Only the people on the line-up count for this competition — someone who joins
     * the team later is not added automatically.
     * Checks: competition takes teams, line-up size within min/max, and nobody on the
     * line-up is already entered here (as an individual or on another team).
     */
    public function actionRegisterTeam(int $id)
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = (int) Yii::$app->user->id;

        if ($post->status !== Post::STATUS_PUBLISHED) {
            throw new ForbiddenHttpException('This competition is not open yet.');
        }
        if ($competition->accepts === Competition::ACCEPTS_INDIVIDUAL) {
            Yii::$app->session->setFlash('error', 'This competition only accepts individual entries.');
            return $this->redirect(['view', 'id' => $id]);
        }
        if (!$competition->isRegistrationOpen()) {
            $reason = $competition->hasEnded() ? 'This competition has ended.' : "Registration closed on {$competition->registration_deadline}.";
            Yii::$app->session->setFlash('error', $reason);
            return $this->redirect(['view', 'id' => $id]);
        }

        $ownedTeams = Team::find()->where(['owner_id' => $userId, 'archived_at' => null])->all();

        if (Yii::$app->request->isPost) {
            $teamId = (int) Yii::$app->request->post('team_id');
            $team = Team::findOne($teamId);

            if ($team === null || (int) $team->owner_id !== $userId || $team->isArchived()) {
                throw new ForbiddenHttpException('You can only register a team you own.');
            }
            if ($team->isRegisteredFor($id)) {
                Yii::$app->session->setFlash('error', 'This team is already registered for this competition.');
                return $this->redirect(['view', 'id' => $id]);
            }

            // Line-up: the ticked members — must all be current members of the team.
            $memberIds = array_map('intval', TeamMembership::find()->select('user_id')
                ->where(['team_id' => $team->id, 'invite_status' => TeamMembership::INVITE_CONSENTED])->column());
            $postedMembers = (array) Yii::$app->request->post('members', []);
            $picked = array_map('intval', (array) ($postedMembers[$team->id] ?? []));
            $lineup = array_values(array_unique(array_intersect($picked, $memberIds)));

            if (empty($lineup)) {
                Yii::$app->session->setFlash('error', 'Pick at least one member for the line-up.');
                return $this->redirect(['register-team', 'id' => $id]);
            }
            if ($sizeError = $competition->teamSizeError(count($lineup))) {
                Yii::$app->session->setFlash('error', $sizeError);
                return $this->redirect(['register-team', 'id' => $id]);
            }

            $problems = [];
            foreach ($lineup as $memberId) {
                $user = \app\models\User::findOne($memberId);
                $name = $user->username ?? "user #{$memberId}";
                if (EntryService::hasEntry($id, $memberId)) {
                    $problems[] = "{$name} (already entered here)";
                } elseif ($user !== null && $user->isRestrictedStudent()) {
                    $problems[] = "{$name} (student ID not verified)";
                }
            }
            if (!empty($problems)) {
                Yii::$app->session->setFlash('error', 'Cannot register — ' . implode(', ', $problems)
                    . '. Leave them off the line-up, or they need to withdraw from their other entry first.');
                return $this->redirect(['register-team', 'id' => $id]);
            }

            $transaction = TeamCompetitionRegistration::getDb()->beginTransaction();
            try {
                $registration = new TeamCompetitionRegistration([
                    'team_id' => $team->id,
                    'competition_id' => $id,
                    'registered_at' => time(),
                ]);
                if (!$registration->save()) {
                    throw new \RuntimeException('Failed to save registration.');
                }
                foreach ($lineup as $memberId) {
                    (new TeamCompetitionMember([
                        'registration_id' => $registration->id,
                        'user_id' => $memberId,
                        'added_at' => time(),
                    ]))->save(false);
                }
                $transaction->commit();
                Yii::$app->session->setFlash('success', "\"{$team->name}\" is registered for \"{$post->title}\" with " . count($lineup) . ' member(s).');
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Could not register — please try again.');
            }
            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('register-team', ['post' => $post, 'competition' => $competition, 'ownedTeams' => $ownedTeams]);
    }

    /** Individual withdraws — only while registration is open and before any submission. */
    public function actionWithdraw(int $id)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;
        $userId = (int) Yii::$app->user->id;

        $registration = CompetitionRegistration::findOne(['competition_id' => $id, 'user_id' => $userId]);
        if ($registration === null) {
            Yii::$app->session->setFlash('error', 'You are not registered as an individual here.');
        } elseif ($error = $this->withdrawBlockedReason($competition, EntryService::individualHasSubmissions($id, $userId))) {
            Yii::$app->session->setFlash('error', $error);
        } else {
            $registration->delete();
            Yii::$app->session->setFlash('success', "You withdrew from \"{$post->title}\".");
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Team owner withdraws the team's entry — only while registration is open and before any submission. */
    public function actionWithdrawTeam(int $id, int $registrationId)
    {
        $post = $this->findPost($id);
        $competition = $post->competition;

        $registration = TeamCompetitionRegistration::findOne(['id' => $registrationId, 'competition_id' => $id]);
        if ($registration === null) {
            throw new NotFoundHttpException('Entry not found.');
        }
        if ((int) $registration->team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can withdraw the team.');
        }

        if ($error = $this->withdrawBlockedReason($competition, $registration->hasSubmissions())) {
            Yii::$app->session->setFlash('error', $error);
            return $this->redirect(['view', 'id' => $id]);
        }

        $transaction = TeamCompetitionRegistration::getDb()->beginTransaction();
        try {
            TeamCompetitionMember::deleteAll(['registration_id' => $registration->id]);
            $registration->delete();
            $transaction->commit();
            Yii::$app->session->setFlash('success', "\"{$registration->team->name}\" withdrew from \"{$post->title}\".");
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not withdraw — please try again.');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Withdrawing is allowed only before registration closes and before any submission (Kaggle-style: after that the entry stays). */
    private function withdrawBlockedReason(Competition $competition, bool $hasSubmissions): ?string
    {
        if (!$competition->isRegistrationOpen()) {
            return 'Registration has closed, so entries can no longer be withdrawn.';
        }
        if ($hasSubmissions) {
            return 'This entry already has submissions, so it can no longer be withdrawn.';
        }
        return null;
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
        $canEdit = $this->canEdit($post);
        $canModerate = !Yii::$app->user->isGuest && Yii::$app->user->can('moderateContent');
        $canManage = $canEdit || $canModerate; // dataset access, all-submissions view
        $userId = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;

        $isRegistered = $userId !== null && EntryService::isIndividual($post->id, $userId);

        $myTeamEntry = $userId === null ? null : EntryService::teamEntryFor($post->id, $userId);
        $myRegisteredTeam = $myTeamEntry?->team;

        // Withdraw buttons: only shown when withdrawing is actually allowed.
        $competitionModel = $post->competition;
        $canWithdrawIndividual = $isRegistered && $competitionModel->isRegistrationOpen()
            && !EntryService::individualHasSubmissions($post->id, $userId);
        $canWithdrawTeam = $myTeamEntry !== null && (int) $myTeamEntry->team->owner_id === $userId
            && $competitionModel->isRegistrationOpen() && !$myTeamEntry->hasSubmissions();

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
            'canEdit' => $canEdit,
            'isRegistered' => $isRegistered,
            'myRegisteredTeam' => $myRegisteredTeam,
            'myTeamEntry' => $myTeamEntry,
            'canWithdrawIndividual' => $canWithdrawIndividual,
            'canWithdrawTeam' => $canWithdrawTeam,
            'datasetSummary' => $this->datasetSummary($post->competition->dataset_file_path, $post->competition),
            'leaderboardTop' => \app\components\LeaderboardService::build($post->id, $post->competition, 5),
            'submissionsRemainingToday' => $submissionsRemainingToday,
        ]);
    }

    /** Register as an individual for a competition that accepts individuals. */
    public function actionRegister(int $id)
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $post = $this->findPost($id);
        $competition = $post->competition;

        if ($competition->accepts === 'team') {
            Yii::$app->session->setFlash('error', 'This competition only accepts team entries.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if (!$competition->isRegistrationOpen()) {
            $reason = $competition->hasEnded() ? 'This competition has ended.' : "Registration closed on {$competition->registration_deadline}.";
            Yii::$app->session->setFlash('error', $reason);
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($post->status !== Post::STATUS_PUBLISHED) {
            throw new ForbiddenHttpException('This competition is not open yet.');
        }

        if (CompetitionRegistration::isRegistered($post->id, Yii::$app->user->id)) {
            Yii::$app->session->setFlash('error', 'You are already registered.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($teamEntry = EntryService::teamEntryFor($post->id, (int) Yii::$app->user->id)) {
            Yii::$app->session->setFlash('error', "You're already entered on team \"{$teamEntry->team->name}\"'s line-up. Drop out of that line-up first (or have the owner withdraw it) to enter as an individual.");
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
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

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

        if (!$this->canEdit($post)) {
            throw new ForbiddenHttpException('Only the person who created this competition (or an admin) can edit it.');
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
            $registrationIds = TeamCompetitionRegistration::find()->select('id')->where(['competition_id' => $post->id])->column();
            if (!empty($registrationIds)) {
                $db->createCommand()->delete('{{%team_competition_member}}', ['registration_id' => $registrationIds])->execute();
            }
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

        // Once anyone has registered, the rules that would be unfair to change are locked.
        $lockErrors = [];
        $locked = !$isNew && $competition->hasEntries();
        $before = $locked ? $competition->getAttributes() : [];
        $hadSubmissions = !$isNew && \app\models\Submission::find()->where(['competition_id' => $post->id])->exists();

        foreach (['team_size_min', 'team_size_limit'] as $sizeField) {
            if (array_key_exists($sizeField, $competitionData) && $competitionData[$sizeField] === '') {
                $competitionData[$sizeField] = null; // blank = no limit
            }
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

        if ($locked) {
            foreach (['metric', 'accepts', 'team_size_min', 'team_size_limit', 'submission_cap_per_day'] as $field) {
                if ((string) $competition->$field !== (string) $before[$field]) {
                    $competition->$field = $before[$field];
                    $lockErrors[] = "\"{$field}\" can't change once people have registered";
                }
            }
            foreach (['registration_deadline' => 'Registration deadline', 'deadline' => 'Final deadline'] as $field => $label) {
                $old = $before[$field];
                $new = $competition->$field;
                if (empty($old)) {
                    // No deadline = open-ended. Adding one would shorten it, which isn't allowed.
                    if (!empty($new)) {
                        $competition->$field = $old;
                        $lockErrors[] = "{$label} can't be added once people have registered";
                    }
                    continue;
                }
                if (empty($new) && $field === 'registration_deadline') {
                    $competition->$field = $old;
                    $lockErrors[] = 'The registration deadline can\'t be removed once people have registered';
                } elseif (!empty($new) && intdiv(strtotime($new), 60) < intdiv(strtotime($old), 60)) { // minute precision — the form has no seconds
                    $competition->$field = $old;
                    $lockErrors[] = "{$label} can only be moved later, not earlier";
                }
            }
        }

        $postValid = $post->validate();
        $competitionValid = $competition->validate([
            'metric', 'accepts', 'team_size_min', 'team_size_limit', 'submission_cap_per_day',
            'reward_type', 'reward_details', 'registration_deadline', 'deadline',
            'dataset_description', 'dataset_target_column', 'dataset_license',
            'dataset_rows', 'dataset_columns', 'dataset_sheets',
        ]);
        if ($hadSubmissions && UploadedFile::getInstanceByName('answer_key') !== null) {
            $lockErrors[] = 'The answer key can\'t be replaced after submissions have been scored';
        }

        $isValid = $postValid && $competitionValid && empty($lockErrors);
        $success = false;

        if (!$isValid) {
            $errors = array_merge($post->getFirstErrors(), $competition->getFirstErrors(), $lockErrors);
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

        if (!$competition->isSubmissionOpen()) {
            if ($competition->hasEnded()) {
                Yii::$app->session->setFlash('error', 'This competition has ended — submissions are closed.');
            } else {
                Yii::$app->session->setFlash('error', "Submissions open once registration closes on {$competition->registration_deadline}.");
            }
            return $this->redirect(['view', 'id' => $id]);
        }

        // Who is submitting: an individual entrant, or someone on a team's line-up
        // for this competition. Only line-up members can submit for a team.
        $isIndividual = EntryService::isIndividual($id, (int) $userId);
        $myTeamReg = EntryService::teamEntryFor($id, (int) $userId);

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

        $myTeamReg = EntryService::teamEntryFor($id, (int) $userId);
        $myRegisteredTeamIds = $myTeamReg !== null ? [$myTeamReg->team_id] : [];

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

        $rows = \app\components\LeaderboardService::build($id, $competition, null);

        return $this->render('leaderboard', ['post' => $post, 'competition' => $competition, 'rows' => $rows]);
    }

    /** Chooser page — add a dataset or notebook, pre-linked to this competition/hackathon. */
    public function actionContribute(int $id)
    {
        $post = $this->findPost($id);
        return $this->render('contribute', ['post' => $post]);
    }

    /** Editing a competition: only the person who created it, or an admin. Moderators approve/reject, they don't edit. */
    private function canEdit(Post $post): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        return (int) Yii::$app->user->id === (int) $post->author_id || Yii::$app->user->can('manageUsers');
    }

    private function findPost(int $id): Post
    {
        $post = Post::find()->where(['id' => $id, 'type' => Post::TYPES_REQUIRING_APPROVAL])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Competition not found.');
        }
        return $post;
    }

    /** Blocks unverified students from creating/joining. Returns a redirect response if blocked, null otherwise. */
    private function blockRestrictedStudent()
    {
        if (Yii::$app->user->identity->isRestrictedStudent()) {
            Yii::$app->session->setFlash('error', 'Verify your student ID on your Profile before creating or joining anything.');
            return $this->redirect(['/user/profile']);
        }
        return null;
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