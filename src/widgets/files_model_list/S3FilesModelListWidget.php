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
		if (null === $id = $this->model->id) throw new InvalidConfigException("Model must have an non-null id attribute");

		return $this->render('list', ['cloudStorage' => CloudStorage::find()
			->where(['model_key' => $id, 'model_name' => get_class($this->model)])
			->all()]);
	}
}
