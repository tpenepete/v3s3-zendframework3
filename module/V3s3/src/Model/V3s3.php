<?php

namespace V3s3\Model;

use Zend\Stdlib\ArrayObject;

class V3s3 extends ArrayObject {
	public $id;
	public $timestamp;
	public $date_time;
	public $ip;
	public $hash_name;
	public $name;
	public $data;
	public $mime_type;
	public $status;
	public $timestamp_deleted;
	public $date_time_deleted;
	public $ip_deleted_from;

	public function __construct() {

	}

	/**
	 * Populate from native PHP array
	 *
	 * @param  array $values
	 * @return void
	 */
	public function fromArray(array $values)
	{
		$this->exchangeArray($values);
	}

	/**
	 * Populate from query string
	 *
	 * @param  string $string
	 * @return void
	 */
	public function fromString($string)
	{
		$array = [];
		parse_str($string, $array);
		$this->fromArray($array);
	}

	/**
	 * Serialize to native PHP array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getArrayCopy();
	}
}