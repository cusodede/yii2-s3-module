<?php
declare(strict_types = 1);

namespace cusodede\s3\widgets\files_model_list;

use cusodede\s3\models\cloud_storage\CloudStorage;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\bootstrap4\Widget;

/**
 * Виджет выводит список загруженных файлов принадлежащих модели
 * @package cusodede\s3\widgets\files_model_list
 */
class S3FilesModelListWidget extends Widget {
	public Model|null $model = null;

	/**
	 * @inheritDoc
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function run():string {
		if (null === $this->model) throw new InvalidConfigException("Model parameter is required");
		$cloudStorage = CloudStorage::find()
			->where(['model_key' => $this->model?->id, 'model_name' => get_class($this->model)])
			->all();

		return $this->render('list', ['cloudStorage' => $cloudStorage]);
	}
}
