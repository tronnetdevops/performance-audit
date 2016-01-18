<?php
/**
 * @brief Performance Metric Model
 *
 * ## Overview
 *
 * 
 * ## DATABASE
 * ```
 * CREATE DATABASE `performance_audit` COLLATE = `utf8_unicode_ci`;
 * CREATE TABLE IF NOT EXISTS `performance_audit`.`metrics` (
 *     `id` INT(11) NOT NULL AUTO_INCREMENT, 
 *     `aid` INT(20) NOT NULL, 
 *     `name` VARCHAR(255) NOT NULL, 
 *     `type` TINYINT(2) NOT NULL DEFAULT 0,
 *     `realm` ENUM("backend", "frontend", "network", "system", "remote") NOT NULL,
 *     `source` ENUM("xhprof", "xdebug", "boomerang", "yslow", "pagespeed") NOT NULL,
 *     `created` TIMESTAMP NOT NULL DEFAULT NOW(),
 *     `start` INT(10) NOT NULL,
 *     `end` INT(10) NOT NULL,
 *     `data` TEXT, 
 *     `counter` INT(3) NOT NULL DEFAULT 0,
 *     PRIMARY KEY (`id`) 
 * ) ENGINE=`InnoDB` DEFAULT CHARSET=`utf8` COLLATE=`utf8_unicode_ci` AUTO_INCREMENT=1;
 * ```
 * 
 * @see PerformanceMetricController
 *
 * @author <smurray@ontraport.com>
 * @date 01/22/2014
 */

class PerformanceMetric
{
    /**@* {String} Name of database. */
	const _DATABASE = "performance_audit";
	
    /**@* {String} Name of table in the database. */
    const _TABLE = "metrics";

    /**@* {String} Path to the moonray database credentials. */
	const _CONFIG_PATH = "../data/db.conf";
    

    /**@* {Array} Used as a means of validating and sanitizing object properties before they reach the database. */
    static protected $attrs = array(
        "id"=>"is_numeric",
        "aid"=>"is_numeric",
        "name"=>"is_string",
        "type"=>"isset",
        "realm"=>"isset",
        "source"=>"isset",
        "created"=>"empty",
        "start"=>"isset",
        "end"=>"isset",
        "data"=>"is_string",
        "counter"=>"is_numeric"
    );
	
    /**@* {Array} Defines which fields are required for an entry. */
	static protected $required = array("aid", "name", "type", "realm", "source", "start", "end", "data");
    
    /**@* {PDO} A reference to an already opened database (used for TableLock scenarios). */
    static private $_db;
	
	static private $_db_creds;
	    
    /**@* {Boolean} Denotes if the current object was just created or not (may not exist in database yet!). */
    public $isNew = true;
	
    /**@* {Integer} `id` of the Performance Metric object in the database. */
    private $_id;
    
    /**@* {Array} A temporary storage of data that will be used to create a new object in the database. */
    private $_data = array();


	/**
	 * Class Methods
	 */

