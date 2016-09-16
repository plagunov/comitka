<?php

use project\models\ContributionReview;
use user\widgets\ContributorLine;
use yii\bootstrap\Nav;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\LinkPager;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */

$this->title = Yii::t('main', 'Contributions');
?>

<?= Nav::widget([
    'options' => ['class' => 'nav nav-tabs'],
    'items' => [
        [
            'url' => ['main/reviews', 'type' => 'my-reviews'],
            'label' => Yii::t('main', 'My reviews'),
        ],
        [
            'url' => ['main/reviews', 'type' => 'my-contributions'],
            'label' => Yii::t('main', 'My contributions'),
        ],
        [
            'url' => ['main/reviews', 'type' => 'all-contributions'],
            'label' => Yii::t('main', 'All contributions'),
        ]
    ],
]) ?>

<h2>
    <?php if ($dataProvider->id == 'my-reviews'):?>
        <?= Yii::t('main', 'My reviews') ?>
    <?php elseif ($dataProvider->id == 'my-contributions'):?>
        <?= Yii::t('main', 'My contributions') ?>
    <?php elseif ($dataProvider->id == 'all-contributions'):?>
        <?= Yii::t('main', 'All contributions') ?>
    <?php endif;?>
</h2>

<?php if ($dataProvider->getCount() == 0):?>
    <p><?= Yii::t('main', 'No new contributions in this filter') ?></p>
<?php else:?>
    <div class="list-group">
        <?php foreach ($dataProvider->getModels() as $model):?>
            <?php
             /* @var $model ContributionReview */
            ?>
            <a class="list-group-item col-md-12 history-simple-item" href="<?=Url::to(['/project/history/commit-summary', 'id' => $model->project_id, 'commitId' => $model->commit_id])?>">
                <div class="col-md-4">
                    <?= ContributorLine::widget([
                        'contributorName' => $model->contributor_name,
                        'contributorEmail' => $model->contributor_email,
                        'user' => $model->contributor,
                        'vcsType' => $model->repo_type,
                        'avatarSize' => 'small',
                        'useLink' => false,
                    ]) ?><br />
                    <span class="label label-info"><?= Html::encode($model->project->title) ?></span>
                    <span class="commit-date"><?=$model->getDateTime()->format('d\'M y H:i:s')?></span>
                </div>
                <div class="col-md-8 commit-message">
                    <strong class="list-group-item-heading"><?=Html::encode($model->message)?></strong>
                </div>
            </a>
        <?php endforeach;?>
    </div>
<?php endif;?>

<?= LinkPager::widget([
    'pagination' => $dataProvider->pagination,
]) ?>
