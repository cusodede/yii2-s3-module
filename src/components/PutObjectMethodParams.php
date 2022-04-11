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
    private array $tags = [];

    /**
     * Добавление названия и значения тега в опции.
     * @param string $name
     * @param string|null $value если `null`, то в качестве значения будет использовано название тега.
     * @return void
     */
    public function addTag(string $name, ?string $value = null):void {
        $this->tags[$name] = $value ?? $name;
    }

    /**
     * @return string|null
     */
    public function composeTags():?string {
        if ([] === $this->tags) {
            return null;
        }

        $str = '';
        foreach ($this->tags as $name => $value) {
            $str .= "$name=$value&";
        }

        return rtrim($str, '&');
    }

    /**
     * @return array
     */
    public function getTags():array {
        return $this->tags;
    }
}
