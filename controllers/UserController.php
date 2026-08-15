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
        if (!Yii::$app->user->can('manageUsers')) {
            throw new ForbiddenHttpException('You do not have permission to manage users.');
        }

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
        if (!Yii::$app->user->can('manageUsers')) {
            throw new ForbiddenHttpException('You do not have permission to manage users.');
        }

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

    /** The logged-in user's own profile — real info, plus the student-verification submission form. */
    public function actionProfile()
    {
        $user = Yii::$app->user->identity;
        return $this->render('profile', ['user' => $user]);
    }

    /** Student submits their ID + proof-of-registration document for review. */
    public function actionSubmitStudentVerification()
    {
        $user = Yii::$app->user->identity;

        if (!$user->isStudentEmail()) {
            throw new ForbiddenHttpException('Student verification is only for st.ug.edu.gh accounts.');
        }
        if ($user->student_verification_status === \app\models\User::STUDENT_VERIFICATION_PENDING) {
            Yii::$app->session->setFlash('error', 'Your verification is already pending review.');
            return $this->redirect(['profile']);
        }
        if ($user->isStudentVerified()) {
            Yii::$app->session->setFlash('error', 'You are already a verified student.');
            return $this->redirect(['profile']);
        }

        $studentId = trim(Yii::$app->request->post('student_id', ''));
        $file = \yii\web\UploadedFile::getInstanceByName('proof_document');

        if ($studentId === '' || $file === null) {
            Yii::$app->session->setFlash('error', 'Student ID and a proof-of-registration document are both required.');
            return $this->redirect(['profile']);
        }

        $dir = Yii::getAlias('@webroot/uploads/student-proofs');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = $user->id . '_' . time() . '.' . $file->extension;
        $file->saveAs("$dir/$filename");

        $user->student_id = $studentId;
        $user->proof_document_path = "/uploads/student-proofs/$filename";
        $user->student_verification_status = \app\models\User::STUDENT_VERIFICATION_PENDING;
        $user->student_verification_note = null;
        $user->save(false);

        Yii::$app->session->setFlash('success', 'Submitted for review — you\'ll be notified once it\'s checked.');
        return $this->redirect(['profile']);
    }

    /** Streams the proof-of-registration document — own document, or any moderator/admin. */
    public function actionViewProof(int $id)
    {
        if ((int) Yii::$app->user->id !== $id && !Yii::$app->user->can('manageUsers')) {
            throw new ForbiddenHttpException('You do not have permission to view this document.');
        }

        $user = User::findOne($id);
        if ($user === null || empty($user->proof_document_path)) {
            throw new NotFoundHttpException('No document on file.');
        }

        return Yii::$app->response->sendFile(Yii::getAlias('@webroot') . $user->proof_document_path);
    }
}