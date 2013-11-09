<?php
/**
 * Created by Brad Walker on 6/8/13 at 11:17 AM
*/

namespace FREST\Spec;

/**
 * Class Condition
 * @package FREST\Spec
 */
class Condition {

	/** @var string $alias */
	protected $alias;
	
	/** @var string $field */
	protected $field;

	/** @var string $value */
	protected $value;

	/** @var int $variableType */
	protected $variableType;

	
	

	/**
	 * @param string $alias
	 * @param string $field
	 * @param mixed $value
	 * @param int $variableType
	 */
	public function __construct($alias, $field, $value, $variableType) {
		$this->alias = $alias;
		$this->field = $field;
		$this->value = $value;
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
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return int
	 */
	public function getVariableType() {
		return $this->variableType;
	}


	
}