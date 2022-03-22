<?php
declare(strict_types = 1);

namespace cusodede\s3\controllers;

use cusodede\s3\models\cloud_storage\CloudStorage;
use Throwable;
use yii\base\Exception;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class DownloadController
 */
class DownloadController extends Controller {

	/**
	 * @param int $id
	 * @param string|null $mime Для переопределения mime-type
	 * @return Response
	 * @throws NotFoundHttpException
	 * @throws Throwable
	 * @throws Exception
	 */
	public function actionIndex(int $id, ?string $mime = null):Response {
		if (null === $response = CloudStorage::Download($id, $mime)) throw new NotFoundHttpException("Model is not found!");
		return $response;
	}

}
