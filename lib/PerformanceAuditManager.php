<?php
/**
 * @brief Performance Audit Manager
 *
 * ## Overview
 * Manages Auditors during a performance audit, performing actions such as starting, 
 * stopping and saving results as `PerformanceMetric` objects in the backend. 
 *
 * Initialization happens at the entry point (`index.php`) and immediately begins collecting 
 * auditors via an autoloader function. Once a session has been established, `init()` is 
 * called and checks to make sure the user hasn't exceeded a maximum amount of performance 
 * audits for their account. If they haven't, then the audit begins.
 *
 * @author <smurray@ontraport.com>
 * @date 02/17/2014
 */

define("PERFORMANCE_AUDIT_PATH", dirname(__FILE__ . "/.." ) );
define("PERFORMANCE_AUDIT_LIBRARY_PATH", dirname(__FILE__) );
define("PERFORMANCE_AUDIT_AUDITORS_PATH", PERFORMANCE_AUDIT_LIBRARY_PATH . "/auditors" );
define("PERFORMANCE_AUDIT_BIN_PATH", PERFORMANCE_AUDIT_PATH . "/bin" );
define("PERFORMANCE_AUDIT_ASSETS_PATH", PERFORMANCE_AUDIT_PATH . "/assets" );
define("PERFORMANCE_AUDIT_BUILD_PATH", PERFORMANCE_AUDIT_PATH . "/build" );
define("PERFORMANCE_AUDIT_DATA_PATH", PERFORMANCE_AUDIT_PATH . "/data" );
define("PERFORMANCE_AUDIT_DOMAINS_PATH", PERFORMANCE_AUDIT_PATH . "/domains" );
define("PERFORMANCE_AUDIT_REPORTS_PATH", PERFORMANCE_AUDIT_PATH . "/reports" );

class PerformanceAuditManager
{
    /**
     * Class Properties
     */
    
    /**#@+
     * @static
     * @access public
     */
    
    /**
     * This is the key passed by the front end Auditors. It's used to conditionally include
     * the `PerformanceAuditManager` for incrementing the account audits counter, while not actually 
     * deploying a backend audit (determinable by `PERFORMANCE_AUDIT_MODE` constant).
     * @var String
     */
    const AUDIT_REQUEST_KEY = "__oppa";
    
    /**
     * Key used in `Ontraport` config for storing performance audit data, such as the account audits counter.
     * @var String
     */
    const AUDIT_CONFIG_KEY = "perfaudit";
    
    /**
     * Maximum amount of total audits for an individual user account.
     * @var Integer
     */
    const MAX_AUDITS_PER_ACCOUNT = 15;
    
    /**
     * General catch all error code.
     * @var Integer
     */
    const ERROR_UNKOWN_ISSUE = -1;
    
    /**
     * Debug levels used for logging purposes in the `PerformanceAuditManager::Log` method
     * @var Integer
     */
    const DEBUG_LEVEL_STATUS = 3;
    const DEBUG_LEVEL_WARN = 2;
    const DEBUG_LEVEL_ERROR = 1;
    
    /**
     * User account handle to get config data from.
     * @var AccountHandle
     */
    static public $accountHandle;
    
    /**
     * Config data attached to the account, containing the account audits counter and other data.
     * @var Integer
     */
    static public $config;
    
    /**
     * Status of audit. Anything other than 0 means there is an issue, and audit will be aborted with a message.
     * @var Integer
     */
    static public $status = 0;
    
    /**
     * Determines at which level the `PerformanceAuditManager::Log` method should report to PHP error log.
     * @var Integer
     */
    static public $debug = 3;
    
    /**
     * Can be used by public to determine if `PerformanceAuditManager` is currently auditing.
     * @var Integer
     */
    static public $running = 0;

    /**
     * Composite of all logs accumulated over the lifetime of this `PerformanceAuditManager`.
     * @var Array
     */
    static public $errorMessages = array();
    
