<?php

namespace app\controllers;

use Yii;
use app\models\Post;
use app\models\Competition;
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
                    ['allow' => true, 'actions' => ['index', 'view'], 'roles' => ['?', '@']],
                    ['allow' => true, 'actions' => ['create', 'update', 'delete'], 'roles' => ['@']],
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

        return $this->render('index', ['posts' => $posts, 'view' => $view]);
    }

    public function actionView(int $id)
    {
        $post = $this->findPost($id);
        $canManage = Yii::$app->user->id === $post->author_id || Yii::$app->user->can('moderateContent');

        return $this->render('view', [
            'post' => $post,
            'competition' => $post->competition,
            'canManage' => $canManage,
        ]);
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

        if (Yii::$app->user->id !== $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only edit your own competitions.');
        }

        $competition = $post->competition;

        if (Yii::$app->request->isPost) {
            // If it had been rejected, editing sends it back into the review queue.
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
     */
    public function actionDelete(int $id)
    {
        $post = $this->findPost($id);

        if (Yii::$app->user->id !== $post->author_id && !Yii::$app->user->can('moderateContent')) {
            throw new ForbiddenHttpException('You can only delete your own competitions.');
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $teamIds = (new \yii\db\Query())->select('id')->from('{{%team}}')->where(['competition_id' => $post->id])->column();

            if (!empty($teamIds)) {
                $db->createCommand()->delete('{{%submission}}', ['team_id' => $teamIds])->execute();
                $db->createCommand()->delete('{{%team_membership}}', ['team_id' => $teamIds])->execute();
                $db->createCommand()->delete('{{%team}}', ['id' => $teamIds])->execute();
            }

            $db->createCommand()->delete('{{%submission}}', ['competition_id' => $post->id])->execute();
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

    /**
     * Shared save logic for create + update. $isNew controls whether we
     * assign author/slug/initial-status (create) or just update fields (update).
     */
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

        // datetime-local inputs send 'YYYY-MM-DDTHH:MM' — SQL Server's
        // datetime type needs 'YYYY-MM-DD HH:MM:SS' (space, not 'T'; seconds
        // required). Reformat both deadline fields before they ever touch the DB.
        foreach (['registration_deadline', 'deadline'] as $dateField) {
            if (!empty($competition->$dateField)) {
                $normalized = str_replace('T', ' ', $competition->$dateField);
                if (strlen($normalized) === 16) { // 'YYYY-MM-DD HH:MM' — no seconds yet
                    $normalized .= ':00';
                }
                $competition->$dateField = $normalized;
            }
        }

        // Validate both separately (not with &&) so competition errors still
        // populate even if post fails first — otherwise short-circuiting hides them.
        $postValid = $post->validate();
        // Exclude post_id: it's set a few lines below, right after $post
        // saves and gets its real id — validating it here would always
        // fail since it genuinely doesn't exist yet at this point.
        $competitionValid = $competition->validate([
            'metric', 'accepts', 'team_size_limit', 'submission_cap_per_day',
            'reward_type', 'reward_details', 'registration_deadline', 'deadline',
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
                // TODO: swap back to a generic message once this is stable —
                // showing the raw exception is just for testing right now.
                Yii::$app->session->setFlash('error', 'Save failed: ' . $e->getMessage());
            }
        }

        return [$post, $competition, $success];
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