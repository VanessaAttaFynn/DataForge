<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Notebook;
use app\models\Tag;
use app\models\Vote;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

class NotebookController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'view', 'download'], 'roles' => ['?', '@']],
                    ['allow' => true, 'actions' => ['create', 'update', 'delete', 'mine', 'upvote', 'request-review'], 'roles' => ['@']],
                ],
            ],
        ];
    }

    /** Browse — sort by date or votes, filter by topic + verified status. Tags are display-only, not filterable. */
    public function actionIndex(string $q = '', string $topic = '', string $sort = 'date', string $verified = '')
    {
        $query = Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_NOTEBOOK])
            ->innerJoinWith('notebook');

        if (trim($q) !== '') {
            $query->andWhere(['like', '{{%post}}.title', $q]);
        }
        if (trim($topic) !== '') {
            $query->andWhere(['like', '{{%notebook}}.topic', $topic]);
        }
        if ($verified === 'verified') {
            $query->andWhere(['{{%post}}.verified' => 1]);
        } elseif ($verified === 'unverified') {
            $query->andWhere(['{{%post}}.verified' => 0]);
        }

        $posts = $query->all();

        $voteCounts = [];
        foreach ($posts as $post) {
            $voteCounts[$post->id] = Vote::countFor($post->id);
        }

        if ($sort === 'votes') {
            usort($posts, fn($a, $b) => $voteCounts[$b->id] <=> $voteCounts[$a->id]);
        } else {
            usort($posts, fn($a, $b) => $b->created_at <=> $a->created_at);
        }

        $topics = Notebook::find()->select('topic')->distinct()->column();

        return $this->render('index', [
            'posts' => $posts, 'topics' => $topics, 'q' => $q, 'topic' => $topic, 'sort' => $sort, 'verified' => $verified, 'voteCounts' => $voteCounts,
        ]);
    }

    /** My notebooks — published (filterable, same as the public index) and rejected. */
    public function actionMine(string $q = '', string $topic = '', string $sort = 'date', string $verified = '')
    {
        $userId = Yii::$app->user->id;

        $query = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_NOTEBOOK])
            ->innerJoinWith('notebook');

        if (trim($q) !== '') {
            $query->andWhere(['like', '{{%post}}.title', $q]);
        }
        if (trim($topic) !== '') {
            $query->andWhere(['like', '{{%notebook}}.topic', $topic]);
        }
        if ($verified === 'verified') {
            $query->andWhere(['{{%post}}.verified' => 1]);
        } elseif ($verified === 'unverified') {
            $query->andWhere(['{{%post}}.verified' => 0]);
        }

        $published = $query->all();

        $voteCounts = [];
        foreach ($published as $post) {
            $voteCounts[$post->id] = Vote::countFor($post->id);
        }
        if ($sort === 'votes') {
            usort($published, fn($a, $b) => $voteCounts[$b->id] <=> $voteCounts[$a->id]);
        } else {
            usort($published, fn($a, $b) => $b->created_at <=> $a->created_at);
        }

        $rejected = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_REJECTED, 'type' => Post::TYPE_NOTEBOOK])
            ->orderBy(['created_at' => SORT_DESC])->all();

        $pendingReview = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_PENDING, 'type' => Post::TYPE_NOTEBOOK])
            ->orderBy(['created_at' => SORT_DESC])->all();

        $rejectionReasons = [];
        foreach ($rejected as $post) {
            $historyRow = \app\models\PostStatusHistory::find()
                ->where(['post_id' => $post->id, 'new_status' => Post::STATUS_REJECTED])
                ->orderBy(['created_at' => SORT_DESC])->one();
            $rejectionReasons[$post->id] = $historyRow->note ?? null;
        }

        $topics = Notebook::find()->select('topic')->distinct()->column();

        return $this->render('mine', [
            'published' => $published, 'pendingReview' => $pendingReview, 'rejected' => $rejected, 'rejectionReasons' => $rejectionReasons,
            'topics' => $topics, 'q' => $q, 'topic' => $topic, 'sort' => $sort, 'verified' => $verified, 'voteCounts' => $voteCounts,
        ]);
    }

    public function actionView(int $id)
    {
        $post = $this->findPost($id);
        $canManage = (int) Yii::$app->user->id === (int) $post->author_id || Yii::$app->user->can('moderateContent');
        $tags = Tag::forPost($post->id);

        $html = '';
        if ($post->notebook->notebook_file_path) {
            $fullPath = Yii::getAlias('@webroot') . $post->notebook->notebook_file_path;
            $html = \app\components\NotebookRenderer::renderFile($fullPath);
        }

        return $this->render('view', [
            'post' => $post, 'notebook' => $post->notebook, 'canManage' => $canManage, 'tags' => $tags, 'renderedHtml' => $html,
            'voteCount' => Vote::countFor($post->id),
            'hasVoted' => !Yii::$app->user->isGuest && Vote::hasVoted($post->id, Yii::$app->user->id),
        ]);
    }

    public function actionUpvote(int $id)
    {
        $post = $this->findPost($id);
        Vote::toggle($post->id, Yii::$app->user->id);
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Streams the .ipynb file through PHP — same pattern as dataset/competition downloads, avoids IIS static-serving issues. */
    public function actionDownload(int $id)
    {
        $post = $this->findPost($id);
        $notebook = $post->notebook;

        if (empty($notebook->notebook_file_path)) {
            throw new NotFoundHttpException('No notebook file for this post.');
        }

        $fullPath = Yii::getAlias('@webroot') . $notebook->notebook_file_path;
        if (!file_exists($fullPath)) {
            throw new NotFoundHttpException('Notebook file is missing on the server.');
        }

        return Yii::$app->response->sendFile($fullPath);
    }

    /** Owner asks a moderator to verify an already-published, unverified notebook — doesn't touch visibility/status. */
    public function actionRequestReview(int $id)
    {
        $post = $this->findPost($id);
        $notebook = $post->notebook;

        if ((int) $post->author_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the owner can request a review.');
        }
        if ($post->verified) {
            Yii::$app->session->setFlash('error', 'This notebook is already verified.');
            return $this->redirect(['view', 'id' => $id]);
        }
        if ($notebook->verification_requested_at !== null) {
            Yii::$app->session->setFlash('error', 'A review is already pending for this notebook.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $notebook->verification_requested_at = time();
        $notebook->save(false);

        Yii::$app->session->setFlash('success', 'Review requested — a moderator will take a look.');
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionCreate()
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $post = new Post();
        $notebook = new Notebook();

        // Contribute flow from a competition/hackathon page — prefill the link.
        $linkedId = Yii::$app->request->get('linked_id');
        if ($linkedId !== null) {
            $notebook->linked_post_id = (int) $linkedId;
        }

        if (Yii::$app->request->isPost) {
            [$post, $notebook, $success] = $this->saveFromRequest($post, $notebook, true);
            if ($success) {
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('create', ['post' => $post, 'notebook' => $notebook, 'tagsValue' => ''] + $this->associationOptions());
    }

    public function actionUpdate(int $id)
    {
        $post = $this->findPost($id);
        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only edit your own notebooks.');
        }
        $notebook = $post->notebook;
        $currentTags = implode(', ', array_map(fn($t) => $t->name, Tag::forPost($post->id)));

        if (Yii::$app->request->isPost) {
            $wasRejected = $post->status === Post::STATUS_REJECTED;

            [$post, $notebook, $success] = $this->saveFromRequest($post, $notebook, false);

            if ($success) {
                if ($wasRejected) {
                    $post->status = Post::STATUS_PENDING;
                    $post->save(false);
                    Yii::$app->session->setFlash('success', "\"{$post->title}\" was resubmitted for review.");
                }
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('update', ['post' => $post, 'notebook' => $notebook, 'tagsValue' => $currentTags] + $this->associationOptions());
    }

    /** Published datasets/competitions/hackathons, for the "associate with" dropdowns. */
    private function associationOptions(): array
    {
        return [
            'datasets' => Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_DATASET])->all(),
            'competitions' => Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_COMPETITION])->all(),
            'hackathons' => Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_HACKATHON])->all(),
        ];
    }

    public function actionDelete(int $id)
    {
        $post = $this->findPost($id);
        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only delete your own notebooks.');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('{{%vote}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post_tag}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%comment}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%bookmark}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post_status_history}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%notebook}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post}}', ['id' => $post->id])->execute();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Notebook deleted.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not delete — check the logs.');
        }

        return $this->redirect(['index']);
    }

    private function saveFromRequest(Post $post, Notebook $notebook, bool $isNew): array
    {
        $postData = Yii::$app->request->post('Post', []);
        $notebookData = Yii::$app->request->post('Notebook', []);
        $tagsInput = Yii::$app->request->post('tags', '');
        $submitForReview = Yii::$app->request->post('submit_for_review') === '1';

        $post->type = Post::TYPE_NOTEBOOK;
        $post->title = $postData['title'] ?? $post->title;
        $post->body = $postData['body'] ?? $post->body;

        if ($isNew) {
            $post->slug = $this->slugify($post->title) . '-' . substr(uniqid(), -6);
            $post->author_id = Yii::$app->user->id;
        }

        $notebook->attributes = $notebookData;

        $postValid = $post->validate();
        $notebookValid = $notebook->validate(['topic', 'linked_post_id']);
        $isValid = $postValid && $notebookValid;
        $success = false;

        if (!$isValid) {
            $errors = array_merge($post->getFirstErrors(), $notebook->getFirstErrors());
            Yii::$app->session->setFlash('error', 'Please fix the following: ' . implode(' · ', $errors));
        }

        if ($isValid) {
            $transaction = Post::getDb()->beginTransaction();
            try {
                if ($isNew) {
                    // Creator's choice, same as datasets: submit for review
                    // (pending, becomes verified once approved) or publish
                    // now as unverified.
                    $post->status = $submitForReview ? Post::STATUS_PENDING : Post::STATUS_PUBLISHED;
                    $post->verified = false;
                }
                if (!$post->save(false)) {
                    throw new \RuntimeException('Failed to save post.');
                }

                $notebookFile = UploadedFile::getInstanceByName('notebook_file');
                if ($notebookFile !== null) {
                    $dir = Yii::getAlias('@webroot/uploads/notebooks');
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $filename = $post->id . '.ipynb';
                    $notebookFile->saveAs("$dir/$filename");
                    $notebook->notebook_file_path = "/uploads/notebooks/$filename";
                } elseif ($isNew) {
                    throw new \RuntimeException('A .ipynb file is required.');
                }

                $notebook->post_id = $post->id;
                if (!$notebook->save(false)) {
                    throw new \RuntimeException('Failed to save notebook details.');
                }

                $tagIds = Tag::resolveNames($tagsInput);
                Tag::syncPostTags($post->id, $tagIds);

                $transaction->commit();
                $success = true;

                Yii::$app->session->setFlash('success', $isNew && $submitForReview
                    ? "\"{$post->title}\" was submitted and is now awaiting approval."
                    : "\"{$post->title}\" saved.");
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Save failed: ' . $e->getMessage());
            }
        }

        return [$post, $notebook, $success];
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

    private function findPost(int $id): Post
    {
        $post = Post::find()->where(['id' => $id, 'type' => Post::TYPE_NOTEBOOK])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Notebook not found.');
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