    /**
     * @brief Creates a new Performance Object.
     *
     * ## Overview
     *
     * @uses PDO
     *
     * @param {AccountHandle}      $ah Account handle used to interact with user data and database.
     * @param {Integer}            $id (optional) An `id` of an existing Performance Metric object in the database.
     * @param {PDO}                $db (optional) A reference to an already opened database (used for TableLock scenarios).
     * 
     * @return {PerformanceObject} Always unless fatal error or exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function __construct($id=false, PDO $db=null)
    {
        
        if (is_numeric($id))
        {
            $this->_id = $this->_data["id"] = $id;
            $this->isNew = false;
        }
        
        if ($db)
        {
            self::$_db = $db;
        }
        else
		{
			self::GetDB();
        }
    }
    
    /**
     * @brief Sets the value of a single specific property.
     *
     * ## Overview 
     * This will update the database for a specific proptery. If this object hasn't been
     * saved yet (ie, doesn't have an `id`), the values will be stored in the $_data 
     * array and gathered during the save process.
     *
     * @throws Exception
     * @throws DBUniqueNameException
     *
     * @param {String}    $name Key to an existing property that must be in the $attrs list.
     * @param {Mixed}     $value A value which must pass validation and sanitization from Sanitize.
     *
     * @return {Boolean} True on success, otherwise an Exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function setValue($name, $value)
    {
        if ($this->_id)
        {
            if (isset(self::$attrs[$name]))
            {
                $cleanAttrs = self::Sanitize($name, $value);
                
                if (is_array($cleanAttrs) && !empty($cleanAttrs)){
                    $statement = self::$_db->prepare("UPDATE `". self::_TABLE ."` SET `". $name ."`=? WHERE `id`=?");
                    
                    if (!$statement->execute(array($cleanAttrs[$name], $this->_id)))
                    {
                        if ($statement->errorCode() == 23000)
                        {
                            throw new DBUniqueNameException("Performance Metric name already exists in the database!");
                        }
                        else
                        {
                            throw new Exception("Couldn't update Performance Metric property $name! ". var_export($statement->errorInfo(), true));
                        }
                    }
                }
                else 
                {
                    throw new Exception("$name contains invalid data! ");
                }
            }
            else 
            {
                throw new Exception("$name isn't a property of the Performance Metric object!");
            }
        }
        else
        {
            $this->_data[$name] = $value;
        }
        
        return true;
    }
    
    /**
     * @brief Sets values to multiple properties of a single object.
     *
     * ## Overview
     *
     * @throws Exception
     * @throws DBUniqueNameException
     *
     * @param {Array} $data A key/value pair of properties to be updated that must pass sanitization.
     *
     * @return {Boolean} True on success, otherwise an Exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function setValues($data)
    {

        if (is_array($data))
        {
            if ($this->_id)
            {
                $cleanAttrs = self::Sanitize($data);
                if (is_array($cleanAttrs) && !empty($cleanAttrs))
                {
                    $query = "UPDATE `". self::_TABLE ."` SET";
                    $names = array_keys($cleanAttrs);
                    $values = array_values($cleanAttrs);
                
                    if (!empty($names))
                    {
                        // Add name placeholders
                        $query .= " `". implode($names, "`=?, `") . "`=?";
                    }
                
                    $query .= " WHERE `id`=?";
                    $values[] = $this->_id;
                
                    $statement = self::$_db->prepare($query);

                    if (!$statement->execute($values))
                    {
                        if ($statement->errorCode() == 23000)
                        {
                            throw new DBUniqueNameException("Performance Metric name already exists in the database!");
                        }
                        else
                        {
                            throw new Exception("Couldn't save Performance Metric to database! ". var_export($statement->errorInfo(), true));
                        }
                    }
                }
                else 
                {
                    /**
                     * @todo Provide more details of which properties failed.
                     */
                    throw new Exception("Request contains invalid data!");
                }
            }
            else
            {
                /**
                 * Store data for when save is called.
                 */
                $this->_data = array_merge($this->_data, $data);
            }
        }
        else
        {
            /**
             * @todo Should we warn that the param provided was unusable?
             */
            return false;
        }
        
        return true;
    }
    
    /**
     * @brief Provides the value to a desired property.
     *
     * ## Overview
     *
     * @uses PDO
     *
     * @throws Exception
     *
     * @param {Array} $name A property name that must exist in the $attrs list.
     *
     * @return {Mixed} Value of property on success, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function getValue($name)
    {
        if (isset(self::$attrs[$name]))
        {
            if ($this->_id && $name !== "id")
            {
                $statement = self::$_db->prepare("SELECT `". $name ."` FROM `". self::_TABLE ."` WHERE `id`=?");
                if (!$statement->execute(array($this->_id)))
                {
                    throw new Exception("Couldn't get Performance Metric property $name! ". var_export($statement->errorInfo(), true));
                }
                
                $data = $statement->fetch(PDO::FETCH_ASSOC);
            }
            else
            {
                $data = $this->_data;
            }
        }
        else
        {
            /**
             * @todo Could warn that property doesn't exist/isn't allowed for this object.
             */
            return false;
        }
        
        return $data[$name];
    }
    
    /**
     * @brief Provides the values to a set of properties.
     *
     * ## Overview
     * This will retrieve as set of properties and their values from the backend. If a param is
     * not provided, or is an empty array or string containing a wildcard, all property values will
     * be returned.
     *
     * @uses PDO
     *
     * @throws Exception
     *
     * @param {Mixed} $names An array of property names that must exist in the $attrs list.
     *
     * @return {Mixed} Value of property on success, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function getValues($names="*")
    {

        if ($names == "*" || is_array($names) && empty($names))
        {
            $names = array_keys(self::$attrs);
        }
        
        if (is_array($names) && !empty($names))
        {
            $cleanAttrs = array();
            foreach($names as $pos=>$name)
            {
                if (isset(self::$attrs[$name]))
                {
                    $cleanAttrs[] = $name;
                }
                else
                {
                    /**
                     * @todo Could warn that property doesn't exist/isn't allowed for this object.
                     */
                    continue;
                }
            }
            if (!empty($cleanAttrs))
            {
                $statement = self::$_db->prepare("SELECT `". implode($cleanAttrs, "`, `") ."` FROM `". self::_TABLE ."` WHERE `id`=?");
                if (!$statement->execute(array($this->_id)))
                {
                    throw new Exception("Couldn't get Performance Metric property $name! ". var_export($statement->errorInfo(), true));
                }
                $ret = $statement->fetch(PDO::FETCH_ASSOC);
            }
        }
        else
        {
            /**
             * @todo Should we warn that the param provided was unusable?
             */
            return false;
        }
        
        return $ret;
    }
    
    /**
     * @brief Creates a new object record in the database.
     *
     * ## Overview
     *
     * @throws Exception
     * @throws DBUniqueNameException
     *
     * @param {Array} $data A key/value pair of properties to be set that must pass sanitization.
     *
     * @return {Boolean} True on success, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function save($data)
    {

        if (!$this->_id)
        {
            $attrs = array_merge($this->_data, $data);
            
            /**
             * Name must be unique and not empty
             */
            if (!isset($attrs["name"]))
            {
                throw new Exception("Performance Metric object are required to have a name!");
            }
            
            $cleanAttrs = self::Sanitize($attrs);
            if (is_array($cleanAttrs) && !empty($cleanAttrs))
            {
                $query = "INSERT INTO `". self::_TABLE ."`";
                $names = array_keys($cleanAttrs);
                $values = array_values($cleanAttrs);
                
                if (!empty($names))
                {
                    // Add property name map
                    $query .= " (`". implode($names, "`, `") . "`) ";
                    // Add property value placeholders
                    $query .= "VALUES (". implode(array_fill(0, count($names), "?"), ", ") . ")";
                }

                $statement = self::$_db->prepare($query);
				// error_log($query);
				// error_log(var_export($values, true));
                if (!$statement->execute($values))
                {
                    if ($statement->errorCode() == 23000)
                    {
                        throw new DBUniqueNameException("Performance Metric name already exists in the database!");
                    }
                    else
                    {
                        throw new Exception("Couldn't save Performance Metric to database! ". var_export($statement->errorInfo(), true));
                    }
                }
                
                if (!$this->_id = $this->_data["id"] = self::$_db->lastInsertId())
                {
                    throw new Exception("Couldn't get new Performance Metric id from database! ". var_export($statement->errorInfo(), true));
                }
            }
            else
            {
                /**
                 * @todo Didn't pass sanitization, should we warn?
                 */
                throw new Exception("Didn't pass sanitization!");
            }
        }
        else
        {
            /**
             * @todo An id was provided, so should object attempt to adopt that id, or pass on data to update?
             */
            throw new Exception("No ID was provided!");
        }
        
        return true;
    }
    
    /**
     * @brief Increments the `counter` for a record from the database by `id`.
     *
     * @return {Boolean} True on success, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function incrementCounter()
    {
        if ($this->_id)
        {
            $statement = self::$_db->prepare("UPDATE `". self::_TABLE ."` SET `counter`=`counter` + 1 WHERE `id`=?");
            if (!$statement->execute(array($this->_id)))
            {
                throw new Exception("Couldn't increment Performance Metric counter! ". var_export($statement->errorInfo(), true));
            }
        }
        else
        {
            /**
             * @todo No id when trying to update, should we warn them?
             */
            return false;
        }
    
        return true;
    }
    
    /**
     * @brief Deletes a record from the database by `id`.
     *
     * @return {Boolean} True on success, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public function delete()
    {
        if ($this->_id)
        {
            $statement = self::$_db->prepare("DELETE FROM `". self::_TABLE ."` WHERE `id`=?");
            if (!$statement->execute(array($this->_id)))
            {
                throw new Exception("Couldn't delete Performance Metric from database! ". var_export($statement->errorInfo(), true));
            }
        }
        else
        {
            /**
             * @todo No id when trying to delete, should we warn them?
             */
            return false;
        }
    
        return true;
    }
    
    /**
     * @brief Sanitizes data that will potentially be sent to the database.
     *
     * @param {Mixed}    $name array of key/value pairs of properties, or a single property name.
     * @param {String}    $value A value to a property when $name is a string.
     *
     * @return {Boolean} True if all properties were valid and sanitized, otherwise an Exception is thrown or false is returned.
     *
     * @author <smurray@ontraport.com>
     * @date 01/22/2014
     */
    public static function Sanitize($name, $value=false)
    {
        $ret = array();

        if (is_array($name))
        {
            $data = $name;
            foreach($data as $attrName=>$value)
            {
                if (isset(self::$attrs[$attrName]))
                {
                    $attrTypeCheck = self::$attrs[$attrName];
                    if (!is_callable($attrTypeCheck) || $attrTypeCheck($value))
                    {
                        switch($attrName)
                        {
                            case "name":
                                if (empty($value))
                                {
                                    return false;
                                }
                                break;
                            case "data":
                                /**
                                 * @todo check if valid json, or if array, convert to json
                                 */
                                break;
                        }
                    
                        $ret[$attrName] = $value;
                    }
                    else
                    {
                        /**
                         * @todo Create a way to return list of failed type checks
                         */
						throw new Exception("$attrName failed check on value: " . var_export($value, true));
                    }
                }
            }
        }
        else if (is_string($name) && isset(self::$attrs[$name]) )
        {

            $attrTypeCheck = self::$attrs[$name];
            if (!is_callable($attrTypeCheck) || $attrTypeCheck($value))
            {
                $ret[$name] = $value;
            }
            else
            {
                /**
                 * @todo Create a way to return list of failed type checks
                 */
				throw new Exception("$name failed check on value: " . var_export($value, true));
            }
        }
        else
        {
            /**
             * @todo No data, should we be worried?
             */
            return false;
        }

		// If any required fields are missing, fail out.
		$missingRequiredFields = array_diff( self::$required, array_keys($ret));
		if (count($missingRequiredFields))
		{
            /**
             * @todo Warn or throw exception that there are missing properties.
             */
			throw new Exception("Missing required fields: " . var_export($missingRequiredFields, true));
		}
        
        return $ret;
    }
	
	public function generateStats($criteria)
	{
		$className = ucfirst($this->getValue("source")) . "PerformanceAuditor";
		return method_exists($className, "GenerateStats") ? call_user_func(array($className, "GenerateStats"), $this, $criteria) : $this->getValues();
	}

	public static function GetDBCreds()
	{
		if (!self::$_db_creds)
		{
			self::$_db_creds = parse_ini_file( dirname(__FILE__) . "/" . self::_CONFIG_PATH );
		}
		
		return self::$_db_creds;
	}
	
	public static function GetDB()
	{
		if (!self::$_db)
		{
			$creds = self::GetDBCreds();
			
			self::$_db = new PDO(
				"mysql:host=" . $creds["DB_HOST"] . ";dbname=".self::_DATABASE, 
				$creds["DB_USER"], 
				$creds["DB_PASS"],  
				array(PDO::ATTR_TIMEOUT, 1)
			);
		}
		
		return self::$_db;
	}
}