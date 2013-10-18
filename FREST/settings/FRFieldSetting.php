<?php
/**
 * Created by Brad Walker on 6/5/13 at 12:02 PM
*/

/**
 * Class FRFieldSetting
 */
class FRFieldSetting {

	/** @var string */
	protected $alias;
	
	/** @var string */
	protected $field;
	
	/** @var int */
	protected $variableType;


	/**
	 * @param string $alias
	 * @param string $field
	 * @param int $variableType
	 */
	public function __construct($alias, $field, $variableType = FRVariableType::STRING) {
		$this->alias = $alias;
		$this->field = $field;
		$this->variableType = $variableType;
	}

	/**
	 * @return string
	 */
	public function getAlias() {
		return $this->alias;
	}
	
	/**
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return int
	 */
	public function getVariableType() {
		return $this->variableType;
	}
	
	
}