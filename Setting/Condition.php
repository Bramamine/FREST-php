<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:47 PM
*/

namespace FREST\Setting;

/**
 * Class Condition
 * @package FREST\Setting
 */
class Condition {
	
	/** @var string */
	protected $alias;
	
	
	/**
	 * @param string $alias
	 */
	public function __construct($alias) {
		$this->alias = $alias;
	}

	
	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}
}