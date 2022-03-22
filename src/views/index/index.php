<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var CloudStorageSearch $searchModel
 * @var string $modelName
 * @var ControllerTrait $controller
 * @var ActiveDataProvider $dataProvider
 */

use cusodede\s3\models\cloud_storage\CloudStorageSearch;
use cusodede\s3\S3Module;
use pozitronik\widgets\BadgeWidget;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use pozitronik\traits\traits\ControllerTrait;
use yii\bootstrap4\Html;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\web\JsExpression;
use yii\web\View;

//ModalHelperAsset::register($this);
//GridHelperAsset::register($this);

$id = "{$modelName}-index-grid";
?>
<?= GridConfig::widget([
	'id' => $id,
	'grid' => GridView::begin([
		'id' => $id,
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'filterOnFocusOut' => false,
		'panel' => [
			'heading' => false,
		],
		'replaceTags' => [
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['файл', 'файлы', 'файлов']):"Нет файлов",
			'{newRecord}' => Html::a('Загрузить файл', S3Module::to('test/create'), ['class' => 'btn btn-success']),
			'{filterBtn}' => Html::button("<i class='fa fa-filter'></i>", ['onclick' => new JsExpression('setFakeGridFilter("#'.$id.'")'), 'class' => 'btn btn-info']),
			'{createBucket}' => Html::a('Добавить корзину', S3Module::to('/index/create-bucket'), ['class' => 'btn btn-success']),
		],
		'toolbar' => [
			'{filterBtn}'
		],
		'panelBeforeTemplate' => '{options}{newRecord}{createBucket}{toolbarContainer}{before}<div class="clearfix"></div>',
		'summary' => null,
		'showOnEmpty' => true,
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => [
			[
				'class' => ActionColumn::class,
				'template' => '<div class="btn-group">{edit}{view}{contracts}</div>',
				'buttons' => [
					'edit' => static fn(string $url) => Html::a('<i class="fa fa-edit"></i>', $url, [
						'class' => 'btn btn-sm btn-outline-primary',
						'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактирование']
					]),
					'view' => static fn(string $url) => Html::a('<i class="fa fa-eye"></i>', $url, [
							'class' => 'btn btn-sm btn-outline-primary',
							'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Просмотр']
						]
					)
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
					'urlScheme' => [S3Module::to(['/download/index']), 'id' => $model->id],
					'useAjaxModal' => false
				])
			],
			'created_at',
			'deleted:boolean',
			'uploaded:boolean'
		]
	])
]) ?>
