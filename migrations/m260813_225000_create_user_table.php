<?php

use yii\db\Migration;

/**
 * Standard Yii2 user table — matches the columns yii\web\IdentityInterface
 * implementations (and yii2-app-advanced's default User model) expect.
 * MUST run before create_post_table, since post.author_id references it.
 */
class m260813_225000_create_user_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'password_reset_token' => $this->string(255)->null(),
            'email' => $this->string(255)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10)->comment('10=active, 0=deleted'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'verification_token' => $this->string(255)->null(),
        ]);

        $this->createIndex('idx_user_username', '{{%user}}', 'username', true);
        $this->createIndex('idx_user_email', '{{%user}}', 'email', true);
        $this->createIndex('idx_user_password_reset_token', '{{%user}}', 'password_reset_token', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}