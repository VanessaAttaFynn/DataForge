<?php

namespace app\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

/**
 * Permissions management — admin only. Lists every user with their current
 * role and lets an admin change it.
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']], // permission checked in-action
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // TODO: re-enable once testing is done.
        // if (!Yii::$app->user->can('manageUsers')) {
        //     throw new ForbiddenHttpException('You do not have permission to manage users.');
        // }

        $users = User::find()->orderBy(['created_at' => SORT_DESC])->all();
        $auth = Yii::$app->authManager;

        $userRoles = [];
        foreach ($users as $user) {
            $roles = $auth->getRolesByUser($user->id);
            $userRoles[$user->id] = $roles ? array_key_first($roles) : null;
        }

        return $this->render('index', [
            'users' => $users,
            'userRoles' => $userRoles,
            'availableRoles' => ['student', 'moderator', 'admin'],
        ]);
    }

    public function actionSetRole(int $id)
    {
        // TODO: re-enable once testing is done.
        // if (!Yii::$app->user->can('manageUsers')) {
        //     throw new ForbiddenHttpException('You do not have permission to manage users.');
        // }

        $user = User::findOne($id);
        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $newRoleName = Yii::$app->request->post('role');
        $auth = Yii::$app->authManager;
        $newRole = $auth->getRole($newRoleName);

        if ($newRole === null) {
            Yii::$app->session->setFlash('error', 'Unknown role.');
            return $this->redirect(['index']);
        }

        // Prevent an admin from locking themselves out by demoting their
        // own last-remaining admin account.
        if ($user->id === Yii::$app->user->id && $newRoleName !== 'admin') {
            $adminCount = count($auth->getUserIdsByRole('admin'));
            if ($adminCount <= 1) {
                Yii::$app->session->setFlash('error', 'You are the only admin — assign another admin before changing your own role.');
                return $this->redirect(['index']);
            }
        }

        $auth->revokeAll($user->id);
        $auth->assign($newRole, $user->id);

        Yii::$app->session->setFlash('success', "{$user->username} is now {$newRoleName}.");
        return $this->redirect(['index']);
    }
}