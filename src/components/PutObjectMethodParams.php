<?php
declare(strict_types = 1);

namespace cusodede\s3\components;

/**
 * Для записи файла в хранилище предусмотрен широкий набор опций. Пусть они будут инкапсулированы в одном месте.
 */
class PutObjectMethodParams {
	/**
	 * @var array
	 */
	private array $_tags = [];

	/**
	 * Добавление названия и значения тега в опции.
	 * @param string $name
	 * @param string|null $value если `null`, то в качестве значения будет использовано название тега.
	 * @return void
	 */
	public function setTag(string $name, ?string $value = null):void {
		$this->_tags[$name] = $value??$name;
	}

	/**
	 * @return string|null
	 */
	public function composeTags():?string {
		return [] === $this->_tags
			?null
			:http_build_query($this->_tags);
	}

	/**
	 * @return array
	 */
	public function getTags():array {
		return $this->_tags;
	}
}
