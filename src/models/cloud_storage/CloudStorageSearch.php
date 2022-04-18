<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage;

use cusodede\s3\models\cloud_storage\active_record\CloudStorageTagsAR;
use Throwable;
use yii\data\ActiveDataProvider;

/**
 * Class CloudStorageSearch
 */
class CloudStorageSearch extends CloudStorage {

	/**
	 * @var null|string $tagsFilter Фильтр по значению тега
	 */
	public ?string $tagsFilter = null;

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['id', 'model_key',], 'integer'],
			[['uploaded', 'deleted'], 'boolean'],
			[['bucket', 'key', 'filename', 'model_name',], 'string', 'max' => 255],
			[['tagsFilter'], 'string']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return array_merge(
			parent::attributeLabels(),
			['tagsFilter' => 'Теги']
		);
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 * @throws Throwable
	 */
	public function search(array $params):ActiveDataProvider {
		$query = self::find()->active();
		$query->joinWith(['relatedTags']);

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);

		$this->setSort($dataProvider);
		$this->load($params);

		if (!$this->validate()) return $dataProvider;

		$this->filterData($query);

		return $dataProvider;
	}

	/**
	 * @param $query
	 * @return void
	 * @throws Throwable
	 */
	private function filterData($query):void {
		$query->andFilterWhere([self::tableName().'.id' => $this->id]);
		$query->andFilterWhere([self::tableName().'.deleted' => $this->deleted]);
		$query->andFilterWhere([self::tableName().'.uploaded' => $this->uploaded]);
		$query->andFilterWhere(['like', self::tableName().'.filename', $this->filename]);
		$query->andFilterWhere(['like', self::tableName().'.bucket', $this->bucket]);
		$query->andFilterWhere(['like', self::tableName().'.key', $this->key]);
		$query->andFilterWhere(['model_name' => $this->model_name]);
		$query->andFilterWhere(['model_key' => $this->model_key]);
		$query->andFilterWhere(['like', CloudStorageTagsAR::tableName().'.tag_key', $this->tagsFilter]);
	}

	/**
	 * @param $dataProvider
	 */
	private function setSort($dataProvider):void {
		$dataProvider->setSort([
			'defaultOrder' => ['id' => SORT_DESC],
			'attributes' => [
				'id',
				'deleted',
				'uploaded',
				'key',
				'filename',
				'bucket',
				'model_name',
				'model_key',
				'tagsFilter' => [
					'asc' => [CloudStorageTagsAR::tableName().'.tag_key' => SORT_ASC],
					'desc' => [CloudStorageTagsAR::tableName().'.tag_key' => SORT_DESC]
				]
			]
		]);
	}

}
