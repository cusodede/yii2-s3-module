<?php
declare(strict_types = 1);

namespace cusodede\s3\controllers;

use cusodede\s3\forms\CreateBucketForm;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageSearch;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class IndexController
 */
class IndexController extends Controller {

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return S3Module::param('viewPath', parent::getViewPath());
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 * @throws Throwable
	 */
	public function actionIndex():string {
		$searchModel = new CloudStorageSearch();

		$viewParams = [
			'searchModel' => $searchModel,
			'dataProvider' => $searchModel->search(Yii::$app->request->queryParams),
			'controller' => $this,
		];

		return $this->render('index', $viewParams);
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionView(int $id):string {
		if (null === $model = CloudStorage::findOne($id)) throw new NotFoundHttpException();
		return $this->render('view', compact('model'));
	}

	/**
	 * @param int $id
	 * @param string|null $mime Для переопределения mime-type
	 * @return Response
	 * @throws NotFoundHttpException
	 * @throws Throwable
	 * @throws Exception
	 */
	public function actionDownload(int $id, ?string $mime = null):Response {
		if (null === $response = CloudStorage::Download($id, $mime)) throw new NotFoundHttpException("Model is not found!");
		return $response;
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function actionUpload() {
		$s3 = new S3();
		$model = new CloudStorage(['bucket' => $s3->getBucket()]);
		if (true === Yii::$app->request->isPost && true === $model->load(Yii::$app->request->post()) && null !== $uploadedFile = UploadedFile::getInstance($model, 'file')) {
			if ($model->uploadInstance($uploadedFile)) return $this->redirect(S3Module::to('index'));
		}
		return $this->render('upload', ['model' => $model, 'buckets' => $s3->getListBucketMap()]);
	}

	/**
	 * @param int $id
	 * @return string|Response
	 * @throws InvalidConfigException
	 * @throws NotFoundHttpException
	 * @throws Throwable
	 */
	public function actionEdit(int $id) {
		if (null === $model = CloudStorage::findOne($id)) throw new NotFoundHttpException();
		if (true === Yii::$app->request->isPost && null !== $uploadedFile = UploadedFile::getInstance($model, 'file')) {
			if ($model->uploadInstance($uploadedFile)) return $this->redirect(S3Module::to('index'));
		}
		return $this->render('edit', ['model' => $model, 'buckets' => (new S3())->getListBucketMap()]);
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionCreateBucket():string {
		$createBucketForm = new CreateBucketForm();
		$isCreated = false;
		if (true === Yii::$app->request->isPost && true === $createBucketForm->load(Yii::$app->request->post()) && $createBucketForm->validate() && true === $isCreated = (new S3())->createBucket($createBucketForm->name)) {
			$createBucketForm = new CreateBucketForm();
		}
		return $this->render('create-bucket', compact('createBucketForm', 'isCreated'));
	}
}