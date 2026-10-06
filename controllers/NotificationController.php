<?php

namespace app\controllers;

use app\models\Notification;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class NotificationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['read-all' => ['POST']],
            ],
        ];
    }

    public function actionIndex()
    {
        $notifications = Notification::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(100)
            ->all();

        return $this->render('index', ['notifications' => $notifications]);
    }

    /** Marks one notification as read, then goes to its link (or back to the list). */
    public function actionOpen($id)
    {
        $n = Notification::findOne(['id' => (int) $id, 'user_id' => Yii::$app->user->id]);
        if ($n === null) {
            throw new NotFoundHttpException('Notification not found.');
        }
        if (!$n->is_read) {
            $n->is_read = 1;
            $n->save(false);
        }
        if ($n->link && str_starts_with($n->link, '/') && !str_starts_with($n->link, '//')) {
            return $this->redirect(Yii::$app->request->baseUrl . $n->link);
        }
        return $this->redirect(['index']);
    }

    public function actionReadAll()
    {
        Notification::updateAll(['is_read' => 1], ['user_id' => Yii::$app->user->id, 'is_read' => 0]);
        return $this->redirect(['index']);
    }
}
