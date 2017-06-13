<?php

namespace V3s3\Exception;

use Exception;
use RuntimeException;

class V3s3Exception extends RuntimeException {
	const INVALID_METHOD=1;
	const OBJECT_NAME_TOO_LONG=2;
	const PUT_EMPTY_OBJECT_NAME=3;
	const DELETE_EMPTY_OBJECT_NAME=4;
	const POST_EMPTY_OBJECT_NAME=5;
	const POST_INVALID_REQUEST=6;
	const DB_CANNOT_UPDATE_ROW_NO_MATCH=7;

	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}