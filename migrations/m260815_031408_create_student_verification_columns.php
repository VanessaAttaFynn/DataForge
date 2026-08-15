<?php

use yii\db\Migration;

class m260815_031408_create_student_verification_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'student_id', $this->string(50)->null());
        $this->addColumn('{{%user}}', 'proof_document_path', $this->string(255)->null());
        $this->addColumn('{{%user}}', 'student_verification_status', $this->string(20)->notNull()->defaultValue('none'));
        $this->addColumn('{{%user}}', 'student_verification_note', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'student_id');
        $this->dropColumn('{{%user}}', 'proof_document_path');
        $this->dropColumn('{{%user}}', 'student_verification_status');
        $this->dropColumn('{{%user}}', 'student_verification_note');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260815_031408_create_student_verification_columns cannot be reverted.\n";

        return false;
    }
    */
}
