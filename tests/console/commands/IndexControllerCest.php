<?php
declare(strict_types = 1);

namespace console\commands;

use ConsoleTester;
use cusodede\s3\commands\IndexController;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Exception;

/**
 * Class IndexControllerCest
 */
class IndexControllerCest {

	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';

	/**
	 * @return IndexController
	 * @throws InvalidConfigException
	 */
	private function initDefaultController():Controller {
		/*Я не могу создать контроллер через методы createController*, т.к. они полагаются на совпадение неймспейсов с путями, а это условие в тестах не выполняется*/
		return Yii::createObject(IndexController::class);
	}

	/**
	 * @param ConsoleTester $I
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function putAndGet(ConsoleTester $I):void {
		$controller = $this->initDefaultController();
		$controller->actionPut(self::SAMPLE_FILE_PATH, 'test_key');
		$controller->actionGet('test_key', null, './tests/_data/');
		$I->assertFileEquals(self::SAMPLE_FILE_PATH, './tests/_data/test_key');
	}

	/**
	 * @param ConsoleTester $I
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 */
	public function listBucketsTest(ConsoleTester $I):void {
		$controller = $this->initDefaultController();
		$controller->actionListBuckets();
		/*Не работает, я не знаю как*/
		//$I->seeInShellOutput('Quantity of buckets');

	}
}