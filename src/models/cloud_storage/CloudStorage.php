<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage;

use Aws\S3\Exception\S3Exception;
use cusodede\s3\components\web\UploadedFile;
use cusodede\s3\models\ArrayTagAdapter;
use cusodede\s3\models\cloud_storage\active_record\CloudStorageAR;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use GuzzleHttp\Psr7\Stream;
use pozitronik\helpers\ArrayHelper;
use Throwable as ThrowableAlias;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class CloudStorage
 *
 * @property string[] $tags
 */
class CloudStorage extends CloudStorageAR {

	public const DEFAULT_MIME_TYPE = 'application/octet-stream';
	/**
	 * ext => mime
	 */
	public const MIME_TYPES = [
		'apk' => 'application/vnd.android.package-archive',
		'ipa' => 'application/octet-stream'
	];

	public $file;

	/**
	 * @var string[] tag label => tag key
	 */
	private array $_tags = [];

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return array_merge(
			parent::rules(),
			[['file', 'file', 'maxSize' => S3Module::param('maxUploadFileSize')]]
		);
	}

	/**
	 * Пытается найти mime-тип по имени файла, с возможностью переопределить его локально
	 * @param string $fileName
	 * @return string
	 * @throws ThrowableAlias
	 */
	public static function GetMimeTypeByExtension(string $fileName):string {
		if ('' !== $ext = pathinfo($fileName, PATHINFO_EXTENSION)) {
			$ext = strtolower($ext);
			return ArrayHelper::getValue(S3Module::param('mimeTypes', static::MIME_TYPES), $ext, S3Module::param('defaultMimeType', static::DEFAULT_MIME_TYPE));
		}
		if (null !== $result = FileHelper::getMimeTypeByExtension($fileName)) return $result;
		return S3Module::param('defaultMimeType', static::DEFAULT_MIME_TYPE);
	}

	/**
	 * @param int $id
	 * @param string|null $mime
	 * @return Response|null
	 * @throws ThrowableAlias
	 * @throws Exception
	 */
	public static function Download(int $id, ?string $mime = null):?Response {
		if (null !== $model = static::findOne($id)) {
			try {
				$s3 = new S3();
				$result = $s3->getObject($model->key, $model->bucket);
				/** @var Stream $body */
				$body = $result->get('Body');
				$body->rewind();
				return Yii::$app->response->sendContentAsFile($body->getContents(), $model->filename, [
					'mimeType' => $mime??static::GetMimeTypeByExtension($model->filename)
				]);
			} catch (S3Exception $e) {
				throw new NotFoundHttpException("Error in storage: {$e->getMessage()}");
			}
		}
		return null;
	}

	/**
	 * @param UploadedFile $instance
	 * @return bool
	 * @throws Exception
	 * @throws NotFoundHttpException
	 * @throws ThrowableAlias
	 */
	public function uploadInstance(UploadedFile $instance):bool {
		try {
			$s3 = new S3();
			$this->filename = empty($this->filename)?$instance->name:$this->filename;
			$key = empty($this->key)?S3::GetFileNameKey($this->filename):$this->key;
			$bucket = empty($this->bucket)?$s3->getBucket($this->bucket):$this->bucket;
			$storageResponse = (null === $resource = $instance->resource)
				?$s3->putObject($instance->tempName, $key, $bucket)
				:$s3->putResource($resource, $instance->name, $key, $bucket);
			/*Передать атрибуты напрямую не выйдет*/
			$this->key = $key;
			$this->bucket = $bucket;
			$this->size = $instance->size;
			if (S3Module::param('deleteTempFiles', true)) $instance->deleteTempFile();
			$this->uploaded = null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL');
			return $this->uploaded && $this->save();
		} catch (S3Exception $e) {
			throw new NotFoundHttpException("Error in storage: {$e->getMessage()}");
		}
	}

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		/*При создании объекта теги подсасываются из бд*/
		$this->_tags = $this->relatedTags;
	}

	/**
	 * @return string[]
	 */
	public function getTags():array {
		return (new ArrayTagAdapter($this->_tags))->getTags();
	}

	/**
	 * @param string[] $tags
	 */
	public function setTags(array $tags):void {
		$this->_tags = $tags;
		$this->on($this->isNewRecord?static::EVENT_AFTER_INSERT:static::EVENT_AFTER_UPDATE, function(Event $event) {
			foreach ($event->data as $label => $key) {
				$tag = new CloudStorageTags(['tag_label' => $label, 'tag_key' => $key]);
				BaseActiveRecord::link('relatedTags', $tag);
			}
		}, $this->tags);
	}

}
