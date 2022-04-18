<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var CloudStorageSearch $searchModel
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use cusodede\s3\models\cloud_storage\CloudStorageSearch;
use cusodede\s3\S3Module;
use pozitronik\widgets\BadgeWidget;
use pozitronik\traits\traits\ControllerTrait;
use yii\base\DynamicModel;
use yii\bootstrap4\Html;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\web\View;

?>
<div class="panel">
	<div class="panel-hdr">
		<div class="panel-content">
			<?= S3Module::a('Загрузить файл', ['index/upload'], ['class' => 'btn btn-success']) ?>
			<?= S3Module::a('Добавить корзину', ['index/create-bucket'], ['class' => 'btn btn-success']) ?>
		</div>
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<?= GridView::widget([
				'id' => "cloud-storage-index-grid",
				'dataProvider' => $dataProvider,
				'filterModel' => $searchModel,
				'filterOnFocusOut' => false,
				'summary' => null,
				'showOnEmpty' => true,
				'columns' => [
					[
						'class' => ActionColumn::class,
						'template' => '<div class="btn-group">{edit}{view}{download}{delete}</div>',
						'buttons' => [
							'edit' => static fn(string $url):string => Html::a('<i class="fa fa-edit"></i>', $url),
							'view' => static fn(string $url):string => Html::a('<i class="fa fa-eye"></i>', $url),
							'download' => static fn(string $url):string => Html::a('<i class="fa fa-download"></i>', $url),
							'delete' => static fn(string $url):string => Html::a('<i class="fa fa-trash"></i>', $url),
						],
					],
					'id',
					'bucket',
					'key',
					[
						'attribute' => 'filename',
						'format' => 'raw',
						'value' => static fn(CloudStorageSearch $model) => BadgeWidget::widget([
							'items' => $model->filename,
							'urlScheme' => [S3Module::to(['/index/download']), 'id' => $model->id],
						])
					],
					'model_name',
					'model_key',
					'created_at:datetime',
					'deleted:boolean',
					'uploaded:boolean',
					'size:shortsize',
					[
						'attribute' => 'tagsFilter',
						'format' => 'raw',
						'value' => static fn(CloudStorageSearch $model) => BadgeWidget::widget([
							'items' => $model->tags,
							'innerPrefix' => fn(string $keyAttributeValue, ?DynamicModel $item):string => $keyAttributeValue.":"
						])
					]
				]
			]) ?>
		</div>
	</div>
</div>