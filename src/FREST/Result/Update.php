<?php
/**
 * Created by Brad Walker on 6/5/13 at 2:02 PM
 */

namespace FREST\Result;

/**
 * Class Update
 * @package FREST\Result
 */
class Update extends Result {


	/**
	 */
	public function __construct() {
		$this->httpStatusCode = 200;
	}

	/**
	 * @return \stdClass
	 */
	protected function generateOutputObject() {
		$outputObject = new \stdClass();
		$outputObject->status = $this->httpStatusCode;

		return $outputObject;
	}
	
}