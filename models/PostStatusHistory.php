<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $post_id
 * @property int $changed_by
 * @property string|null $old_status
 * @property string $new_status
 * @property string|null $note
 * @property int $created_at
 */
class PostStatusHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%post_status_history}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'changed_by', 'new_status', 'created_at'], 'required'],
            [['post_id', 'changed_by', 'created_at'], 'integer'],
            [['old_status', 'new_status'], 'string', 'max' => 20],
            [['note'], 'string'],
        ];
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getModerator()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by']);
    }
}