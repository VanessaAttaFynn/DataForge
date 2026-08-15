<?php

/** @var app\models\Post $post */

use yii\helpers\Html;

$this->title = 'Contribute';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Contribute</div>
        <h1 class="page-title"><?= Html::encode($post->title) ?></h1>
        <div class="page-sub">Add a dataset or notebook related to this <?= $post->type ?>.</div>
    </div>
    <?= Html::a('← Back', ['view', 'id' => $post->id], ['class' => 'nav-item', 'style' => 'display: inline-flex; padding: 9px 16px;']) ?>
</div>

<div style="display: flex; gap: 16px; flex-wrap: wrap;">
    <?= Html::a(
        Html::tag('div', '<div style="font-size: 26px; margin-bottom: 10px;">🗄️</div><div class="comp-name">Add a Dataset</div><div class="comp-meta">Upload a file related to this ' . $post->type . '.</div>', ['class' => 'dataset-card']),
        ['/dataset/create', 'linked_id' => $post->id],
        ['style' => 'text-decoration: none; width: 260px;']
    ) ?>
    <?= Html::a(
        Html::tag('div', '<div style="font-size: 26px; margin-bottom: 10px;">📓</div><div class="comp-name">Add a Notebook</div><div class="comp-meta">Publish an approach or analysis for this ' . $post->type . '.</div>', ['class' => 'dataset-card']),
        ['/notebook/create', 'linked_id' => $post->id],
        ['style' => 'text-decoration: none; width: 260px;']
    ) ?>
</div>