<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var null|bool $isCreated
 * @var CreateBucketForm $createBucketForm
 */

use cusodede\s3\forms\CreateBucketForm;
use cusodede\s3\models\S3;
use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

?>

<?php $form = ActiveForm::begin(['id' => 'create-bucket']); ?>
	<div class="panel">
		<div class="panel-hdr">
		</div>
		<div class="panel-container show">
			<div class="panel-content">
				<?php if (null === $isCreated): ?>
					<div class="alert alert-info" role="alert">К наименованию будет добавлен префикс "<?= S3::BUCKET_PREFIX ?>"</div>
				<?php endif; ?>
				<?php if (true === $isCreated): ?>
					<div class="alert alert-info" role="alert">Bucket создан!</div>
				<?php endif; ?>
				<div class="row">
					<div class="col-md-6">
						<?= $form->field($createBucketForm, 'name')->textInput() ?>
					</div>
				</div>
			</div>
			<div class="panel-content">
				<?= Html::submitButton('Создать', ['class' => 'btn btn-success float-right']) ?>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
<?php ActiveForm::end(); ?>