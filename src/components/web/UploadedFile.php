<?php
declare(strict_types = 1);

namespace cusodede\s3\components\web;

use yii\base\UnknownPropertyException;
use yii\web\UploadedFile as YiiUploadedFile;

/**
 * Class UploadedFile
 * @property-read ?resource $tempResource
 */
class UploadedFile extends YiiUploadedFile {

	/**
	 * @return resource|null
	 * @throws UnknownPropertyException
	 */
	public function getTempResource() {
		return is_resource($resource = $this->__get('_tempResource'))
			?$resource
			:null;
	}

	/**
	 * @inheritDoc
	 */
	public static function getInstance($model, $attribute):self {
		return parent::getInstance($model, $attribute);
	}
}