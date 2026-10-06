<?php

namespace app\commands;

use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Role helpers for the command line.
 *
 *   php yii rbac/make-admin <username or email>
 */
class RbacController extends Controller
{
    /**
     * Makes an existing user an admin (replaces their current role).
     * The account must already exist — sign up through the site first.
     */
    public function actionMakeAdmin(string $usernameOrEmail): int
    {
        $user = User::find()
            ->where(['username' => $usernameOrEmail])
            ->orWhere(['email' => $usernameOrEmail])
            ->one();

        if ($user === null) {
            $this->stderr("No user found with username or email \"{$usernameOrEmail}\".\n");
            return ExitCode::DATAERR;
        }

        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');
        if ($admin === null) {
            $this->stderr("The 'admin' role does not exist. Run the RBAC migration first.\n");
            return ExitCode::CONFIG;
        }

        $auth->revokeAll($user->id);
        $auth->assign($admin, $user->id);

        $this->stdout("{$user->username} ({$user->email}) is now an admin.\n");
        return ExitCode::OK;
    }
}
