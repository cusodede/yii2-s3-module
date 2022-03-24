<?php
declare(strict_types = 1);

namespace cusodede\s3\forms;

use cusodede\s3\models\S3;
use pozitronik\helpers\ArrayHelper;
use yii\base\Model;
use Throwable;

/**
 * Class CreateBucketForm
 */
class CreateBucketForm extends Model {
	public ?string $name = null;

	/**
	 * @return array
	 */
	public function rules():array {
		return [
			['name', 'required'],
			['name', 'match', 'pattern' => '/^[A-Za-z0-9\-]+$/'],
			['name', 'checkNameUnique']
		];
	}

	/**
	 * @param string $attribute
	 * @return bool
	 * @throws Throwable
	 */
	public function checkNameUnique(string $attribute):bool {
		if (in_array(ArrayHelper::getValue($this->getAttributes([$attribute]), $attribute), ArrayHelper::getColumn(ArrayHelper::getValue((new S3())->client->listBuckets()->toArray(), 'Buckets'), 'Name'), true)) {
			$this->addErrors(['name' => 'Наименование должно быть уникальным']);
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function attributeLabels():array {
		return [
			'name' => 'Название',
		];
	}
}
