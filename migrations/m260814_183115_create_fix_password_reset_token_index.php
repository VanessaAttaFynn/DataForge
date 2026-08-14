<?php

use yii\db\Migration;

class m260814_183115_create_fix_password_reset_token_index extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex('idx_user_password_reset_token', '{{%user}}');
 
        $this->execute('
            CREATE UNIQUE INDEX idx_user_password_reset_token
            ON {{%user}} (password_reset_token)
            WHERE password_reset_token IS NOT NULL
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('DROP INDEX idx_user_password_reset_token ON {{%user}}');
        $this->createIndex('idx_user_password_reset_token', '{{%user}}', 'password_reset_token', true);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260814_183115_create_fix_password_reset_token_index cannot be reverted.\n";

        return false;
    }
    */
}
