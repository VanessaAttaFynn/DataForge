<?php

namespace app\controllers;

use Yii;
use app\components\EntryService;
use app\models\Team;
use app\models\TeamCompetitionMember;
use app\models\TeamCompetitionRegistration;
use app\models\TeamMembership;
use app\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Teams are standalone — created on their own, then REGISTERED for
 * competitions (see CompetitionController::actionRegisterTeam).
 *
 * Rules:
 *  - The owner invites people (status "invited") — only the invitee can accept.
 *  - People ask to join (status "requested") — only the owner can approve.
 *  - The owner must hand ownership to another member before leaving.
 *  - When the last member leaves, the team is archived (kept read-only), not deleted.
 *  - Each competition entry has its own line-up. Joining the team later does not
 *    put you on line-ups it has already entered; the owner adds people to a
 *    line-up explicitly, and only while that competition's registration is open.
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'invite' => ['post'], 'request-join' => ['post'], 'approve-member' => ['post'],
                    'decline-member' => ['post'], 'remove-member' => ['post'], 'accept-invite' => ['post'],
                    'decline-invite' => ['post'], 'leave' => ['post'], 'transfer-ownership' => ['post'],
                    'lineup-add' => ['post'], 'lineup-remove' => ['post'], 'upload-avatar' => ['post'],
                ],
            ],
        ];
    }

    /** Plain, generic team creation — no competition context at all. */
    public function actionCreate()
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

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
                        'cap' => Team::DEFAULT_CAP, // fixed for now; admin-adjustable later
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

    /** The team dashboard — banner, stats, tabs (members / requests / invites / competitions), member search. */
    public function actionManage(int $id, string $q = '')
    {
        $team = $this->findTeam($id);
        $userId = (int) Yii::$app->user->id;

        if ($team->isArchived()) {
            Yii::$app->session->setFlash('error', "\"{$team->name}\" is archived — it's listed under Past teams.");
            return $this->redirect(['my']);
        }

        $isOwner = (int) $team->owner_id === $userId;
        if (!$isOwner && !$team->isMember($userId)) {
            throw new ForbiddenHttpException('You are not a member of this team.');
        }

        $searchResults = [];
        if ($isOwner && trim($q) !== '') {
            $existingUserIds = TeamMembership::find()->select('user_id')->where(['team_id' => $id])
                ->andWhere(['not in', 'invite_status', [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED]])
                ->column();

            $searchResults = User::find()
                ->where(['or', ['like', 'username', $q], ['like', 'email', $q]])
                ->andWhere(['not in', 'id', array_merge($existingUserIds, [$team->owner_id])])
                ->andWhere(['status' => User::STATUS_ACTIVE])
                ->limit(10)
                ->all();
        }

        // Wins/top-10 are computed from the real leaderboard, but only for
        // competitions that have actually ended — a live rank isn't a "win" yet.
        $registrations = $team->registrations;
        $challengesWon = 0;
        $hackathonsWon = 0;
        $top10Count = 0;

        foreach ($registrations as $reg) {
            $competitionPost = $reg->competitionPost;
            $competition = $competitionPost->competition;
            if (!$competition->hasEnded()) {
                continue;
            }

            $placement = \app\components\LeaderboardService::placement($competitionPost->id, $competition, 'team', $team->id);
            if ($placement === null) {
                continue; // never submitted
            }

            if ($placement['won']) {
                $competitionPost->type === 'hackathon' ? $hackathonsWon++ : $challengesWon++;
            }
            if ($placement['top10']) {
                $top10Count++;
            }
        }

        $stats = [
            'challenges_participated' => count(array_filter($registrations, fn($r) => $r->competitionPost->type === 'competition')),
            'challenges_won' => $challengesWon,
            'hackathons_participated' => count(array_filter($registrations, fn($r) => $r->competitionPost->type === 'hackathon')),
            'hackathons_won' => $hackathonsWon,
            'top_10_count' => $top10Count,
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
        $team = $this->findActiveTeam($id);
        $this->requireOwner($team, 'Only the team owner can change the team image.');

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

    // ---------- Invites (owner → person) ----------

    /** Owner sends a direct invite to a specific user. Only that user can accept it. */
    public function actionInvite(int $teamId, int $userId)
    {
        $team = $this->findActiveTeam($teamId);
        $this->requireOwner($team, 'Only the team owner can invite members.');

        if ($team->isFull()) {
            Yii::$app->session->setFlash('error', "Team is full ({$team->cap} member cap).");
            return $this->redirect(['manage', 'id' => $teamId]);
        }

        $existing = TeamMembership::find()->where(['team_id' => $teamId, 'user_id' => $userId])->one();
        if ($existing !== null && $existing->invite_status === TeamMembership::INVITE_REQUESTED) {
            Yii::$app->session->setFlash('error', 'This person already asked to join — approve their request under Join Requests.');
            return $this->redirect(['manage', 'id' => $teamId]);
        }
        if ($existing !== null && !in_array($existing->invite_status, [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED], true)) {
            Yii::$app->session->setFlash('error', 'This user already has a pending invite or is already a member.');
            return $this->redirect(['manage', 'id' => $teamId]);
        }

        $membership = $existing ?? new TeamMembership(['team_id' => $teamId, 'user_id' => $userId]);
        $membership->invite_status = TeamMembership::INVITE_INVITED;
        $membership->invited_at = time();
        $membership->responded_at = null;
        $membership->conflict_status = null;
        $membership->note = null;
        $membership->save(false);

        Yii::$app->session->setFlash('success', 'Invite sent.');
        return $this->redirect(['manage', 'id' => $teamId]);
    }

    /** The invited person accepts. */
    public function actionAcceptInvite(int $membershipId)
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $membership = $this->findOwnMembership($membershipId);
        if ($membership->invite_status !== TeamMembership::INVITE_INVITED) {
            throw new ForbiddenHttpException('There is no invite to accept here.');
        }
        if ($membership->team->isArchived()) {
            Yii::$app->session->setFlash('error', 'This team no longer exists.');
            return $this->redirect(['my']);
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

    /** The invited person declines — or cancels their own join request. */
    public function actionDeclineInvite(int $membershipId)
    {
        $membership = $this->findOwnMembership($membershipId);
        if (!$membership->isOpen()) {
            throw new ForbiddenHttpException('Nothing pending to decline here.');
        }

        $wasRequest = $membership->invite_status === TeamMembership::INVITE_REQUESTED;
        $membership->invite_status = TeamMembership::INVITE_DECLINED;
        $membership->responded_at = time();
        $membership->save(false);

        Yii::$app->session->setFlash('success', $wasRequest ? 'Request cancelled.' : 'Invite declined.');
        return $this->redirect(['my']);
    }

    // ---------- Join requests (person → owner) ----------

    /** Someone not on the team asks to join — optionally with a note. Only the owner can approve it. */
    public function actionRequestJoin(int $id, ?int $competitionId = null)
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $team = $this->findActiveTeam($id);
        $userId = (int) Yii::$app->user->id;
        $note = trim((string) Yii::$app->request->post('note', ''));

        $existing = TeamMembership::find()->where(['team_id' => $id, 'user_id' => $userId])->one();
        if ($existing !== null && $existing->invite_status === TeamMembership::INVITE_INVITED) {
            Yii::$app->session->setFlash('error', 'You already have an invite from this team — accept it under My Teams.');
            return $this->redirect($this->backTarget($competitionId, $id));
        }
        if ($existing !== null && !in_array($existing->invite_status, [TeamMembership::INVITE_LEFT, TeamMembership::INVITE_DECLINED], true)) {
            Yii::$app->session->setFlash('error', 'You already have a pending request or are already a member.');
            return $this->redirect($this->backTarget($competitionId, $id));
        }

        if ($team->isFull()) {
            Yii::$app->session->setFlash('error', "This team is full ({$team->cap} member cap).");
            return $this->redirect($this->backTarget($competitionId, $id));
        }

        // Only ONE membership row per person per team (unique index), so a
        // second request after leaving/declining reuses that row.
        $membership = $existing ?? new TeamMembership(['team_id' => $id, 'user_id' => $userId]);
        $membership->invite_status = TeamMembership::INVITE_REQUESTED;
        $membership->invited_at = time();
        $membership->responded_at = null;
        $membership->conflict_status = null;
        $membership->note = $note !== '' ? $note : null;
        $membership->save(false);

        Yii::$app->session->setFlash('success', "Request sent to join \"{$team->name}\" — waiting on the team owner.");
        return $this->redirect($this->backTarget($competitionId, $id));
    }

    /** Owner approves a join request. */
    public function actionApproveMember(int $membershipId)
    {
        $membership = $this->findMembership($membershipId);
        $team = $this->findActiveTeam($membership->team_id);
        $this->requireOwner($team, 'Only the team owner can approve requests.');

        if ($membership->invite_status !== TeamMembership::INVITE_REQUESTED) {
            throw new ForbiddenHttpException('Only join requests can be approved — invites are accepted by the person invited.');
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

    /** Owner declines a join request — or cancels an invite they sent. */
    public function actionDeclineMember(int $membershipId)
    {
        $membership = $this->findMembership($membershipId);
        $team = $this->findActiveTeam($membership->team_id);
        $this->requireOwner($team, 'Only the team owner can do this.');

        if (!$membership->isOpen()) {
            throw new ForbiddenHttpException('Nothing pending to decline here.');
        }

        $wasInvite = $membership->invite_status === TeamMembership::INVITE_INVITED;
        $membership->invite_status = TeamMembership::INVITE_DECLINED;
        $membership->responded_at = time();
        $membership->save(false);

        Yii::$app->session->setFlash('success', $wasInvite ? 'Invite cancelled.' : 'Request declined.');
        return $this->redirect(['manage', 'id' => $team->id]);
    }

    // ---------- Leaving, removing, ownership ----------

    /** Owner removes an existing member. They also come off any line-up whose registration is still open. */
    public function actionRemoveMember(int $membershipId)
    {
        $membership = $this->findMembership($membershipId);
        $team = $this->findActiveTeam($membership->team_id);
        $this->requireOwner($team, 'Only the team owner can remove members.');

        if ($membership->invite_status !== TeamMembership::INVITE_CONSENTED) {
            throw new ForbiddenHttpException('That person is not a member.');
        }
        if ((int) $membership->user_id === (int) $team->owner_id) {
            Yii::$app->session->setFlash('error', 'You can\'t remove yourself — hand ownership to another member first, then leave.');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        if ($error = $this->dropFromOpenLineups($team, (int) $membership->user_id, $membership->user->username ?? 'This member')) {
            Yii::$app->session->setFlash('error', $error);
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        $membership->invite_status = TeamMembership::INVITE_LEFT;
        $membership->responded_at = time();
        $membership->save(false);

        Yii::$app->session->setFlash('success', 'Member removed.');
        return $this->redirect(['manage', 'id' => $team->id]);
    }

    /**
     * A member leaves. The owner can only leave once they've handed ownership
     * to someone else — unless they're the last member, in which case the team
     * is archived (kept as history under Past teams).
     */
    public function actionLeave(int $id)
    {
        $team = $this->findActiveTeam($id);
        $userId = (int) Yii::$app->user->id;

        $membership = TeamMembership::find()
            ->where(['team_id' => $team->id, 'user_id' => $userId, 'invite_status' => TeamMembership::INVITE_CONSENTED])
            ->one();
        if ($membership === null) {
            throw new ForbiddenHttpException('You are not a member of this team.');
        }

        $isOwner = (int) $team->owner_id === $userId;
        $otherMembers = $team->getConsentedMemberCount() - 1;

        if ($isOwner && $otherMembers > 0) {
            Yii::$app->session->setFlash('error', 'Hand ownership to another member before leaving (Members tab → Make owner).');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        if ($error = $this->dropFromOpenLineups($team, $userId, 'You')) {
            Yii::$app->session->setFlash('error', $error);
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        $transaction = Team::getDb()->beginTransaction();
        try {
            $membership->invite_status = TeamMembership::INVITE_LEFT;
            $membership->responded_at = time();
            $membership->save(false);

            if ($otherMembers === 0) {
                // Last one out: archive instead of deleting, and close anything still pending.
                TeamMembership::updateAll(
                    ['invite_status' => TeamMembership::INVITE_DECLINED, 'responded_at' => time()],
                    ['team_id' => $team->id, 'invite_status' => [TeamMembership::INVITE_INVITED, TeamMembership::INVITE_REQUESTED]]
                );
                $team->archived_at = time();
                $team->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not leave the team — please try again.');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        Yii::$app->session->setFlash('success', $otherMembers === 0
            ? "You left \"{$team->name}\". It had no other members, so it's now archived under Past teams."
            : "You left \"{$team->name}\".");
        return $this->redirect(['my']);
    }

    /** Owner hands ownership to another current member. */
    public function actionTransferOwnership(int $id, int $userId)
    {
        $team = $this->findActiveTeam($id);
        $this->requireOwner($team, 'Only the team owner can hand over ownership.');

        if ($userId === (int) $team->owner_id || !$team->isMember($userId)) {
            Yii::$app->session->setFlash('error', 'Ownership can only go to another current member.');
            return $this->redirect(['manage', 'id' => $team->id]);
        }

        $team->owner_id = $userId;
        $team->save(false);

        $newOwner = User::findOne($userId);
        Yii::$app->session->setFlash('success', ($newOwner->username ?? 'They') . ' is now the team owner.');
        return $this->redirect(['manage', 'id' => $team->id]);
    }

    // ---------- Competition line-ups ----------

    /** Owner adds a current member to a competition line-up (only while that competition's registration is open). */
    public function actionLineupAdd(int $registrationId, int $userId)
    {
        $registration = $this->findRegistration($registrationId);
        $team = $this->findActiveTeam($registration->team_id);
        $this->requireOwner($team, 'Only the team owner can change a line-up.');
        $competition = $registration->competitionPost->competition;
        $back = ['manage', 'id' => $team->id, '#' => 'competitions'];

        if ($registration->isLineupLocked()) {
            Yii::$app->session->setFlash('error', 'The line-up is locked — ' . $registration->lockReason() . '.');
            return $this->redirect($back);
        }
        if (!$team->isMember($userId)) {
            Yii::$app->session->setFlash('error', 'Only current team members can be added to a line-up.');
            return $this->redirect($back);
        }
        if (in_array($userId, $registration->getLineupUserIds(), true)) {
            Yii::$app->session->setFlash('error', 'Already on this line-up.');
            return $this->redirect($back);
        }
        if ($competition->team_size_limit !== null && $registration->getLineupCount() + 1 > (int) $competition->team_size_limit) {
            Yii::$app->session->setFlash('error', "This line-up is already at the competition's maximum of {$competition->team_size_limit}.");
            return $this->redirect($back);
        }
        $user = User::findOne($userId);
        if (EntryService::hasEntry($registration->competition_id, $userId, $registration->id)) {
            Yii::$app->session->setFlash('error', ($user->username ?? 'This member') . ' is already entered in this competition (as an individual or on another team). They need to withdraw from that entry first.');
            return $this->redirect($back);
        }
        if ($user !== null && $user->isRestrictedStudent()) {
            Yii::$app->session->setFlash('error', "{$user->username} hasn't verified their student ID yet.");
            return $this->redirect($back);
        }

        (new TeamCompetitionMember([
            'registration_id' => $registration->id,
            'user_id' => $userId,
            'added_at' => time(),
        ]))->save(false);

        Yii::$app->session->setFlash('success', ($user->username ?? 'Member') . " added to the line-up for \"{$registration->competitionPost->title}\".");
        return $this->redirect($back);
    }

    /** Owner takes someone off a line-up, or a member drops themselves (only while registration is open). */
    public function actionLineupRemove(int $registrationId, int $userId)
    {
        $registration = $this->findRegistration($registrationId);
        $team = $this->findActiveTeam($registration->team_id);
        $me = (int) Yii::$app->user->id;
        $isOwner = (int) $team->owner_id === $me;
        $back = $isOwner || $team->isMember($me) ? ['manage', 'id' => $team->id, '#' => 'competitions'] : ['my'];

        if (!$isOwner && $userId !== $me) {
            throw new ForbiddenHttpException('Only the team owner can take other people off a line-up.');
        }

        $competition = $registration->competitionPost->competition;
        if ($registration->isLineupLocked()) {
            Yii::$app->session->setFlash('error', 'The line-up is locked — ' . $registration->lockReason() . '.');
            return $this->redirect($back);
        }

        $row = TeamCompetitionMember::findOne(['registration_id' => $registration->id, 'user_id' => $userId]);
        if ($row === null) {
            Yii::$app->session->setFlash('error', 'That person is not on this line-up.');
            return $this->redirect($back);
        }

        $newCount = $registration->getLineupCount() - 1;
        if ($newCount === 0 || ($competition->team_size_min !== null && $newCount < (int) $competition->team_size_min)) {
            $min = max(1, (int) $competition->team_size_min);
            Yii::$app->session->setFlash('error', "The line-up can't drop below {$min}. Add someone else first, or withdraw the whole entry from the competition page.");
            return $this->redirect($back);
        }

        $row->delete();
        Yii::$app->session->setFlash('success', $userId === $me ? 'You dropped out of that line-up.' : 'Removed from the line-up.');
        return $this->redirect($back);
    }

    // ---------- Lists ----------

    public function actionMy()
    {
        $userId = Yii::$app->user->id;

        $memberships = TeamMembership::find()
            ->alias('m')
            ->innerJoinWith('team t')
            ->where(['m.user_id' => $userId, 'm.invite_status' => TeamMembership::INVITE_CONSENTED])
            ->andWhere(['t.archived_at' => null])
            ->all();

        $pendingInvites = TeamMembership::find()
            ->alias('m')
            ->innerJoinWith('team t')
            ->where(['m.user_id' => $userId, 'm.invite_status' => TeamMembership::INVITE_INVITED])
            ->andWhere(['t.archived_at' => null])
            ->all();

        $myRequests = TeamMembership::find()
            ->alias('m')
            ->innerJoinWith('team t')
            ->where(['m.user_id' => $userId, 'm.invite_status' => TeamMembership::INVITE_REQUESTED])
            ->andWhere(['t.archived_at' => null])
            ->all();

        // Past teams: ones I left/was removed from, plus archived teams.
        $pastTeams = TeamMembership::find()
            ->alias('m')
            ->innerJoinWith('team t')
            ->where(['m.user_id' => $userId])
            ->andWhere(['or',
                ['m.invite_status' => TeamMembership::INVITE_LEFT],
                ['and', ['m.invite_status' => TeamMembership::INVITE_CONSENTED], ['not', ['t.archived_at' => null]]],
            ])
            ->orderBy(['m.responded_at' => SORT_DESC])
            ->all();

        return $this->render('my', [
            'memberships' => $memberships,
            'pendingInvites' => $pendingInvites,
            'myRequests' => $myRequests,
            'pastTeams' => $pastTeams,
        ]);
    }

    // ---------- Helpers ----------

    /**
     * Before someone leaves or is removed:
     *  - If they're on a LOCKED line-up of a competition that hasn't ended, they can't
     *    leave yet (Kaggle-style: you're committed to that entry until it's over).
     *  - Otherwise they come off every line-up that can still change. Returns an error
     *    if that would push a line-up below its minimum, null if done.
     */
    private function dropFromOpenLineups(Team $team, int $userId, string $who): ?string
    {
        $rows = TeamCompetitionMember::find()
            ->alias('m')
            ->innerJoin(TeamCompetitionRegistration::tableName() . ' r', 'r.id = m.registration_id')
            ->where(['r.team_id' => $team->id, 'm.user_id' => $userId])
            ->all();

        $toDrop = [];
        foreach ($rows as $row) {
            $registration = $row->registration;
            $competition = $registration->competitionPost->competition;
            $title = $registration->competitionPost->title;
            if ($registration->isLineupLocked()) {
                if ($competition->hasEnded()) {
                    continue; // finished competition — the entry is history, nothing to protect
                }
                $until = empty($competition->deadline) ? 'it ends' : date('M j, Y H:i', strtotime($competition->deadline));
                return "{$who} " . ($who === 'You' ? 'are' : 'is') . " on the locked line-up for \"{$title}\" and can't leave the team until that competition ends ({$until}).";
            }
            $newCount = $registration->getLineupCount() - 1;
            $min = max(1, (int) $competition->team_size_min);
            if ($newCount < $min) {
                return "{$who} " . ($who === 'You' ? 'are' : 'is') . " on the line-up for \"{$title}\", which needs at least {$min}. "
                    . 'Add someone else to that line-up first, or withdraw the entry.';
            }
            $toDrop[] = $row;
        }

        foreach ($toDrop as $row) {
            $row->delete();
        }
        return null;
    }

    private function backTarget(?int $competitionId, int $teamId): array
    {
        return $competitionId !== null
            ? ['/competition/teams', 'id' => $competitionId]
            : ['my'];
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

    private function requireOwner(Team $team, string $message): void
    {
        if ((int) $team->owner_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException($message);
        }
    }

    private function findTeam(int $id): Team
    {
        $team = Team::findOne($id);
        if ($team === null) {
            throw new NotFoundHttpException('Team not found.');
        }
        return $team;
    }

    /** Same as findTeam, but archived teams are read-only. */
    private function findActiveTeam(int $id): Team
    {
        $team = $this->findTeam($id);
        if ($team->isArchived()) {
            throw new ForbiddenHttpException('This team is archived and can no longer be changed.');
        }
        return $team;
    }

    private function findMembership(int $id): TeamMembership
    {
        $membership = TeamMembership::findOne($id);
        if ($membership === null) {
            throw new NotFoundHttpException('Not found.');
        }
        return $membership;
    }

    /** A membership row that belongs to the logged-in user. */
    private function findOwnMembership(int $id): TeamMembership
    {
        $membership = $this->findMembership($id);
        if ((int) $membership->user_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Not yours.');
        }
        return $membership;
    }

    private function findRegistration(int $id): TeamCompetitionRegistration
    {
        $registration = TeamCompetitionRegistration::findOne($id);
        if ($registration === null) {
            throw new NotFoundHttpException('Competition entry not found.');
        }
        return $registration;
    }
}
