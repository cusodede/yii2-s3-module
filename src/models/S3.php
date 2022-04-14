<?php
declare(strict_types = 1);

namespace cusodede\s3\models;

use cusodede\s3\models\cloud_storage\CloudStorage;
use Aws\Result;
use Aws\S3\S3Client;
use cusodede\s3\S3Module;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\PathHelper;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\web\NotFoundHttpException;

/**
 * @property S3Client $client
 */
class S3 extends Model {
	public ?CloudStorage $storage = null;
	private string $host;
	private string $login;
	private string $password;
	private int $connectTimeout = 10;
	private int $timeout = 10;
	private ?string $certPath;
	private ?string $certPassword;
	private ?string $defaultBucket;

	/**
	 * @inheritDoc
	 */
	public function __construct(array $config = []) {
		$this->host = S3Module::param("connection.host");
		$this->login = S3Module::param("connection.login");
		$this->password = S3Module::param("connection.password");
		$this->connectTimeout = (int)S3Module::param("connection.connect_timeout", $this->connectTimeout);
		$this->timeout = (int)S3Module::param("connection.timeout", $this->timeout);
		$this->certPath = S3Module::param("connection.cert_path");
		$this->certPassword = S3Module::param("connection.cert_password");
		$this->defaultBucket = S3Module::param("defaultBucket");
		parent::__construct($config);
	}

	/**
	 * @return S3Client
	 */
	public function getClient():S3Client {
		return new S3Client([
			'version' => 'latest',
			'region' => '', // обязательный параметр. Из доки AWS: Specifies which AWS Region to send this request to.
			'endpoint' => $this->host,
			'use_path_style_endpoint' => true, // определяет вид URL. Если true, то http://minio:9002/test/, иначе http://test.minio:9002/
			'http' => $this->getHttp(),
			'credentials' => [
				'key' => $this->login,
				'secret' => $this->password
			]
		]);
	}

	/**
	 * @return array
	 */
	private function getHttp():array {
		$http = [
			'connect_timeout' => $this->connectTimeout,
			'timeout' => $this->timeout
		];

		if (null !== $this->certPath) {
			$http[] = [$this->certPath, $this->certPassword??'']; // второй элемент пароль, не думаю что будем его использовать
		}

		return $http;
	}

	/**
	 * Выбор корзины при скачивании и загрузке.
	 * Если она указана явно, или указана в связанном хранилище, берём оттуда.
	 * Иначе выбираем хранилище по умолчанию. Если оно не указано, то выбирается последняя корзина из списка имеющихся.
	 * @param string|null $bucket
	 * @return string
	 * @throws Throwable
	 */
	public function getBucket(?string $bucket = null):string {
		if (null !== $bucket || (null !== $bucket = $this?->storage?->bucket) || (null !== $bucket = $this->defaultBucket)) return $bucket;
		$buckets = ArrayHelper::getValue($this->client->listBuckets()->toArray(), 'Buckets', []);
		$latestBucket = count($buckets) - 1;
		return ArrayHelper::getValue($buckets, $latestBucket.'.Name', new NotFoundHttpException("No buckets configured/found"));
	}

	/**
	 * Получение ключа: если он не указан напрямую, то взять из связанного хранилища.
	 * @param string|null $key
	 * @return string
	 */
	public function getKey(?string $key = null):string {
		return $key??$this?->storage?->key;
	}

	/**
	 * Сохраняем объект в хранилище
	 * @param string $filePath path to the file we want to upload
	 * @param string|null $bucket
	 * @param string|null $fileName
	 * @param string[]|null $tags
	 * @throws Exception
	 * @throws Throwable
	 */
	public function saveObject(string $filePath, ?string $bucket = null, ?string $fileName = null, ?array $tags = null):void {
		if (null === $fileName) {
			$fileName = basename($filePath);
		}
		$key = static::GetFileNameKey($fileName);
		$storageResponse = $this->putObject($filePath, $key, $bucket, $tags);
		$this->storage = new CloudStorage([
			'bucket' => $this->getBucket($bucket),
			'key' => $this->getKey($key),
			'filename' => $fileName,
			'uploaded' => null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL'),
			'size' => (false === $filesize = filesize($filePath))?null:$filesize
		]);
		$this->storage->tags = $tags??[];
		$this->storage->save();
	}

