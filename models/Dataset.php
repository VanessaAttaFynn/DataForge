<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Dataset extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%dataset}}';
    }

    public function rules()
    {
        return [
            [['post_id', 'file_path', 'file_size'], 'required'],
            [['post_id', 'file_size', 'row_count', 'column_count', 'download_count', 'linked_post_id', 'verification_requested_at'], 'integer'],
            [['file_path'], 'string', 'max' => 255],
            [['license', 'target_column', 'topic'], 'string', 'max' => 150],
            [['description', 'summary_stats'], 'string'],
            [['topic'], 'validateAssociation'],
        ];
    }

    /** Must have either a topic OR a linked post — the "associated to" choice is mutually exclusive, but one is required. */
    public function validateAssociation(): void
    {
        if (empty($this->topic) && empty($this->linked_post_id)) {
            $this->addError('topic', 'Choose a topic or link this to a competition/hackathon.');
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

    public function getLinkedTypeLabel(): ?string
    {
        if ($this->linkedPost === null) {
            return null;
        }
        return ucfirst($this->linkedPost->type) . ' Dataset';
    }

    public function isCsv(): bool
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) === 'csv';
    }

    /** First N rows for a safe, read-only preview — CSV only. Binary/other formats never get a raw preview. */
    public function csvPreview(int $maxRows = 20): ?array
    {
        if (!$this->isCsv()) {
            return null;
        }

        $fullPath = Yii::getAlias('@webroot') . $this->file_path;
        if (!file_exists($fullPath)) {
            return null;
        }

        $handle = @fopen($fullPath, 'r');
        if (!$handle) {
            return null;
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        $rows = [];
        while (count($rows) < $maxRows && ($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return ['header' => $header, 'rows' => $rows];
    }
}