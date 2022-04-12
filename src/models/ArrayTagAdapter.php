<?php
declare(strict_types = 1);

namespace cusodede\s3\models;

/**
 * Адаптер для работы с тегами, как с массивом ключ-значение
 */
class ArrayTagAdapter {

	/**
	 * @var string[]
	 */
	private array $_tags = [];

	/**
	 * @return string
	 */
	public function __toString():string {
		return http_build_query($this->_tags);
	}

	/**
	 * @param null|string[] $tags
	 */
	public function __construct(?array $tags = null) {
		if (null !== $tags) {
			foreach ($tags as $tag => $value) {
				$this->setTag(is_string($tag)?$tag:$value, $value);
			}
		}
	}

	/**
	 * Устанавливает название и значение тега.
	 * @param string $name
	 * @param string|null $value Если `null`, то в качестве значения будет использовано название тега.
	 * @return void
	 */
	public function setTag(string $name, ?string $value = null):void {
		$this->_tags[$name] = $value??$name;
	}

	/**
	 * @return string[]
	 */
	public function getTags():array {
		return $this->_tags;
	}

	/**
	 * Добавляет тег, если он ещё не установлен
	 * @param string $name
	 * @param string|null $value Если `null`, то в качестве значения будет использовано название тега.
	 * @return bool True, если тег установлен
	 */
	public function addTag(string $name, ?string $value = null):bool {
		if (isset($this->_tags[$name])) return false;
		$this->setTag($name, $value);
		return true;
	}
}
