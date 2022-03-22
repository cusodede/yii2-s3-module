<?php
declare(strict_types = 1);

namespace cusodede\s3\controllers;

use cusodede\s3\forms\CreateBucketForm;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageSearch;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\traits\traits\ActiveRecordTrait;
use ReflectionException;
use Throwable;
use Yii;
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
	 * @return string|Response
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 * @noinspection PhpUndefinedMethodInspection Существование метода проверяется при инициализации поисковой модели
	 */
	public function actionIndex() {
		$searchModel = new CloudStorageSearch();

		$viewParams = [
			'searchModel' => $searchModel,
			'dataProvider' => $searchModel->search(Yii::$app->request->queryParams),
			'controller' => $this,
		];

		if (Yii::$app->request->isAjax) {
			return $this->viewExists(static::ViewPath().'modal/index') /*если модальной вьюхи для индекса не найдено - редирект*/
				?$this->renderAjax('modal/index', $viewParams)
				:$this->redirect($this->link('index'));/*параметры неважны - редирект произойдёт в modalHelper.js*/
		}

		return $this->render('index', $viewParams);
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function actionCreate() {
		$model = new CloudStorage();
		$s3 = new S3();
		if (ControllerHelper::IsAjaxValidationRequest()) {
			return $this->asJson($model->validateModelFromPost());
		}
		if (true === Yii::$app->request->isPost && true === $model->load(Yii::$app->request->post())) {
			/*todo: вынести в модель*/
			$uploadedFile = UploadedFile::getInstances($model, 'file');
			$bucket = $s3->getBucket($model->bucket);
			$storageResponse = $s3->client->putObject([
				'Bucket' => $bucket,
				'Key' => $model->key,
				'Body' => fopen($uploadedFile[0]->tempName, 'rb')
			]);
			Yii::info(json_encode($storageResponse->toArray()), S3::WEB_LOG);
			$model->bucket = $bucket;
			$model->uploaded = null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL');
			if ($model->save()) {
				return $this->redirect(S3Module::to('test'));
			}
			/* Есть ошибки */
			if (Yii::$app->request->isAjax) {
				return $this->asJson($model->errors);
			}
		}
		/* Постинга не было */
		return (Yii::$app->request->isAjax)
			?$this->renderAjax('modal/create', ['model' => $model, 'buckets' => $s3->getListBucketMap()])
			:$this->render('create', ['model' => $model, 'buckets' => $s3->getListBucketMap()]);
	}

	/**
	 * @inheritdoc
	 */
	public function actionEdit(int $id) {
		if (null === $model = CloudStorage::findOne($id)) throw new NotFoundHttpException();
		$s3 = new S3();

		/** @var ActiveRecordTrait $model */
		if (ControllerHelper::IsAjaxValidationRequest()) {
			return $this->asJson($model->validateModelFromPost());
		}
		if (true === Yii::$app->request->isPost) {
			$uploadedFile = UploadedFile::getInstances($model, 'file');
			$storageResponse = $s3->client->putObject([
				'Bucket' => $model->bucket,
				'Key' => $model->key,
				'Body' => fopen($uploadedFile[0]->tempName, 'rb')
			]);
			Yii::info(json_encode($storageResponse->toArray()), S3::WEB_LOG);

			$model->uploaded = null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL');
			if ($model->save()) {
				return $this->redirect(S3Module::to('/test'));
			}
			/* Есть ошибки */
			if (Yii::$app->request->isAjax) {
				return $this->asJson($model->errors);
			}
		}
		/* Постинга не было */
		return (Yii::$app->request->isAjax)
			?$this->renderAjax('modal/edit', ['model' => $model, 'buckets' => $s3->getListBucketMap()])
			:$this->render('edit', ['model' => $model, 'buckets' => $s3->getListBucketMap()]);
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionCreateBucket():string {
		$createBucketForm = new CreateBucketForm();
		$isCreated = null;
		if (true === Yii::$app->request->isPost && true === $createBucketForm->load(Yii::$app->request->post()) && $createBucketForm->validate()) {
			if (true === $isCreated = (new S3())->createBucket($createBucketForm->name)) {
				$createBucketForm = new CreateBucketForm();
			}
		}
		return $this->render('create-bucket', compact('createBucketForm', 'isCreated'));
	}
}