    /**
     * Predefined messages used by `PerformanceAuditManager::Log` for reporting. Can by extended by public.
     * @var Array
     */
    static public $errorCodeMessages = array(
        -1 => "Error!",
        0 => "No Error.",
        
        /** Errors */
        "status_is_bad" => array(
            "message"=>"Performance Audit Manager must have a \"0\" status, currently it has \"{STATUS}\"",
            "expansions"=>array(
                "{STATUS}" => "status"
            )
        ),
        
        "exceeded_per_accnt_thresh" => array(
            "message" => "This account has already met the maximum allouted performance audits!"
        ),
        
        /** Warnings */
        "ext_not_loaded" => array(
            "message"=>"The resource \"{RESOURCE_NAME}\" could not be loaded.",
            "expansions"=>array(
                "{RESOURCE_NAME}" => "resource"
            )
        ),
        
        "bad_report" => array(
            "message"=>"The log information provided was inadequate."
        ),
        
        "report_params_missing" => array(
            "message"=>"Level and code parameters are required to be provided to Log method."
        ),
		
		"perf_metric_save_exception" => array(
			"message"=>"There was an exception thrown when attempting to save a Performance Metric."
		)
    );
    /**#@-*/
    
    
    /**#@+
     * @static
     * @access private
     */

    /**
     * List of Auditors to be created during initailzation.
     * @var Array
     */
    static private $_endabledResources = array("XHProfPerformanceAuditor");
    /**#@-*/
    
    /**#@+
     * @access public
     */
    
    /**
     * Initialized Auditors collection at `PerformanceAuditManager`'s initialization. Can be extended by public.
     * @var Array
     */
    public $resources = array();
    
    /**
     * Reports collected from each Auditor, generally when calling their `stop()` method.
     * @var Array
     */
    public $reports = array();
    /**#@-*/
    
    /**
     * Class Methods
     */
         
    /**#@+
     * @access public
     */
    
    /**
     * @brief Creates a new Performance Audit Manager.
     *
     * ## Overview
     * This will register the static `PerformanceAuditManager::Autoloader` method as an autoloader,
     * then attempt to initialize all auditors defined in `PerformanceAuditManager::$endabledResources`.
     *
     * @uses spl_autoload_register()
     * @see index.php
     *
     * @return {Null} Always unless fatal error or exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function __construct($requestedResourceClassNames = array("XHProfPerformanceAuditor"))
    {
        spl_autoload_register("PerformanceAuditManager::Autoloader");
        
        foreach($requestedResourceClassNames as $resource)
        {
            try
            {
                $this->resources[$resource] = new $resource();
            }
            catch (Exception $e)
            {
                self::Warn("ext_not_loaded", array(
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                    "resource" => $resource
                ));
            }
        }
    }
    
    /**
     * @brief Initializes a Performance Audit.
     *
     * ## Overview
     * This will assure that the user account isn't audited more than `MAX_AUDITS_PER_ACCOUNT` total.
     * If the threshold has been met, this will fail out with an error report. If not, then the counter
     * is incremented in the `PerformanceAuditManager::$accountHandle` config data retrieved from 
     * `Ontraport::AccountHandle()`.
     *
     * @uses Ontraport::GetAccountHandle()
     * @uses PerformanceAuditManager->start()
     * @see index.php 
     *
     * @param {Integer} $start Determines if the `PerformanceAuditManager->start()` should be called after setup.
     *
     * @return {Boolean} Return status from `PerformanceAuditManager->start()` or else true if this is a soft init.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function init($start=1)
    {
        if (self::$status)
        {
            return !self::Fail("status_is_bad", array(
                "func" => "init",
                "status" => self::$status
            ));
        }
        
        self::$accountHandle = $ah = Ontraport::GetAccountHandle();
        
        /**
         * @todo Calling $ah->configData() is somehow unsetting $_SESSION["accountHandle"] and forcing log out! 
         * Figure out why.
         */
        self::$config = $conf = null; //json_decode($ah->configData( self::AUDIT_CONFIG_KEY ), true);
        
