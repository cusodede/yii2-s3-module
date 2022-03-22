<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var array $buckets
 * @var CloudStorage $model
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use pozitronik\widgets\BadgeWidget;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Modal;
use yii\web\View;

$modelName = $model->formName();
?>
<?php
Modal::begin([
	'id' => "{$modelName}-modal-edit-{$model->id}",
	'size' => Modal::SIZE_LARGE,
	'title' => 'ID:'.BadgeWidget::widget([
			'items' => $model,
			'subItem' => 'id'
		]),
	'footer' => $this->render('../subviews/editPanelFooter', [
		'model' => $model,
		'form' => "{$modelName}-modal-edit"
	]),//post button outside the form
	'clientOptions' => [
		'backdrop' => true
	],
	'options' => [
		'class' => 'modal-dialog-large',
		'tabindex' => false // for Kartik's Select2 widget in modals
	]
]); ?>
<?php $form = ActiveForm::begin(
	[
		'id' => "{$model->formName()}-modal-edit",
		'enableAjaxValidation' => true,

	]) ?>
<?= $this->render('../subviews/editPanelBody', compact('model', 'form', 'buckets')) ?>
<?php ActiveForm::end(); ?>
<?php Modal::end(); ?>