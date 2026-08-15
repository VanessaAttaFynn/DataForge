<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Vote extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%vote}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'user_id', 'value', 'created_at'], 'required'],
            [['post_id', 'user_id', 'value', 'created_at'], 'integer'],
        ];
    }

    public static function countFor(int $postId): int
    {
        return (int) static::find()->where(['post_id' => $postId])->sum('value') ?: 0;
    }

    /** Toggle: if the user already upvoted, remove it; otherwise add one. Returns the new state (true = now upvoted). */
    public static function toggle(int $postId, int $userId): bool
    {
        $existing = static::findOne(['post_id' => $postId, 'user_id' => $userId]);
        if ($existing !== null) {
            $existing->delete();
            return false;
        }

        $vote = new static(['post_id' => $postId, 'user_id' => $userId, 'value' => 1, 'created_at' => time()]);
        $vote->save(false);
        return true;
    }

    public static function hasVoted(int $postId, int $userId): bool
    {
        return static::find()->where(['post_id' => $postId, 'user_id' => $userId])->exists();
    }
}