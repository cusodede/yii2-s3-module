<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var array $buckets
 * @var CloudStorage $model
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\S3ModuleAssets;
use yii\bootstrap4\ActiveForm;
use yii\web\View;

S3ModuleAssets::register($this);
?>

<?php $form = ActiveForm::begin(); ?>
<div class="panel">
	<div class="panel-hdr">
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<?= $this->render('subviews/putPanelBody', compact('model', 'form', 'buckets')) ?>
		</div>
		<div class="panel-content">
			<?= $form->errorSummary($model) ?>
			<?= $this->render('subviews/editPanelFooter', compact('model', 'form', 'buckets')) ?>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>
