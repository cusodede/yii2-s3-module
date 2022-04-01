<?php
declare(strict_types = 1);

namespace Helper\Module;

use Codeception\Module\Yii2;
use Codeception\TestInterface;
use Yii;

/**
 * class Yii2Module
 */
class Yii2Module extends Yii2 {
	/**
	 * **HOOK** executed before test
	 *
	 * @param TestInterface $test
	 */
	public function _before(TestInterface $test):void {
		parent::_before($test);

		$this->debugSection("Cache", "Clear cache");
		Yii::$app->cache->flush();
	}
}