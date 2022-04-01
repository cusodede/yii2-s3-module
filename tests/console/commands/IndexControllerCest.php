<?php
declare(strict_types = 1);

namespace console\commands;

use app\models\Users;
use cusodede\s3\commands\IndexController;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Exception;

/**
 * Class IndexControllerCest
 */
class IndexControllerCest {

	/**
	 * @return Controller
	 * @throws InvalidConfigException
	 */
	private function initDefaultController():Controller {
		/*Я не могу создать контроллер через методы createController*, т.к. они полагаются на совпадение неймспейсов с путями, а это условие в тестах не выполняется*/
		return Yii::createObject(IndexController::class);
	}

	/**
	 * @return Users
	 * @throws Exception
	 */
	private function initUser():Users {
		return Users::CreateUser()->saveAndReturn();
	}
}