<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Notification;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * Moderation queue for content that requires approval before going live
 * (competitions, hackathons), plus two review-request queues for content
 * that's already live but wants a verified badge (datasets, notebooks).
 */
class ApprovalController extends Controller
{
    const REVIEWABLE_TYPES = [Post::TYPE_DATASET, Post::TYPE_NOTEBOOK];

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

    public function actionIndex()
    {
        if (!Yii::$app->user->can('approveCompetition')) {
            throw new ForbiddenHttpException('You do not have permission to review approvals.');
        }

        $pending = Post::find()
            ->where(['status' => Post::STATUS_PENDING])
            ->andWhere(['type' => array_merge(Post::TYPES_REQUIRING_APPROVAL, self::REVIEWABLE_TYPES)])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        // Already-published dataset/notebook posts whose owner asked for
        // verification — never touches status/visibility, just sets verified.
        $datasetRequests = Post::find()
            ->where(['{{%post}}.status' => Post::STATUS_PUBLISHED, '{{%post}}.type' => Post::TYPE_DATASET, '{{%post}}.verified' => 0])
            ->innerJoinWith('dataset')
            ->andWhere(['not', ['{{%dataset}}.verification_requested_at' => null]])
            ->orderBy(['{{%dataset}}.verification_requested_at' => SORT_ASC])
            ->all();

        $notebookRequests = Post::find()
            ->where(['{{%post}}.status' => Post::STATUS_PUBLISHED, '{{%post}}.type' => Post::TYPE_NOTEBOOK, '{{%post}}.verified' => 0])
            ->innerJoinWith('notebook')
            ->andWhere(['not', ['{{%notebook}}.verification_requested_at' => null]])
            ->orderBy(['{{%notebook}}.verification_requested_at' => SORT_ASC])
            ->all();

        $verificationRequests = array_merge($datasetRequests, $notebookRequests);

        $studentRequests = User::find()
            ->where(['student_verification_status' => User::STUDENT_VERIFICATION_PENDING])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return $this->render('index', ['pending' => $pending, 'verificationRequests' => $verificationRequests, 'studentRequests' => $studentRequests]);
    }

    public function actionApprove(int $id)
    {
        if (!Yii::$app->user->can('approveCompetition')) {
            throw new ForbiddenHttpException('You do not have permission to approve this.');
        }

        $post = $this->findPendingPost($id);

        if ($post->approve(Yii::$app->user->id)) {
            Yii::$app->session->setFlash('success', "\"{$post->title}\" was approved and is now live.");
        } else {
            Yii::$app->session->setFlash('error', 'Something went wrong approving this post — check the logs.');
        }

        return $this->redirect(['index']);
    }

    public function actionReject(int $id)
    {
        if (!Yii::$app->user->can('approveCompetition')) {
            throw new ForbiddenHttpException('You do not have permission to reject this.');
        }

        $post = $this->findPendingPost($id);
        $note = Yii::$app->request->post('note', '');

        if (trim($note) === '') {
            Yii::$app->session->setFlash('error', 'A reason is required when rejecting a submission.');
            return $this->redirect(['index']);
        }

        if ($post->reject(Yii::$app->user->id, $note)) {
            Yii::$app->session->setFlash('success', "\"{$post->title}\" was rejected.");
        } else {
            Yii::$app->session->setFlash('error', 'Something went wrong rejecting this post — check the logs.');
        }

        return $this->redirect(['index']);
    }

    /** Approve a dataset/notebook's standalone verification request — sets verified, doesn't touch status. */
    public function actionApproveVerification(int $id)
    {
        if (!Yii::$app->user->can('verifyDataset')) {
            throw new ForbiddenHttpException('You do not have permission to verify content.');
        }

        $post = Post::find()->where(['id' => $id, 'type' => self::REVIEWABLE_TYPES, 'verified' => 0])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Not found or already verified.');
        }

        $extension = $post->type === Post::TYPE_DATASET ? $post->dataset : $post->notebook;

        $post->verified = true;
        $post->save(false);
        $extension->verification_requested_at = null;
        $extension->save(false);

