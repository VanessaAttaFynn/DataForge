<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Tag extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%tag}}';
    }

    public function rules()
    {
        return [
            [['name', 'slug'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['slug'], 'string', 'max' => 50],
        ];
    }

    /**
     * Parses a comma-separated string, finds or creates each Tag,
     * and returns the resulting Tag ids.
     */
    public static function resolveNames(string $commaSeparated): array
    {
        $names = array_filter(array_map('trim', explode(',', $commaSeparated)));
        $ids = [];

        foreach ($names as $name) {
            $slug = strtolower(preg_replace('~[^a-zA-Z0-9]+~', '-', trim($name)));
            $slug = trim($slug, '-') ?: 'tag';

            $tag = static::findOne(['slug' => $slug]);
            if ($tag === null) {
                $tag = new static(['name' => $name, 'slug' => $slug]);
                $tag->save(false);
            }
            $ids[] = $tag->id;
        }

        return array_unique($ids);
    }

    /** Replace a post's tags entirely with the given tag ids. */
    public static function syncPostTags(int $postId, array $tagIds): void
    {
        $db = Yii::$app->db;
        $db->createCommand()->delete('{{%post_tag}}', ['post_id' => $postId])->execute();
        foreach ($tagIds as $tagId) {
            $db->createCommand()->insert('{{%post_tag}}', ['post_id' => $postId, 'tag_id' => $tagId])->execute();
        }
    }

    public static function forPost(int $postId): array
    {
        return static::find()
            ->innerJoin('{{%post_tag}}', '{{%post_tag}}.tag_id = {{%tag}}.id')
            ->where(['{{%post_tag}}.post_id' => $postId])
            ->all();
    }
}