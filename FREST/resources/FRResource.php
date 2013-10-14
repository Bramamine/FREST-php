<?php
/**
 * Created by Brad Walker on 6/3/13 at 5:09 PM
*/

require_once(dirname(__FILE__).'/../enums/FRMethod.php');
require_once(dirname(__FILE__).'/../enums/FRFilter.php');
require_once(dirname(__FILE__).'/../settings/FRSetting.php');
require_once(dirname(__FILE__).'/../functions/FRFunction.php');
require_once(dirname(__FILE__).'/../results/FRResourceFunctionResult.php');


/**
 * Class resources
 */
abstract class FRResource {
		
	/**
	 * The limit to use on SQL queries when no limit is specified
	 * 
	 * @var int (optional)
	 */
	private $defaultLimit = 10;

	/**
	 * The maximum limit allowed in the SQL query
	 * 
	 * @var int (optional)
	 */
	private $maxLimit = 25;

	/**
	 * An associative array with keys indicating the name of the SQL table associated
	 * to this resource and value being an array of arrays keyed by the SQL field name
	 * and valued with an array of configuration options for each field. 
	 * 
	 * Note: Normally only one table is used since resources are usually defined in a
	 * a single table. However, if a resource's properties are split up into multiple
	 * tables, then consequently multiple table definitions must be defined.
	 * 
	 * Example:
	 * 
	 * 
	 * 
	 * @var array (required)
	 */
	private $tableSettings;

	/**
	 * An associative array with keys indicating the pseudo name or "alias" of a property
	 * of the resource that can be retrieved using a GET request with values being an 
	 * array of configuration options for each (if any).
	 * 
	 * Example:
	 * 
	 * 
	 * 
	 * @var array (optional)
	 */
	private $readSettings;

	/**
	 * An associative array with keys indicating the pseudo name or "alias" of a property
	 * of the resource that can be used as a filter in a GET request (corresponding to 
	 * values in the WHERE clause in a SQL statement) with values being an array of 
	 * configuration options for each one (if any).
	 * 
	 * Example:
	 *
	 * 
	 * 
	 * @var array (optional)
	 */
	private $conditionSettings;

	/**
	 * An associative array with keys indicating the pseudo name or "alias" of a property
	 * of the resource that can be used to order resources in a GET request (corresponding 
	 * to values in the ORDER BY clause in a SQL statement) with values being an array of
	 * configuration options for each one (if any).
	 * 
	 * Example:
	 *
	 * 
	 * 
	 * @var array (optional)
	 */
	private $orderSettings;

	/**
	 * An associative array with keys indicating the pseudo name or "alias" of a property
	 * of the resource that can be updated using a PUT/POST request with values being an
	 * array of configuration options for each one (if any).
	 * 
	 * Example:
	 *
	 * array(
	 *      'username' => array(),
	 *      'verified' => array()
	 * )
	 * 
	 * Note: In the example above, the ID for the user is not included because (normally)
	 * the ID of a resource should never be changed.
	 * 
	 * @var array (optional)
	 */
	private $updateSettings;

	/**
	 * An associative array with keys indicating the pseudo name or "alias" of a property
	 * of the resource that can be created using a POST request with values being an
	 * array of configuration options for each one (if any).
	 * 
	 * Example:
	 *
	 * array(
	 *      'username' => array(
	 *          Setting::IS_REQUIRED => TRUE (optional - default: TRUE)
	 *      ),
	 *      'verified' => array(
	 *          Setting::IS_REQUIRED => FALSE (optional - default: TRUE)
	 *      )
	 * )
	 * 
	 * Note: In the example above, the ID for the user is not included because (normally) it
	 * is auto-generated by AUTO INCREMENT. However, if the ID of the resource is not an
	 * AUTO INCREMENT field (such as a GUID), one would then want to include the ID here and
	 * make it required as it is a necessary field for the resource.
	 * 
	 * @var array (optional)
	 */
	private $createSettings;

	/**
	 * @var array (optional)
	 */
	private $resourceFunctions;
	

	/**
	 * Created dynamically to quicken table lookup by field
	 * 
	 * @var array
	 */
	private $tablesByField;

	/**
	 * Created dynamically to quicken field lookup by alias
	 *
	 * @var array
	 */
	private $fieldsByAlias;