        if (!is_array($conf))
        {
            self::$config = $conf = array("count" => 0);
        }
        else if ($conf["count"] >= self::MAX_AUDITS_PER_ACCOUNT)
        {
            return !self::Fail("exceeded_per_accnt_thresh", array(
                "func" => "init",
                "status" => self::$status
            ));
        }
        
        return $start ? $this->start() : true;
    }
    
    /**
     * @brief Calls start method on all Auditors.
     *
     * @uses PerformanceAuditManager->resources[@]->start()
     * @see PerformanceAuditManager->init()
     *
     * @return {Boolean} True as returned from assigning the `PerformanceAuditManager->running` flag true.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function start()
    {
        if (self::$status)
        {
            return !self::Fail("status_is_bad", array(
                "func" => "init",
                "status" => self::$status
            ));
        }
        
        foreach($this->resources as $resource)
        {
            $resource->start();
        }
        
        return self::$running = 1;
    }
    
    /**
     * @brief Calls stop method on all Auditors.
     *
     * @uses PerformanceAuditManager->resources[@]->stop()
     *
     * @return {Boolean} True as returned from assigning the `PerformanceAuditManager->running` flag false.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function stop()
    {
        if (self::$status)
        {
            return !self::Fail("status_is_bad", array(
                "func" => "init",
                "status" => self::$status
            ));
        }
        
        foreach($this->resources as $resource)
        {
            $resource->stop();
        }
        
        return self::$running = 0;
    }
    
    /**
     * @brief Generates a composite report from data gathered from all Auditors.
     *
     * @uses PerformanceAuditManager->resources[@]->generateReport()
     *
     * @return {Array} a composite report from data gathered from all Auditors.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function getAudits($criteria)
    {
		$results = array(
			"totals" => array(
				"sources" => count($this->resources),
				"realms" => 0,
				"reports" => 0
			),
			"audits" => array()
		);
		
		$realms = array();

        foreach($this->resources as $resourceName=>$resource)
        {
            $results["audits"][$resourceName] = $resource->generateReport($criteria);
			$results["totals"]["audits"] += $results["reports"][$resourceName]["total"];
			$realms[$resourceName] = 1;
        }
		
		$results["totals"]["realms"] = count($realms);
        
        return $results;
    }
	
    /**
     * @brief Generates a composite report from data gathered from all Auditors.
     *
     * @uses PerformanceAuditManager->resources[@]->generateReport()
     *
     * @return {Array} a composite report from data gathered from all Auditors.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function finish()
    {
        if (self::$status)
        {
            return !self::Fail("status_is_bad", array(
                "func" => "init",
                "status" => self::$status
            ));
        }
        
        foreach($this->resources as $resource)
        {
            $resource->save();
        }
        
        return true;
    }
	
	
	public function BufferDOMLoader($buffer)
	{
	  // replace all the apples with oranges
      foreach($this->resources as $resource)
      {
          $buffer = $resource->modifyDOMString($buffer);
      }
	  return $buffer;
	}
    /**#@-*/
    
    /**#@+
     * @static
     * @access public
     */
    
    /**
     * @brief Logs a failure.
     *
     * ## Overview
     * Used for aborting this audit and reporting what caused the failure. Used in cases such as when
     * the `PerformanceAuditManager::MAX_AUDITS_PER_ACCOUNT` limit has been met.
     *
     * @uses PerformanceAuditManager::Log()
     *
     * @param {String} $errorCode Code to set status too, also passed to Log method as key for error message.
     * @param {Array} $errorData Extra data pertaining to failure.
     *
     * @return {Boolean} True always.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Fail($errorCode = -1, $errorData = null)
    {
        self::$status = $errorCode;
        return self::Log(self::DEBUG_LEVEL_ERROR, $errorCode, $errorData);
    }
    
    /**
     * @brief Logs a warning.
     *
     * ## Overview
     * Used for warnings such as when Auditors can't be loaded.
     *
     * @uses PerformanceAuditManager::Log()
     *
     * @param {String} $warnCode Passed to Log method as key for error message.
     * @param {Array} $warnData Extra data pertaining to warning.
     *
     * @return {Boolean} True always.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Warn($warnCode = 0, $warnData = null)
    {
        return self::Log(self::DEBUG_LEVEL_WARN, $warnCode, $warnData);
    }
    
    /**
     * @brief Logs a status update.
     *
     * @uses PerformanceAuditManager::Log()
     *
     * @param {String} $message Message for status update.
     *
     * @return {Boolean} True always.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Status($message)
    {
        return self::Log(self::DEBUG_LEVEL_STATUS, 0, $message);
    }
    
    /**
     * @brief Logs a status update.
     *
     * ## Overview
     * Stores log messages in `PerformanceAuditManager::$errorMessages` and if they level is below
     * the `PerformanceAuditManager::$debug` level, then the log is forwarded to the PHP error log.
     *
     * This also allows for expansions on replacement keys in the message via an `expansions` array 
     * in the `PerformanceAuditManager$errorCodeMessages` array for error messages referencing live 
     * data. The key to key pair, the replacement key (which is the expansion array) is swapped with 
     * the value of the value key in the `$data` array.
     *
     * @param {Integer} $level Log level of report.
     * @param {String} $code Key used for retrieving message from `PerformanceAuditManager::$errorCodeMessages`.
     * @param {Array} $data Array of data pertinent to report.
     *
     * @return {Boolean} True always.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Log($level, $code, $data = null)
    {
        if (!is_numeric($code) && !is_string($code) || !is_numeric($level))
        {
            return self::Warn("report_params_missing", 0, array($level, $code, $data));
        }
        else if (is_array($data) && is_array(self::$errorCodeMessages[ $code ]))
        {
            $messageDetails = self::$errorCodeMessages[ $code ];
            if (is_array($messageDetails["expansions"]))
            {
                foreach($messageDetails["expansions"] as $key=>$valueDataKey)
                {
                    $message = str_replace($key, $data[$valueDataKey], $messageDetails["message"]);
                }
            }
            else
            {
                $message = $messageDetails["message"];
            }
        }
        else if (is_string($data))
        {
            $message = $data;
            unset($data);
        }
        else
        {
            return self::Warn("bad_report", 0, array($level, $code, $data));
        }
        
        self::$errorMessages[] = array(
            "level" => $level,
            "code" => $code,
            "message" => $message,
            "data" => $data
        );
        
        if ($level <= self::$debug)
        {
            error_log( $message );
            error_log( var_export($data, true));
        }
        
        return true;
    }

    /**
     * @brief Increment the account audit counter.
     *
     * @uses PerformanceAuditManager::$accountHandle->configData()
     *
     * @return {Integer} Current counter index.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function incCounter()
    {
        self::$config["count"]++;
        self::$accountHandle->configData(self::AUDIT_CONFIG_KEY, json_encode(self::$config));
        return self::$config["count"];
    }
    
    /**
     * @brief Increment the account audit counter.
     *
     * @uses PerformanceMetricController
     * @uses PerformanceMetricController->save()
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Save($report)
    {
		$ah = class_exists("Ontraport") ? Ontraport::GetAccountHandle() : $_SESSION["accountHandle"];
		if ($ah)
		{
			$report["aid"] = $ah->accountID();
			try{
		        $metric = new PerformanceMetric();
		        $metric->save($report);
			}
			catch(Exception $e)
			{
                self::Warn("perf_metric_save_exception", array(
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                    "report" => $report
                ));
			}

		}
		else
		{
			if (class_exists("Ontraport"))
			{
			}
		}

    }
    
    /**
     * @brief Autoloader for Auditors.
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public static function Autoloader($className)
    {
		if ($className == "PerformanceMetric")
		{
			$path = PERFORMANCE_AUDIT_LIBRARY_PATH;
		}
		else
		{
			$path = PERFORMANCE_AUDIT_AUDITORS_PATH;
		}
        
		$path .= "/" . $className .".php";
        if (file_exists($path))
        {
            include_once($path);
            return;
        }
    }
    /**#@-*/
}