<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var array $buckets
 * @var CloudStorage $model
 * @var ActiveForm $form
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use kartik\select2\Select2;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

$isDisabled = $model->isNewRecord?false:'disabled';
?>

<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'file')->fileInput() ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'bucket')->dropdownList($buckets, ['prompt' => '', 'disabled' => $isDisabled]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'key')->textInput([
			'disabled' => $isDisabled,
			'placeholder' => 'Оставьте пустым для автоматической генерации.'
		]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'filename')->textInput([
			'disabled' => $isDisabled,
			'placeholder' => 'Имя загружаемого файла с указанием расширения. Оставьте пустым для автоподстановки.'
		]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'tags')->widget(Select2::class, [
			'options' => [
				'placeholder' => 'Выберите или добавьте теги',
				'multiple' => true,
			],
			'pluginOptions' => [
				'tags' => true,
				'tokenSeparators' => [',', ' '],
				'maximumInputLength' => 10
			]
		]) ?>
	</div>
</div>