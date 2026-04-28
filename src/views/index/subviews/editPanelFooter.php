<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var CloudStorage $model
 * @var ActiveForm| $form
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\web\View;

?>

<?= Html::submitButton('Сохранить', [
		'class' => $model->isNewRecord?'btn btn-success float-right':'btn btn-primary float-right',
		'form' => $form->id
	]
) ?>
