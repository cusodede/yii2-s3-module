<?php
declare(strict_types = 1);

namespace cusodede\s3;

use yii\web\AssetBundle;

/**
 * Class S3ModuleAssets
 */
class S3ModuleAssets extends AssetBundle {


	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = [];
		$this->js = ['js/s3assets.js'];
		parent::init();
	}
}