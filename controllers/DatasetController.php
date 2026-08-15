<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Dataset;
use app\models\Vote;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;

class DatasetController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['index', 'view', 'download'], 'roles' => ['?', '@']],
                    ['allow' => true, 'actions' => ['create', 'update', 'delete', 'upvote', 'request-review', 'mine'], 'roles' => ['@']],
                ],
            ],
        ];
    }

    /** Browse — sort by date or votes, filter by topic, search by title. No tag filter (datasets don't use tags). */
    public function actionIndex(string $q = '', string $topic = '', string $sort = 'date', string $verified = '')
    {
        $query = Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_DATASET])
            ->innerJoinWith('dataset');

        if (trim($q) !== '') {
            $query->andWhere(['like', '{{%post}}.title', $q]);
        }
        if (trim($topic) !== '') {
            $query->andWhere(['like', '{{%dataset}}.topic', $topic]);
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

        $topics = Dataset::find()->select('topic')->distinct()->column();

        return $this->render('index', [
            'posts' => $posts, 'topics' => $topics, 'q' => $q, 'topic' => $topic, 'sort' => $sort, 'verified' => $verified, 'voteCounts' => $voteCounts,
        ]);
    }

    public function actionView(int $id)
    {
        $post = $this->findPost($id);
        $dataset = $post->dataset;
        $canManage = (int) Yii::$app->user->id === (int) $post->author_id || Yii::$app->user->can('moderateContent');

        $associatedNotebooks = Post::find()
            ->where(['type' => Post::TYPE_NOTEBOOK, 'status' => Post::STATUS_PUBLISHED])
            ->andWhere(['in', 'id', (new \yii\db\Query())->select('post_id')->from('{{%notebook}}')->where(['linked_post_id' => $post->id])])
            ->all();

        return $this->render('view', [
            'post' => $post,
            'dataset' => $dataset,
            'canManage' => $canManage,
            'preview' => $dataset->csvPreview(),
            'voteCount' => Vote::countFor($post->id),
            'hasVoted' => !Yii::$app->user->isGuest && Vote::hasVoted($post->id, Yii::$app->user->id),
            'associatedNotebooks' => $associatedNotebooks,
        ]);
    }

    public function actionUpvote(int $id)
    {
        $post = $this->findPost($id);
        Vote::toggle($post->id, Yii::$app->user->id);
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDownload(int $id)
    {
        $post = $this->findPost($id);
        $dataset = $post->dataset;

        $dataset->download_count = ($dataset->download_count ?? 0) + 1;
        $dataset->save(false);

        return Yii::$app->response->sendFile(Yii::getAlias('@webroot') . $dataset->file_path);
    }

    /** My datasets — published (filterable, same as the public index) and rejected (with reasons). */
    public function actionMine(string $q = '', string $topic = '', string $sort = 'date', string $verified = '')
    {
        $userId = Yii::$app->user->id;

        $query = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_DATASET])
            ->innerJoinWith('dataset');

        if (trim($q) !== '') {
            $query->andWhere(['like', '{{%post}}.title', $q]);
        }
        if (trim($topic) !== '') {
            $query->andWhere(['like', '{{%dataset}}.topic', $topic]);
        }
        if ($verified === 'verified') {
            $query->andWhere(['{{%post}}.verified' => 1]);
        } elseif ($verified === 'unverified') {
            $query->andWhere(['{{%post}}.verified' => 0]);
        }

        $published = $query->orderBy(['{{%post}}.created_at' => SORT_DESC])->all();

        $pendingReview = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_PENDING, 'type' => Post::TYPE_DATASET])
            ->orderBy(['created_at' => SORT_DESC])->all();

        $rejected = Post::find()->where(['author_id' => $userId, 'status' => Post::STATUS_REJECTED, 'type' => Post::TYPE_DATASET])
            ->orderBy(['created_at' => SORT_DESC])->all();

        $rejectionReasons = [];
        foreach ($rejected as $post) {
            $historyRow = \app\models\PostStatusHistory::find()
                ->where(['post_id' => $post->id, 'new_status' => Post::STATUS_REJECTED])
                ->orderBy(['created_at' => SORT_DESC])->one();
            $rejectionReasons[$post->id] = $historyRow->note ?? null;
        }

        $topics = Dataset::find()->select('topic')->distinct()->column();

        return $this->render('mine', [
            'published' => $published, 'pendingReview' => $pendingReview, 'rejected' => $rejected, 'rejectionReasons' => $rejectionReasons,
            'topics' => $topics, 'q' => $q, 'topic' => $topic, 'sort' => $sort, 'verified' => $verified,
        ]);
    }

    /** Owner asks a moderator to verify an already-published, unverified dataset — doesn't touch visibility/status. */
    public function actionRequestReview(int $id)
    {
        $post = $this->findPost($id);
        $dataset = $post->dataset;

        if ((int) $post->author_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException('Only the owner can request a review.');
        }
        if ($post->verified) {
            Yii::$app->session->setFlash('error', 'This dataset is already verified.');
            return $this->redirect(['view', 'id' => $id]);
        }
        if ($dataset->verification_requested_at !== null) {
            Yii::$app->session->setFlash('error', 'A review is already pending for this dataset.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $dataset->verification_requested_at = time();
        $dataset->save(false);

        Yii::$app->session->setFlash('success', 'Review requested — a moderator will take a look.');
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionCreate()
    {
        if ($blocked = $this->blockRestrictedStudent()) return $blocked;

        $post = new Post();
        $dataset = new Dataset();

        // Contribute flow from a competition/hackathon page — prefill the link.
        $linkedId = Yii::$app->request->get('linked_id');
        if ($linkedId !== null) {
            $dataset->linked_post_id = (int) $linkedId;
        }

        if (Yii::$app->request->isPost) {
            [$post, $dataset, $success] = $this->saveFromRequest($post, $dataset, true);
            if ($success) {
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('create', ['post' => $post, 'dataset' => $dataset] + $this->associationOptions());
    }

    public function actionUpdate(int $id)
    {
        $post = $this->findPost($id);
        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only edit your own datasets.');
        }
        $dataset = $post->dataset;

        if (Yii::$app->request->isPost) {
            // A rejected dataset that gets edited goes back into the review
            // queue — matches how competitions handle this. Only relevant if
            // it was submitted for review in the first place; unverified
            // datasets are never 'rejected' since they publish immediately.
            $wasRejected = $post->status === Post::STATUS_REJECTED;

            [$post, $dataset, $success] = $this->saveFromRequest($post, $dataset, false);

            if ($success) {
                if ($wasRejected) {
                    $post->status = Post::STATUS_PENDING;
                    $post->save(false);
                    Yii::$app->session->setFlash('success', "\"{$post->title}\" was resubmitted for review.");
                }
                return $this->redirect(['view', 'id' => $post->id]);
            }
        }

        return $this->render('update', ['post' => $post, 'dataset' => $dataset] + $this->associationOptions());
    }

    /** Published competitions/hackathons, for the "associate with" dropdowns. */
    private function associationOptions(): array
    {
        return [
            'competitions' => Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_COMPETITION])->all(),
            'hackathons' => Post::find()->where(['status' => Post::STATUS_PUBLISHED, 'type' => Post::TYPE_HACKATHON])->all(),
        ];
    }

    public function actionDelete(int $id)
    {
        $post = $this->findPost($id);
        if ((int) Yii::$app->user->id !== (int) $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only delete your own datasets.');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $db->createCommand()->delete('{{%vote}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%comment}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%bookmark}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post_status_history}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%dataset}}', ['post_id' => $post->id])->execute();
            $db->createCommand()->delete('{{%post}}', ['id' => $post->id])->execute();
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Dataset deleted.');
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Could not delete — check the logs.');
        }

        return $this->redirect(['index']);
    }

    private function saveFromRequest(Post $post, Dataset $dataset, bool $isNew): array
    {
        $postData = Yii::$app->request->post('Post', []);
        $datasetData = Yii::$app->request->post('Dataset', []);
        $submitForReview = Yii::$app->request->post('submit_for_review') === '1';

        $post->type = Post::TYPE_DATASET;
        $post->title = $postData['title'] ?? $post->title;
        $post->body = $postData['body'] ?? $post->body;

        if ($isNew) {
            $post->slug = $this->slugify($post->title) . '-' . substr(uniqid(), -6);
            $post->author_id = Yii::$app->user->id;
        }

        $dataset->attributes = $datasetData;

        $postValid = $post->validate();
        $datasetValid = $dataset->validate(['topic', 'linked_post_id', 'description', 'target_column', 'summary_stats']);
        $isValid = $postValid && $datasetValid;
        $success = false;

        if (!$isValid) {
            $errors = array_merge($post->getFirstErrors(), $dataset->getFirstErrors());
            Yii::$app->session->setFlash('error', 'Please fix the following: ' . implode(' · ', $errors));
        }

        if ($isValid) {
            $transaction = Post::getDb()->beginTransaction();
            try {
                if ($isNew) {
                    // Creator's choice, not type-based: submit for review (pending,
                    // becomes verified once approved) or publish now as unverified.
                    $post->status = $submitForReview ? Post::STATUS_PENDING : Post::STATUS_PUBLISHED;
                    $post->verified = false;
                }
                if (!$post->save(false)) {
                    throw new \RuntimeException('Failed to save post.');
                }

                $datasetFile = UploadedFile::getInstanceByName('dataset_file');
                if ($datasetFile !== null) {
                    $dir = Yii::getAlias('@webroot/uploads/standalone-datasets');
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    $filename = $post->id . '_' . $datasetFile->baseName . '.' . $datasetFile->extension;
                    $datasetFile->saveAs("$dir/$filename");
                    $fullPath = "$dir/$filename";

                    $dataset->file_path = "/uploads/standalone-datasets/$filename";
                    $dataset->file_size = filesize($fullPath);

                    if (strtolower($datasetFile->extension) === 'csv') {
                        $handle = @fopen($fullPath, 'r');
                        if ($handle) {
                            $header = fgetcsv($handle, 0, ',', '"', '\\');
                            $dataset->column_count = $header ? count($header) : null;
                            $rowCount = 0;
                            while (fgetcsv($handle, 0, ',', '"', '\\') !== false) $rowCount++;
                            fclose($handle);
                            $dataset->row_count = $rowCount;
                        }
                    }
                } elseif ($isNew) {
                    throw new \RuntimeException('A dataset file is required.');
                }

                $dataset->post_id = $post->id;
                $dataset->download_count = $dataset->download_count ?: 0;
                if (!$dataset->save(false)) {
                    throw new \RuntimeException('Failed to save dataset details.');
                }

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

        return [$post, $dataset, $success];
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
        $post = Post::find()->where(['id' => $id, 'type' => Post::TYPE_DATASET])->one();
        if ($post === null) {
            throw new NotFoundHttpException('Dataset not found.');
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