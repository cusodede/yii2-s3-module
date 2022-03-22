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
<?php Modal::begin([
	'id' => "{$modelName}-modal-create-new",
	'size' => Modal::SIZE_LARGE,
	'title' => BadgeWidget::widget([
		'items' => $model,
		'subItem' => 'name'
	]),
	'footer' => $this->render('../subviews/editPanelFooter', [
		'model' => $model,
		'form' => "{$modelName}-modal-create"
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
		'id' => "{$model->formName()}-modal-create",
		'enableAjaxValidation' => true,

	]) ?>
<?= $this->render('../subviews/editPanelBody', compact('model', 'form', 'buckets')) ?>
<?php ActiveForm::end(); ?>
<?php Modal::end(); ?>