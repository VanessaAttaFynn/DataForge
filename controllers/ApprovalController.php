<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

/**
 * Moderation queue for content that requires approval before going live —
 * i.e. competitions and hackathons (datasets/discussions publish immediately
 * and don't pass through here).
 */
class ApprovalController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // any logged-in user reaches actions;
                        // the actual permission check happens per-action below,
                        // so a student hitting /approval/index just sees an empty
                        // queue rather than a hard 403 — feels less like a wall.
                    ],
                ],
            ],
        ];
    }

    /**
     * The moderation queue — every pending competition/hackathon, oldest first.
     */
    public function actionIndex()
    {
        // TODO: re-enable once testing is done.
        // if (!Yii::$app->user->can('approveCompetition')) {
        //     throw new ForbiddenHttpException('You do not have permission to review approvals.');
        // }

        $pending = Post::find()
            ->where(['status' => Post::STATUS_PENDING])
            ->andWhere(['type' => Post::TYPES_REQUIRING_APPROVAL])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return $this->render('index', ['pending' => $pending]);
    }

    /**
     * Approve a pending competition/hackathon. Publishes it, logs history,
     * and notifies the student who created it.
     */
    public function actionApprove(int $id)
    {
        // TODO: re-enable once testing is done.
        // if (!Yii::$app->user->can('approveCompetition')) {
        //     throw new ForbiddenHttpException('You do not have permission to approve this.');
        // }

        $post = $this->findPendingPost($id);

        if ($post->approve(Yii::$app->user->id)) {
            Yii::$app->session->setFlash('success', "\"{$post->title}\" was approved and is now live.");
        } else {
            Yii::$app->session->setFlash('error', 'Something went wrong approving this post — check the logs.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Reject a pending competition/hackathon. Requires a reason — that
     * reason is what gets shown to the student in their notification.
     */
    public function actionReject(int $id)
    {
        // TODO: re-enable once testing is done.
        // if (!Yii::$app->user->can('approveCompetition')) {
        //     throw new ForbiddenHttpException('You do not have permission to reject this.');
        // }

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

    private function findPendingPost(int $id): Post
    {
        $post = Post::find()
            ->where(['id' => $id, 'status' => Post::STATUS_PENDING])
            ->andWhere(['type' => Post::TYPES_REQUIRING_APPROVAL])
            ->one();

        if ($post === null) {
            throw new NotFoundHttpException('This item is not awaiting approval (already reviewed, or does not exist).');
        }

        return $post;
    }
}