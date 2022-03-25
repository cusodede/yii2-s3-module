<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var array $buckets
 * @var CloudStorage $model
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\S3Module;
use limion\jqueryfileupload\JQueryFileUpload;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

$isDisabled = $model->isNewRecord?false:'disabled';
?>

<?php $form = ActiveForm::begin(); ?>
<div class="panel">
	<div class="panel-hdr">
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<div class="row">
				<div class="col-md-12">
					<?= $form->field($model, 'file')->widget(JQueryFileUpload::class, [
						'url' => [S3Module::to('index/upload')],
						'appearance' => 'ui',
						'formId' => $form->id,
						'gallery' => false,
						'options' => [
						],
						'clientOptions' => [
							'disableImagePreview' => true,
							'previewThumbnail' => false,
							'preview' => false,
							'type' => 'PUT',
							'autoUpload' => false,
							'multipart' => true
						]
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<?= $form->field($model, 'bucket')->dropdownList($buckets, ['prompt' => '', 'disabled' => $isDisabled]) ?>
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
		</div>
		<div class="panel-content">
			<?= $form->errorSummary($model) ?>
			<?= $this->render('subviews/editPanelFooter', compact('model', 'form', 'buckets')) ?>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>
