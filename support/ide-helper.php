<?php

declare(strict_types=1);

use yii\BaseYii;

class Yii extends BaseYii
{
    /**
     * @var BaseApplication
     */
    public static $app;
}

abstract class BaseApplication extends yii\base\Application
{
}
