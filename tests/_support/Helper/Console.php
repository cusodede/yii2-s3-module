<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use JsonException;
use Yii;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

/**
 * class Console
 */
class Console extends Module
{
    /**
     * **HOOK** executed before test
     * @param TestInterface $test
     * @throws JsonException
     */
    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        $this->debugSection('Cache', 'Clear cache');
        if (Yii::$app && Yii::$app->cache) {
            Yii::$app->cache->flush();
        }
    }
}
