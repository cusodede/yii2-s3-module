<?php
declare(strict_types = 1);

namespace cusodede\s3\components\web;

use pozitronik\helpers\ReflectionHelper;
use ReflectionException;
use yii\base\UnknownClassException;
use yii\web\UploadedFile as YiiUploadedFile;

/**
 * Class UploadedFile
 * Перекрытие стандартного компонента для доступа к _tempResource
 * @property-read ?resource $resource
 */
class UploadedFile extends YiiUploadedFile {

	/**
	 * @return ?resource
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public function getResource() {
		return ([] === $resource = ReflectionHelper::getValue(parent::class, '_tempResource', $this))?null:$resource;
	}

	/**
	 * @inheritDoc
	 * Для приведения типа
	 */
	public static function getInstance($model, $attribute):self {
		return parent::getInstance($model, $attribute);
	}

}