        $notification = new Notification([
            'user_id' => $post->author_id,
            'type' => $post->type . '_verified',
            'message' => "Your {$post->type} \"{$post->title}\" was verified.",
            'link' => "/{$post->type}/view?id={$post->id}",
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $notification->save();

        Yii::$app->session->setFlash('success', "\"{$post->title}\" is now verified.");
        return $this->redirect(['index']);
    }

    /** Decline a verification request — stays published, just clears the request. */
    public function actionDeclineVerification(int $id)
    {
        if (!Yii::$app->user->can('verifyDataset')) {
            throw new ForbiddenHttpException('You do not have permission to verify content.');
        }

        $post = Post::find()->where(['id' => $id, 'type' => self::REVIEWABLE_TYPES, 'verified' => 0])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Not found or already verified.');
        }

        $extension = $post->type === Post::TYPE_DATASET ? $post->dataset : $post->notebook;

        $note = Yii::$app->request->post('note', '');
        $extension->verification_requested_at = null;
        $extension->save(false);

        $notification = new Notification([
            'user_id' => $post->author_id,
            'type' => $post->type . '_verification_declined',
            'message' => "Your verification request for \"{$post->title}\" was declined." . ($note ? " Reason: {$note}" : ''),
            'link' => "/{$post->type}/view?id={$post->id}",
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $notification->save();

        Yii::$app->session->setFlash('success', 'Verification request declined.');
        return $this->redirect(['index']);
    }

    private function findPendingPost(int $id): Post
    {
        $post = Post::find()
            ->where(['id' => $id, 'status' => Post::STATUS_PENDING])
            ->andWhere(['type' => array_merge(Post::TYPES_REQUIRING_APPROVAL, self::REVIEWABLE_TYPES)])
            ->one();

        if ($post === null) {
            throw new NotFoundHttpException('This item is not awaiting approval (already reviewed, or does not exist).');
        }

        return $post;
    }

    /** Approve a student's ID + proof-of-registration submission. */
    public function actionApproveStudent(int $id)
    {
        if (!Yii::$app->user->can('approveCompetition')) { // moderators (lecturers) and admins
            throw new ForbiddenHttpException('You do not have permission to verify students.');
        }

        $user = User::find()->where(['id' => $id, 'student_verification_status' => User::STUDENT_VERIFICATION_PENDING])->one();
        if ($user === null) {
            throw new NotFoundHttpException('Not found or not pending.');
        }

        $user->student_verification_status = User::STUDENT_VERIFICATION_APPROVED;
        $user->student_verification_note = null;
        $user->save(false);

        $notification = new Notification([
            'user_id' => $user->id,
            'type' => 'student_verified',
            'message' => 'Your student verification was approved — your account is now fully verified.',
            'link' => '/user/profile',
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $notification->save();

        Yii::$app->session->setFlash('success', "{$user->username} is now a verified student.");
        return $this->redirect(['index']);
    }

    /** Reject a student's submission — they can resubmit with corrected info/document. */
    public function actionRejectStudent(int $id)
    {
        if (!Yii::$app->user->can('approveCompetition')) { // moderators (lecturers) and admins
            throw new ForbiddenHttpException('You do not have permission to verify students.');
        }

        $user = User::find()->where(['id' => $id, 'student_verification_status' => User::STUDENT_VERIFICATION_PENDING])->one();
        if ($user === null) {
            throw new NotFoundHttpException('Not found or not pending.');
        }

        $note = Yii::$app->request->post('note', '');
        if (trim($note) === '') {
            Yii::$app->session->setFlash('error', 'A reason is required when rejecting a student verification.');
            return $this->redirect(['index']);
        }

        $user->student_verification_status = User::STUDENT_VERIFICATION_REJECTED;
        $user->student_verification_note = $note;
        $user->save(false);

        $notification = new Notification([
            'user_id' => $user->id,
            'type' => 'student_verification_rejected',
            'message' => "Your student verification was rejected. Reason: {$note}",
            'link' => '/user/profile',
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $notification->save();

        Yii::$app->session->setFlash('success', 'Rejected — the student can resubmit.');
        return $this->redirect(['index']);
    }
}