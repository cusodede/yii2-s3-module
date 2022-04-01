<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var CloudStorage[] $cloudStorage
 */

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\S3Module;
use yii\bootstrap4\Html;
use yii\web\View;

?>

<?php if (!empty($cloudStorage)): ?>
	<ul class="list-group mt-3">
		<?php foreach ($cloudStorage as $model): ?>
			<li class="list-group-item">
				<?= Html::a($model->filename, [S3Module::to(['/index/download']), 'id' => $model->id]) ?>
				<?= Html::tag(
					'span',
					$model->created_at,
					[
						'class' => 'badge badge-info float-right',
						'data' => ['toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Дата загрузки файла'],
					]
				) ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
