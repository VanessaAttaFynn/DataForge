<?php

namespace app\controllers;

use Yii;
use app\models\Team;
use app\models\TeamMembership;
use app\models\User;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * Teams are generic, standalone entities — created independently of any
 * competition (basic CRUD), and separately REGISTERED for competitions
 * later (see CompetitionController::actionRegisterTeam / actionTeams).
 */
class TeamController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /** Plain, generic team creation — no competition context at all. */
    public function actionCreate()
    {
        $team = new Team();

        if (Yii::$app->request->isPost) {
            $name = trim(Yii::$app->request->post('name', ''));

            if ($name === '') {
                Yii::$app->session->setFlash('error', 'Team name is required.');
            } else {
                $transaction = Team::getDb()->beginTransaction();
                try {
                    $team = new Team([
                        'name' => $name,
                        'owner_id' => Yii::$app->user->id,
                        'created_at' => time(),
                        'cap' => 10, // fixed for now; admin-adjustable later
                    ]);
                    if (!$team->save()) {
                        throw new \RuntimeException('Failed to create team.');
                    }

                    $membership = new TeamMembership([
                        'team_id' => $team->id,
                        'user_id' => Yii::$app->user->id,
                        'invite_status' => TeamMembership::INVITE_CONSENTED,
                        'invited_at' => time(),
                        'responded_at' => time(),
                    ]);
                    if (!$membership->save()) {
                        throw new \RuntimeException('Failed to add owner as member.');
                    }

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', "Team \"{$name}\" created.");
                    return $this->redirect(['manage', 'id' => $team->id]);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', 'Could not create team — please try again.');
                }
            }
        }

        return $this->render('create', ['team' => $team]);
    }

    /** The team dashboard — banner, stats, tabs (members / pending / requests), member search. */
    public function actionManage(int $id, string $q = '')
    {
        $team = $this->findTeam($id);
        $isOwner = (int) $team->owner_id === (int) Yii::$app->user->id;

        if (!$isOwner) {
            $isMember = TeamMembership::find()
                ->where(['team_id' => $id, 'user_id' => Yii::$app->user->id, 'invite_status' => TeamMembership::INVITE_CONSENTED])
                ->exists();
            if (!$isMember) {
                throw new ForbiddenHttpException('You are not a member of this team.');
            }
        }

        $searchResults = [];
        if ($isOwner && trim($q) !== '') {
            $existingUserIds = TeamMembership::find()->select('user_id')->where(['team_id' => $id])
                ->andWhere(['not in', 'invite_status', [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED]])
                ->column();

            $searchResults = User::find()
                ->where(['or', ['like', 'username', $q], ['like', 'email', $q]])
                ->andWhere(['not in', 'id', array_merge($existingUserIds, [$team->owner_id])])
                ->limit(10)
                ->all();
        }

        // Stats: real counts where we have real data, honest zeros where we
        // don't yet (leaderboard/ranking logic isn't built, so "won" /
        // "top 10" can't be computed truthfully — wiring is ready for when it is).
        $registrations = $team->registrations;
        $stats = [
            'challenges_participated' => count(array_filter($registrations, fn($r) => $r->competitionPost->type === 'competition')),
            'challenges_won' => 0,
            'hackathons_participated' => count(array_filter($registrations, fn($r) => $r->competitionPost->type === 'hackathon')),
            'hackathons_won' => 0,
            'top_10_count' => 0,
        ];

        return $this->render('manage', [
            'team' => $team,
            'isOwner' => $isOwner,
            'q' => $q,
            'searchResults' => $searchResults,
            'stats' => $stats,
            'registrations' => $registrations,
        ]);
    }

    public function actionUploadAvatar(int $id)
    {
        $team = $this->findTeam($id);
        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can change the team image.');
        }

        $avatar = UploadedFile::getInstanceByName('avatar');
        if ($avatar !== null) {
            $dir = Yii::getAlias('@webroot/uploads/team-avatars');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $filename = $team->id . '.' . $avatar->extension;
            $avatar->saveAs("$dir/$filename");
            $team->avatar_path = "/uploads/team-avatars/$filename";
            $team->save(false);
        }

        return $this->redirect(['manage', 'id' => $id]);
    }

    /** Owner sends a direct invite to a specific user. */
    public function actionInvite(int $teamId, int $userId)
    {
        $team = $this->findTeam($teamId);
        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can invite members.');
        }
        if ($team->isFull()) {
            Yii::$app->session->setFlash('error', "Team is full ({$team->cap} member cap).");
            return $this->redirect(['manage', 'id' => $teamId]);
        }

        $existing = TeamMembership::find()->where(['team_id' => $teamId, 'user_id' => $userId])->one();
        if ($existing !== null && !in_array($existing->invite_status, [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED])) {
            Yii::$app->session->setFlash('error', 'This user already has a pending or active membership with this team.');
            return $this->redirect(['manage', 'id' => $teamId]);
        }

        $membership = $existing ?? new TeamMembership([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);

        $membership->invite_status = TeamMembership::INVITE_INVITED;
        $membership->invited_at = time();
        $membership->responded_at = null;
        $membership->conflict_status = null;
        $membership->save(false);

        Yii::$app->session->setFlash('success', 'Invite sent.');
        return $this->redirect(['manage', 'id' => $teamId]);
    }

    /** Someone not on the team asks to join — optionally with a note (e.g. "just for this competition"). */
    public function actionRequestJoin(int $id, string $note = '', ?int $competitionId = null)
    {
        $team = $this->findTeam($id);
        $userId = Yii::$app->user->id;

        $existing = TeamMembership::find()->where(['team_id' => $id, 'user_id' => $userId])->one();
        if ($existing !== null && !in_array($existing->invite_status, [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED])) {
            Yii::$app->session->setFlash('error', 'You already have a pending or active membership with this team.');
            return $this->redirect($this->backTarget($competitionId, $id));
        }

        if ($team->isFull()) {
            Yii::$app->session->setFlash('error', "This team is full ({$team->cap} member cap).");
            return $this->redirect($this->backTarget($competitionId, $id));
        }

        // Reuse the existing row if this person was declined or left before —
        // the unique (team_id, user_id) index means there can only ever be
        // ONE membership row per person per team, so re-requesting has to
        // reset that same row rather than insert a second one.
        $membership = $existing ?? new TeamMembership([
            'team_id' => $id,
            'user_id' => $userId,
        ]);

        $membership->invite_status = TeamMembership::INVITE_INVITED;
        $membership->invited_at = time();
        $membership->responded_at = null;
        $membership->conflict_status = null;
        $membership->note = trim($note) !== '' ? trim($note) : null;
        $membership->save(false);

        Yii::$app->session->setFlash('success', "Request sent to join \"{$team->name}\" — waiting on the team owner.");
        return $this->redirect($this->backTarget($competitionId, $id));
    }

    public function actionApproveMember(int $membershipId)
    {
        $membership = TeamMembership::findOne($membershipId);
        if ($membership === null) {
            throw new NotFoundHttpException('Request not found.');
        }
        $team = $membership->team;

        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can approve requests.');
        }
        if ($team->isFull()) {
            Yii::$app->session->setFlash('error', 'Team is already full.');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        $membership->invite_status = TeamMembership::INVITE_CONSENTED;
        $membership->responded_at = time();
        $membership->save(false);

        Yii::$app->session->setFlash('success', 'Member approved.');
        return $this->redirect(['manage', 'id' => $team->id]);
    }

    public function actionDeclineMember(int $membershipId)
    {
        $membership = TeamMembership::findOne($membershipId);
        if ($membership === null) {
            throw new NotFoundHttpException('Request not found.');
        }
        $team = $membership->team;

        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can decline requests.');
        }

        $membership->invite_status = TeamMembership::INVITE_DECLINED;
        $membership->responded_at = time();
        $membership->save(false);

        return $this->redirect(['manage', 'id' => $team->id]);
    }

    /** Owner removes an existing consented member (e.g. to get under a competition's team cap). */
    public function actionRemoveMember(int $membershipId)
    {
        $membership = TeamMembership::findOne($membershipId);
        if ($membership === null) {
            throw new NotFoundHttpException('Membership not found.');
        }
        $team = $membership->team;

        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the team owner can remove members.');
        }
        if ((int) $membership->user_id === (int) $team->owner_id) {
            Yii::$app->session->setFlash('error', 'The owner cannot remove themselves — use Leave instead (transfers ownership isn\'t supported yet).');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        $membership->invite_status = TeamMembership::INVITE_LEFT;
        $membership->save(false);

        Yii::$app->session->setFlash('success', 'Member removed.');
        return $this->redirect(['manage', 'id' => $team->id]);
    }

    public function actionMy()
    {
        $userId = Yii::$app->user->id;

        $memberships = TeamMembership::find()
            ->where(['user_id' => $userId, 'invite_status' => TeamMembership::INVITE_CONSENTED])
            ->all();

        $pendingInvites = TeamMembership::find()
            ->where(['user_id' => $userId, 'invite_status' => TeamMembership::INVITE_INVITED])
            ->all();

        return $this->render('my', ['memberships' => $memberships, 'pendingInvites' => $pendingInvites]);
    }

    public function actionAcceptInvite(int $membershipId)
    {
        $membership = TeamMembership::findOne($membershipId);
        if ($membership === null || (int) $membership->user_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Not your invite.');
        }
        if ($membership->team->isFull()) {
            Yii::$app->session->setFlash('error', 'Team is now full.');
            return $this->redirect(['my']);
        }

        $membership->invite_status = TeamMembership::INVITE_CONSENTED;
        $membership->responded_at = time();
        $membership->save(false);

        Yii::$app->session->setFlash('success', "You joined \"{$membership->team->name}\".");
        return $this->redirect(['my']);
    }

    public function actionDeclineInvite(int $membershipId)
    {
        $membership = TeamMembership::findOne($membershipId);
        if ($membership === null || (int) $membership->user_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Not your invite.');
        }

        $membership->invite_status = TeamMembership::INVITE_DECLINED;
        $membership->responded_at = time();
        $membership->save(false);

        return $this->redirect(['my']);
    }

    public function actionLeave(int $id)
    {
        $team = $this->findTeam($id);

        $membership = TeamMembership::find()
            ->where(['team_id' => $team->id, 'user_id' => Yii::$app->user->id, 'invite_status' => TeamMembership::INVITE_CONSENTED])
            ->one();

        if ($membership !== null) {
            $membership->invite_status = TeamMembership::INVITE_LEFT;
            $membership->save(false);

            if ($team->getConsentedMemberCount() === 0) {
                $team->delete();
            }
        }

        return $this->redirect(['my']);
    }

    private function backTarget(?int $competitionId, int $teamId): array
    {
        return $competitionId !== null
            ? ['/competition/teams', 'id' => $competitionId]
            : ['manage', 'id' => $teamId];
    }

    private function findTeam(int $id): Team
    {
        $team = Team::findOne($id);
        if ($team === null) {
            throw new NotFoundHttpException('Team not found.');
        }
        return $team;
    }
}