<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage;

use Throwable;
use yii\data\ActiveDataProvider;

/**
 * Class CloudStorageSearch
 */
class CloudStorageSearch extends CloudStorage {

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['uploaded', 'deleted'], 'boolean'],
			[['bucket', 'key', 'filename'], 'string', 'max' => 255],
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 * @throws Throwable
	 */
	public function search(array $params):ActiveDataProvider {
		$query = self::find()->distinct()->active();
		$query->scope();

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
				'bucket'
			]
		]);
	}

}
