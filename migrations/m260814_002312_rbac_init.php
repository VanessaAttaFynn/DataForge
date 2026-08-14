<?php

use yii\db\Migration;

class m260814_002312_rbac_init extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
 
        // ---------- Permissions ----------
        // Content creation — every student gets these.
        $createDataset = $auth->createPermission('createDataset');
        $createDataset->description = 'Upload a new dataset';
        $auth->add($createDataset);
 
        $createNotebook = $auth->createPermission('createNotebook');
        $createNotebook->description = 'Publish a new notebook';
        $auth->add($createNotebook);
 
        $createDiscussion = $auth->createPermission('createDiscussion');
        $createDiscussion->description = 'Start a new discussion';
        $auth->add($createDiscussion);
 
        $createCompetition = $auth->createPermission('createCompetition');
        $createCompetition->description = 'Propose a competition/hackathon (goes to pending review)';
        $auth->add($createCompetition);
 
        // Moderation — moderator and admin only.
        $approveCompetition = $auth->createPermission('approveCompetition');
        $approveCompetition->description = 'Approve or reject a pending competition/hackathon';
        $auth->add($approveCompetition);
 
        $verifyDataset = $auth->createPermission('verifyDataset');
        $verifyDataset->description = 'Mark a dataset as Verified';
        $auth->add($verifyDataset);
 
        $moderateContent = $auth->createPermission('moderateContent');
        $moderateContent->description = 'Remove/flag any post or comment';
        $auth->add($moderateContent);
 
        // Admin-only.
        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Assign roles, ban/unban users';
        $auth->add($manageUsers);
 
        // ---------- Roles ----------
        $student = $auth->createRole('student');
        $auth->add($student);
        $auth->addChild($student, $createDataset);
        $auth->addChild($student, $createNotebook);
        $auth->addChild($student, $createDiscussion);
        $auth->addChild($student, $createCompetition);
 
        $moderator = $auth->createRole('moderator');
        $auth->add($moderator);
        $auth->addChild($moderator, $student);          // inherits everything a student can do
        $auth->addChild($moderator, $approveCompetition);
        $auth->addChild($moderator, $verifyDataset);
        $auth->addChild($moderator, $moderateContent);
 
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $moderator);             // inherits everything a moderator can do
        $auth->addChild($admin, $manageUsers);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_002312_rbac_init cannot be reverted.\n";

        return false;
    }
    */
}
