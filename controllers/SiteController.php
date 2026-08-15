<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\ContactForm;
use app\models\LoginForm;
use app\models\SignupForm;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Security;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;
use app\models\TeamMembership;
use app\models\User;
use app\models\Post;
use app\models\Team;
use app\models\Vote;
use app\models\Notification;
use app\models\TeamCompetitionRegistration;
use app\models\CompetitionRegistration;


class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        private readonly Security $security,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login', 'signup', 'verify-email', 'serve-image'],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['logout', 'dashboard', 'index'],
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'transparent' => true,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return Yii::$app->user->isGuest
            ? $this->redirect(['site/login'])
            : $this->redirect(['site/dashboard']);
    }



    public function actionSignup()
    {
        $this->layout = 'blank';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new SignupForm();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $user = $model->signup();
            if ($user !== null) {
                Yii::$app->session->setFlash('success', 'Account created! Check your email to verify your account before logging in.');
                return $this->redirect(['site/login']);
            }
        }

        return $this->render('signup', ['model' => $model]);
    }

    public function actionLogin()
    {
        $this->layout = 'blank';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionVerifyEmail(string $token)
    {
        $user = User::findByVerificationToken($token);

        if ($user === null) {
            Yii::$app->session->setFlash('error', 'This verification link is invalid or has already been used.');
            return $this->redirect(['site/login']);
        }

        $user->status = User::STATUS_ACTIVE;
        $user->verification_token = null;
        $user->save(false);

        Yii::$app->session->setFlash('success', 'Email verified! You can now log in.');
        return $this->redirect(['site/login']);
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        $contact = $model->load($this->request->post()) && $model->contact(
            $this->mailer,
            Yii::$app->params['adminEmail'],
            Yii::$app->params['senderEmail'],
            Yii::$app->params['senderName'],
        );

        if ($contact) {
            Yii::$app->session->setFlash(
                'success',
                'Thank you for contacting us. We will respond to you as soon as possible.',
            );

            return $this->refresh();
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }

    public function actionDashboard()
    {
        $userId = Yii::$app->user->id;

        // ---------- Real stat counts ----------
        $myTeamIds = TeamMembership::find()
            ->select('team_id')->where(['user_id' => $userId, 'invite_status' => 'consented'])->column();

        $individualCompIds = CompetitionRegistration::find()
            ->select('competition_id')->where(['user_id' => $userId])->column();
        $teamCompIds = empty($myTeamIds) ? [] : TeamCompetitionRegistration::find()
            ->select('competition_id')->where(['in', 'team_id', $myTeamIds])->column();
        $competitionsJoinedCount = count(array_unique(array_merge($individualCompIds, $teamCompIds)));

        $datasetsPublishedCount = Post::find()->where(['author_id' => $userId, 'type' => 'dataset', 'status' => 'published'])->count();
        $notebooksPublishedCount = Post::find()->where(['author_id' => $userId, 'type' => 'notebook', 'status' => 'published'])->count();
        $teamsCount = count($myTeamIds);

        // ---------- Active competitions/hackathons I'm part of (not completed) ----------
        $joinedCompetitionIds = array_unique(array_merge($individualCompIds, $teamCompIds));
        $activeCompetitions = [];
        if (!empty($joinedCompetitionIds)) {
            $posts = Post::find()->where(['id' => $joinedCompetitionIds, 'status' => 'published'])->all();
            foreach ($posts as $p) {
                if ($p->competition->phaseKey() !== 'completed') {
                    $activeCompetitions[] = $p;
                }
            }
            $activeCompetitions = array_slice($activeCompetitions, 0, 4);
        }

        // ---------- Trending datasets (top voted, published) ----------
        $datasetPosts = Post::find()->where(['status' => 'published', 'type' => 'dataset'])->all();
        $datasetVotes = [];
        foreach ($datasetPosts as $p) {
            $datasetVotes[$p->id] = Vote::countFor($p->id);
        }
        usort($datasetPosts, fn($a, $b) => $datasetVotes[$b->id] <=> $datasetVotes[$a->id]);
        $trendingDatasets = array_slice($datasetPosts, 0, 3);

        // ---------- Recent activity = my real notifications ----------
        $recentNotifications = Notification::find()
            ->where(['user_id' => $userId])->orderBy(['created_at' => SORT_DESC])->limit(4)->all();

        // ---------- My teams (consented + pending invites) ----------
        $myTeams = empty($myTeamIds) ? [] : Team::find()->where(['id' => $myTeamIds])->all();
        $myPendingInvites = TeamMembership::find()
            ->where(['user_id' => $userId, 'invite_status' => 'invited'])->all();

        return $this->render('dashboard', [
            'competitionsJoinedCount' => $competitionsJoinedCount,
            'datasetsPublishedCount' => $datasetsPublishedCount,
            'notebooksPublishedCount' => $notebooksPublishedCount,
            'teamsCount' => $teamsCount,
            'activeCompetitions' => $activeCompetitions,
            'trendingDatasets' => $trendingDatasets,
            'datasetVotes' => $datasetVotes,
            'recentNotifications' => $recentNotifications,
            'myTeams' => $myTeams,
            'myPendingInvites' => $myPendingInvites,
        ]);
    }

    public function actionServeImage(string $path)
    {
        // Only ever serve files under web/uploads/ — reject anything else,
        // including any attempt to climb out with '../'.
        $path = ltrim($path, '/');
        if (!str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            throw new NotFoundHttpException('Not found.');
        }

        $fullPath = Yii::getAlias('@webroot/' . $path);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            throw new NotFoundHttpException('Image not found.');
        }

        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

        return Yii::$app->response->sendFile($fullPath, null, [
            'mimeType' => $mimeType,
            'inline' => true, // display in the page, not a download prompt
        ]);
    }
}
