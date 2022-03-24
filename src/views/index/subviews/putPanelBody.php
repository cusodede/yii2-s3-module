<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var array $buckets
 * @var CloudStorage $model
 * @var ActiveForm $form
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\S3Module;
use limion\jqueryfileupload\JQueryFileUpload;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

$isDisabled = $model->isNewRecord?false:'disabled';
?>

<div class="row">
	<div class="col-md-12">
		<?= JQueryFileUpload::widget([
			'model' => $model,
			'url' => [S3Module::to('index/put')], // your route for saving images,
			'appearance' => 'ui', // available values: 'ui','plus' or 'basic'

			'formId' => $form->id,
			'options' => [
				'method' => 'PUT'
			],
			'clientOptions' => [
				'maxFileSize' => 2000000,
//				'dataType' => 'json',
//				'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(gif|jpe?g|png)$/i'),
				'autoUpload' => false
			]
		]); ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'bucket')->dropDownList($buckets, ['prompt' => '', 'disabled' => $isDisabled]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'key')->textInput(['disabled' => $isDisabled,
			'placeholder' => 'Оставьте пустым для автоматической генерации.']) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'filename')->textInput(['disabled' => $isDisabled,
			'placeholder' => 'Имя загружаемого файла с указанием расширения. Оставьте пустым для автоподстановки.']) ?>
	</div>
</div>

