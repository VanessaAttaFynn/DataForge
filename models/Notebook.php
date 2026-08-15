<?php

namespace app\models;

use yii\db\ActiveRecord;

class Notebook extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%notebook}}';
    }

    public function rules()
    {
        return [
            [['post_id'], 'required'],
            [['post_id'], 'integer'],
            [['topic'], 'string', 'max' => 100],
            [['notebook_file_path', 'code_path'], 'string', 'max' => 255],
            [['language'], 'string', 'max' => 30],
            [['linked_post_id'], 'integer'],
            [['verification_requested_at'], 'integer'],
            [['topic'], 'validateAssociation'],
        ];
    }

    public function validateAssociation(): void
    {
        if (empty($this->topic) && empty($this->linked_post_id)) {
            $this->addError('topic', 'Choose a topic or link this to a dataset/competition/hackathon.');
        }
    }

    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    public function getLinkedPost()
    {
        return $this->hasOne(Post::class, ['id' => 'linked_post_id']);
    }

    /** "Competition Notebook", "Hackathon Notebook", "Dataset Notebook", or null if unlinked. */
    public function getLinkedTypeLabel(): ?string
    {
        if ($this->linkedPost === null) {
            return null;
        }
        return ucfirst($this->linkedPost->type) . ' Notebook';
    }
}