	/**
	 * Created dynamically to quicken field lookup by field
	 *
	 * @var array
	 */
	private $aliasesByField;

	
	/**
	 * @var FREST
	 */
	private $frest;
	

	/**
	 * This is where all configuration of the subclass resource should be defined. 
	 * @param FREST $frest
	 */
	public function __construct($frest) {
		$this->frest = $frest;
		$this->setup();
	}


	/**
	 * @param string $date
	 * @return int
	 */
	public function timestamp($date) {
		return strtotime($date);
	}
	


	/**
	 * @param string $alias
	 * @return string
	 */
	public function value($alias)
	{
		return '{'.$alias.'}';
	}
	
	
	/**
	 * Where everything is set in subclasses (e.g. read settings, alias settings, table settings, etc.)
	 */
	public abstract function setup();
	
	
	/**
	 * Returns whether or not to validate client's credentials given the specified request.
	 * 
	 * Also returns an array of scopes required for the specified request. These scopes are
	 * validated by SNOAuth2 against the current scope permissions of the API consumer.
	 * 
	 * @param FRRequest $request
	 * @param array $scopes
	 * 
	 * @return bool
	 */
	public function isAuthRequiredForRequest($request, &$scopes = NULL) {
		return FALSE;
	}


	/**
	 * @param string $field
	 * 
	 * @return string
	 */
	public function getTableForField($field) {
		if (!isset($this->tablesByField)) {
			$this->tablesByField = array();
			
			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();
				
				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$this->tablesByField[$fieldSetting->getField()] = $tableSetting->getTable();
				}
			}
		}
		
		if (isset($this->tablesByField[$field])) {
			return $this->tablesByField[$field];
		}
		else {
			return NULL;
		}
	}

	/**
	 * @param string $alias
	 *
	 * @return string
	 */
	public function getFieldForAlias($alias) {
		if (!isset($this->fieldsByAlias)) {
			$this->fieldsByAlias = array();

			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();
				
				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$this->fieldsByAlias[$fieldSetting->getAlias()] = $fieldSetting->getField();
				}
			}
		}

		return isset($this->fieldsByAlias[$alias]) ? $this->fieldsByAlias[$alias] : NULL;
	}
	

	/**
	 * @param string $field
	 * @return string
	 */
	public function getAliasForField($field) {
		if (!isset($this->aliasesByField)) {
			$this->aliasesByField = array();

			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$this->aliasesByField[$fieldSetting->getField()] = $fieldSetting->getAlias();
				}
			}
		}

		return isset($this->aliasesByField[$field]) ? $this->aliasesByField[$field] : NULL;
	}
	

	/**
	 * @param string $alias
	 * 
	 * @return FRFieldSetting
	 */
	public function getFieldSettingForAlias($alias) {
		$aliasField = $this->getFieldForAlias($alias);
		
		/** @var FRTableSetting $tableSetting */
		foreach ($this->tableSettings as $tableSetting) {
			$fieldSettings = $tableSetting->getFieldSettings();
			
			/** @var FRFieldSetting $fieldSetting */
			foreach ($fieldSettings as $field=>$fieldSetting) {
				if ($field == $aliasField) {
					return $fieldSetting;
				}
			}
		}
	}
	
	
	/**
	 * @param FRFieldSetting $fieldSetting
	 * 
	 * @return string
	 */
	public function getIDField(&$fieldSetting = NULL) {
		/** @var FRTableSetting $firstTableSetting */
		$firstTableSetting = reset($this->tableSettings);
		/** @var FRFieldSetting $firstFieldSetting */
		$oldFieldSetting = $firstTableSetting->getFieldSettings();
		$firstFieldSetting = reset($oldFieldSetting);
		
		$fieldSetting = $firstFieldSetting;
		
		return $firstFieldSetting->getField();
	}

	/**
	 * @param string $table
	 * @return string
	 */
	public function getIDFieldForTable($table) {
		$tableSettings = $this->getTableSettings();
		
		/** @var FRTableSetting $tableSetting */
		$tableSetting = $tableSettings[$table];
		
		/** @var FRFieldSetting $firstFieldSetting */
		$firstFieldSetting = reset($tableSetting->getFieldSettings());
		
		return $firstFieldSetting->getField();
	}

	/**
	 * @param array $settingsToModify
	 */
	public function modifyReadSettings($settingsToModify) {
		$readSettings = $this->getReadSettings();

		/** @var FRReadSetting $settingToModify */
		foreach ($settingsToModify as $settingToModify) {
			$key = $settingToModify->getAlias();
			$readSettings[$key] = $settingToModify;
		}

		$this->setReadSettings($readSettings);
	}

	/**
	 * @param array $settingsToModify
	 */
	public function modifyConditionSettings($settingsToModify) {
		$conditionSettings = $this->getConditionSettings();

		/** @var FRConditionSetting $settingToModify */
		foreach ($settingsToModify as $settingToModify) {
			$key = $settingToModify->getAlias();
			$conditionSettings[$key] = $settingToModify;
		}

		$this->setConditionSettings($conditionSettings);
	}

	/**
	 * @param array $settingsToModify
	 */
	public function modifyOrderSettings($settingsToModify) {
		$orderSettings = $this->getOrderSettings();

		/** @var FROrderSetting $settingToModify */
		foreach ($settingsToModify as $settingToModify) {
			$key = $settingToModify->getAlias();
			$orderSettings[$key] = $settingToModify;
		}

		$this->setOrderSettings($orderSettings);
	}

	/**
	 * @param array $settingsToModify
	 */
	public function modifyCreateSettings($settingsToModify) {
		$createSettings = $this->getCreateSettings();

		/** @var FRCreateSetting $settingToModify */
		foreach ($settingsToModify as $settingToModify) {
			$key = $settingToModify->getAlias();
			$createSettings[$key] = $settingToModify;
		}

		$this->setCreateSettings($createSettings);
	}

	/**
	 * @param array $settingsToModify
	 */
	public function modifyUpdateSettings($settingsToModify) {
		$updateSettings = $this->getUpdateSettings();

		/** @var FRUpdateSetting $settingToModify */
		foreach ($settingsToModify as $settingToModify) {
			$key = $settingToModify->getAlias();
			$updateSettings[$key] = $settingToModify;
		}

		$this->setUpdateSettings($updateSettings);
	}
	
	/**
	 * @return int
	 */
	public function getDefaultLimit() {
		return $this->defaultLimit;
	}

	/**
	 * @return int
	 */
	public function getMaxLimit() {
		return $this->maxLimit;
	}

	/**
	 * @return array
	 */
	public function getTableSettings() {
		return $this->tableSettings;
	}

	/**
	 * @return array
	 */
	public function getReadSettings() {
		if (!isset($this->readSettings)) {
			$readSettings = array();
			
			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$readSettings[] = FRSetting::readField($fieldSetting->getAlias());
				}
			}
			
			$this->setReadSettings($readSettings);
		}
		
		return $this->readSettings;
	}

	/**
	 * @return array
	 */
	public function getConditionSettings() {
		if (!isset($this->conditionSettings)) {
			$conditionSettings = array();
			
			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$conditionSettings[] = FRSetting::condition($fieldSetting->getAlias());
				}
			}
			
			$this->setConditionSettings($conditionSettings);
		}

		return $this->conditionSettings;
	}

	/**
	 * @return array
	 */
	public function getOrderSettings() {
		if (!isset($this->orderSettings)) {
			$orderSettings = array();
			
			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$orderSettings[] = FRSetting::order($fieldSetting->getAlias());
				}
			}
			
			$this->setOrderSettings($orderSettings);
		}

		return $this->orderSettings;
	}

	/**
	 * @return array
	 */
	public function getCreateSettings() {
		if (!isset($this->createSettings)) {
			$createSettings = array();
			$idField = $this->getIDField();

			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$alias = $fieldSetting->getAlias();
					$field = $fieldSetting->getField();

					if ($field !== $idField) {
						$createSettings[] = FRSetting::update($alias);
					}
				}
			}

			$this->setUpdateSettings($createSettings);
		}
		
		return $this->createSettings;
	}

	/**
	 * @return array
	 */
	public function getUpdateSettings() {
		if (!isset($this->updateSettings)) {
			$updateSettings = array();
			$idField = $this->getIDField();
			
			/** @var FRTableSetting $tableSetting */
			foreach ($this->tableSettings as $tableSetting) {
				$fieldSettings = $tableSetting->getFieldSettings();

				/** @var FRFieldSetting $fieldSetting */
				foreach ($fieldSettings as $fieldSetting) {
					$alias = $fieldSetting->getAlias();
					$field = $fieldSetting->getField();

					if ($field !== $idField) {
						$updateSettings[] = FRSetting::update($alias);
					}
				}
			}
			
			$this->setUpdateSettings($updateSettings);
		}

		return $this->updateSettings;
	}




	/**
	 * @param int $defaultLimit
	 */
	protected function setDefaultLimit($defaultLimit) {
		$this->defaultLimit = $defaultLimit;
	}

	/**
	 * @param int $maxLimit
	 */
	protected function setMaxLimit($maxLimit) {
		$this->maxLimit = $maxLimit;
	}

	/**
	 * @param array $tableSettings
	 */
	protected function setTableSettings($tableSettings) {
		$keyedSettings = array();

		/** @var FRTableSetting $setting */
		foreach ($tableSettings as $setting) {
			$key = $setting->getTable();
			$keyedSettings[$key] = $setting;
		}

		$this->tableSettings = $keyedSettings;
	}
	
	/**
	 * @param array $readSettings
	 */
	public function setReadSettings($readSettings) {
		$keyedSettings = array();

		/** @var FRFieldReadSetting $setting */
		foreach ($readSettings as $setting) {
			$key = $setting->getAlias();
			$keyedSettings[$key] = $setting;
		}

		$this->readSettings = $keyedSettings;
	}

	/**
	 * @param array $conditionSettings
	 */
	public function setConditionSettings($conditionSettings) {
		$keyedSettings = array();

		/** @var FRConditionSetting $setting */
		foreach ($conditionSettings as $setting) {
			$key = $setting->getAlias();
			$keyedSettings[$key] = $setting;
		}

		$this->conditionSettings = $keyedSettings;
	}

	/**
	 * @param array $orderSettings
	 */
	public function setOrderSettings($orderSettings) {
		$keyedSettings = array();

		/** @var FROrderSetting $setting */
		foreach ($orderSettings as $setting) {
			$key = $setting->getAlias();
			$keyedSettings[$key] = $setting;
		}

		$this->orderSettings = $keyedSettings;
	}

	/**
	 * @param array $createSettings
	 */
	public function setCreateSettings($createSettings) {
		$keyedSettings = array();

		/** @var FRCreateSetting $setting */
		foreach ($createSettings as $setting) {
			$key = $setting->getAlias();
			$keyedSettings[$key] = $setting;
		}

		$this->createSettings = $keyedSettings;
	}

	/**
	 * @param array $updateSettings
	 */
	public function setUpdateSettings($updateSettings) {
		$keyedSettings = array();

		/** @var FRUpdateSetting $setting */
		foreach ($updateSettings as $setting) {
			$key = $setting->getAlias();
			$keyedSettings[$key] = $setting;
		}

		$this->updateSettings = $keyedSettings;
	}

	/**
	 * @return mixed
	 */
	public function getResourceFunctions()
	{
		return $this->resourceFunctions;
	}

	/**
	 * @param mixed $resourceFunctions
	 */
	public function setResourceFunctions($resourceFunctions)
	{
		$keyedFunctions = array();

		/** @var FRResourceFunction $function */
		foreach ($resourceFunctions as $function) {
			$key = $function->getName();
			$keyedFunctions[$key] = $function;
		}

		$this->resourceFunctions = $keyedFunctions;		
	}

	/**
	 * @param stdClass $object
	 * @return stdClass
	 */
	public function formatResourceObject($object) {
		$newObject = new stdClass();

		foreach ($object as $property=>$value) {
			$alias = $this->getAliasForField($property);
			if (isset($alias)) {
				$newObject->$alias = $value;
			}
		}
		
		return $newObject;
	}

	/**
	 * @return \FREST
	 */
	public function getFREST()
	{
		return $this->frest;
	}

	/**
	 * @return \PDO
	 */
	public function getPDO()
	{
		return $this->frest->getConfig()->getPDO();
	}
}