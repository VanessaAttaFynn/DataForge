<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $message
 * @property string|null $link
 * @property bool $is_read
 * @property int $created_at
 */
class Notification extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%notification}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'type', 'message', 'created_at'], 'required'],
            [['user_id', 'created_at'], 'integer'],
            [['is_read'], 'boolean'],
            [['type'], 'string', 'max' => 30],
            [['message', 'link'], 'string', 'max' => 255],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}