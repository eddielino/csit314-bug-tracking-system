<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/**
 * @var yii\web\View $this
 * @var common\models\BugAction $model
 * @var yii\bootstrap4\ActiveForm $form
 */
?>

<div class="bug-action-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?php echo $form->field($model, 'id') ?>
    <?php echo $form->field($model, 'bug_id') ?>
    <?php echo $form->field($model, 'action_type') ?>
    <?php echo $form->field($model, 'notes') ?>
    <?php echo $form->field($model, 'status') ?>
    <?php // echo $form->field($model, 'created_at') ?>
    <?php // echo $form->field($model, 'created_by') ?>
    <?php // echo $form->field($model, 'updated_at') ?>
    <?php // echo $form->field($model, 'updated_by') ?>

    <div class="form-group">
        <?php echo Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?php echo Html::resetButton('Reset', ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