	/**
	 * Получаем объект из хранилища по заданному ключу
	 * @param string|null $key
	 * @param string|null $bucket
	 * @param null|string $savePath Если null, то данные нужно выковыривать из потока
	 * @return Result
	 * @throws Throwable
	 */
	public function getObject(?string $key = null, ?string $bucket = null, ?string $savePath = null):Result {
		return $this->client->getObject([
			'Key' => $this->getKey($key),
			'Bucket' => $this->getBucket($bucket),
			'SaveAs' => $savePath
		]);
	}

	/**
	 * Получаем объект с тегами из хранилища по заданному ключу
	 * @param string|null $key |null
	 * @param string|null $bucket
	 * @return Result
	 * @throws Throwable
	 */
	public function getObjectTagging(?string $key = null, ?string $bucket = null):Result {
		return $this->client->getObjectTagging([
			'Key' => $this->getKey($key),
			'Bucket' => $this->getBucket($bucket)
		]);
	}

	/**
	 * Устанавливает массив тегов объекта
	 * @param string|null $key
	 * @param string|null $bucket
	 * @param array|null $tags Массив тегов, null для очистки тегов
	 * @return Result
	 * @throws Throwable
	 */
	public function setObjectTagging(?string $key = null, ?string $bucket = null, ?array $tags = null):Result {
		return null === $tags
			?$this->client->deleteObjectTagging([
				'Key' => $this->getKey($key),
				'Bucket' => $this->getBucket($bucket)
			])
			:$this->client->putObjectTagging([
				'Key' => $this->getKey($key),
				'Bucket' => $this->getBucket($bucket),
				'Tagging' => ['TagSet' => (new ArrayTagAdapter($tags))->tagSet()]
			]);
	}

	/**
	 * Возвращает массив тегов объекта
	 * @param string|null $key
	 * @param string|null $bucket
	 * @return array
	 * @throws Throwable
	 */
	public function getTagsArray(?string $key = null, ?string $bucket = null):array {
		return ArrayHelper::map($this->client->getObjectTagging([
			'Key' => $this->getKey($key),
			'Bucket' => $this->getBucket($bucket)
		])->get('TagSet'), 'Key', 'Value');
	}

	/**
	 * Загрузка файла в хранилище
	 * @param string $filePath
	 * @param string|null $key
	 * @param string|null $bucket
	 * @param string[]|null $tags
	 * @return Result
	 * @throws Exception
	 * @throws Throwable
	 */
	public function putObject(string $filePath, ?string &$key = null, ?string &$bucket = null, ?array $tags = null):Result {
		return $this->client->putObject([
			'Key' => $key = $this->getKey($key??static::GetFileNameKey(PathHelper::ExtractBaseName($filePath))),
			'Bucket' => $bucket = $this->getBucket($bucket),
			'Body' => fopen($filePath, 'rb'),
			'Tagging' => (string)(new ArrayTagAdapter($tags))
		]);
	}

	/**
	 * @param resource $resource
	 * @param string $fileName
	 * @param string|null $key
	 * @param string|null $bucket
	 * @return Result
	 * @throws Exception
	 * @throws Throwable
	 */
	public function putResource($resource, string $fileName, ?string &$key = null, ?string &$bucket = null):Result {
		return $this->client->putObject([
			'Key' => $key = $this->getKey($key??static::GetFileNameKey($fileName)),
			'Bucket' => $bucket = $this->getBucket($bucket),
			'Body' => $resource
		]);
	}

	/**
	 * Получаем список корзин
	 * @return array[]
	 */
	public function getListBucketMap():array {
		$res = $this->client->listBuckets()->toArray();
		$buckets = [];
		foreach ($res['Buckets'] as $bucket) {
			$buckets[$bucket['Name']] = $bucket['Name'];
		}
		return $buckets;
	}

	/**
	 * Создание корзины
	 * @param string $name
	 * @return bool
	 * @throws Throwable
	 */
	public function createBucket(string $name):bool {
		return null !== ArrayHelper::getValue($this->client->createBucket(['Bucket' => $name])->toArray(), 'Location');
	}

	/**
	 * @param string $fileName
	 * @return string
	 * @throws Exception
	 */
	public static function GetFileNameKey(string $fileName):string {
		return implode('_', [Yii::$app->security->generateRandomString(), $fileName]);
	}

	/**
	 * Удаляем объект из хранилища
	 * @param string $key
	 * @param string|null $bucket
	 * @return Result
	 * @throws Throwable
	 */
	public function deleteObject(string $key, string &$bucket = null):Result {
		return $this->client->deleteObject([
			'Bucket' => $bucket = $this->getBucket($bucket),
			'Key' => $key
		]);
	}
}
