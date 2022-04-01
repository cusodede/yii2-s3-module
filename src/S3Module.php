<?php
declare(strict_types = 1);

namespace cusodede\s3;

use pozitronik\traits\traits\ModuleTrait;
use Yii;
use Exception;
use yii\base\Module as YiiModule;
use yii\console\Application;

/**
 * Class S3Module
 */
class S3Module extends YiiModule {
	use ModuleTrait;

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();

		try {
			if (Yii::$app instanceof Application) {
				$this->controllerNamespace = 'vendor\cusodede\s3\commands';
				$this->setControllerPath('vendor\cusodede\yii2-s3-module\src\commands');
			}
		} catch (Exception $e) {
			Yii::error($e->getTraceAsString(), 's3');
		}
	}
}
