<?php
/**
 * @brief Auditor utilizing built in PHP debugging functions and sessions to audit the network.
 * @todo THIS AUDITOR IS INCOMPLETE
 *
 * ## Overview
 *
 *
 * @uses iPerformanceAuditor
 * @uses PerformanceAuditor
 *
 * @author <smurray@ontraport.com>
 * @date 02/20/2014
 */

class NetworkAuditor extends PerformanceAuditor implements iPerformanceAuditor
{
    /**
     * Class Properties
     */
    /**#@-*/

    /**#@+
     * @static
     * @access public
     */
    
    /**
     * Identifier to be used for `source` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const ID = "network";

    /**
     * Long name for this Auditor.
     * @var String
     */
    const NAME = "Network Auditor";

    /**
     * Identifier to be used for `realm` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const REALM = "network";
	
	const REQUEST_KEY = "__patid";
    
	
    /**
     * Predefined messages used for reporting. Can by extended by public.
     * @var Array
     */
    static public $errorCodeMessages = array();
    /**#@-*/
	
	public $start;
    
    /**
     * Class Methods
     */

    /**#@+
     * @access public
     */

    /**
     * @brief Creates a new Browser Cache Auditor.
     *
     * ## Overview
     * Checks if boomerang JS files exist. Throws exception if not.
     *
     * @see PerformanceAuditManager->__construct();
     *
     * @return {Null} Always unless fatal error or exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 02/27/2014
     */
    public function __construct()
    {
		if (isset($_REQUEST[ PerformanceAuditManager::AUDIT_REQUEST_KEY ]) 
			&& $_REQUEST[ PerformanceAuditManager::AUDIT_REQUEST_KEY ] == "NetworkAuditor")
		{
			error_log("we've got a report for network!");
			
			header("Content-Type: image/jpeg");
			header("Expires: Tue, 25 Feb 2014 20:00:00 GMT");
			
			$this->save($_REQUEST);
			
			exit(0);
		}
		/**
		 * @todo Check that all boomerang pertinent files exist.
		 */
	}
	
	public function save($report=null)
	{
		$this->start = $_COOKIE[ $report[ self::REQUEST_KEY ] ];
		setcookie($report[ self::REQUEST_KEY ]);
		unset($_COOKIE[ $report[ self::REQUEST_KEY ] ]);
		
		return parent::save(array(
            "name"=> self::ID . " ::=> " . microtime(),
            "type"=>0,
            "start"=>floor($this->start*100000),
            "end"=>floor(PERFORMANCE_AUDIT_START_TIME*100000),
            "data"=>json_encode($_SERVER)
        ));
	}
	
    /**
     * @brief Generates a report derived from `xhprof` data.
     *
     * ## Overview
     * This will loop through each report and extract data and create usable metrics.
     *
     * @see PerformanceAuditor->generateReport();
     *
     * @return {Array} Metrics derived from audit reports' data.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
	public function generateReport($criteria)
	{
		/**
		 * @todo Go through apache access logs and record `cachee.php` 200 and 304 hits.
		 */
		return parent::generateReport($criteria);
	}
	
	/**
	 * ## Overview
	 * Merges `$_frontEndScripts` into `PerformanceAuditManager::$performanceVars` which is subsequently
	 * picked up by `InitialController::loadChrome` method, joined into a single string and injected 
	 * into `initial.html` via the `$templateVars` in said method.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
	 */
	public function modifyDOMString($dom)
	{
		$id = "startTime_" . uniqid() . rand(1,100);

		setcookie($id, PERFORMANCE_AUDIT_START_TIME);

		$dom = str_replace("</body>", "<img src=\"/index.php?". PerformanceAuditManager::AUDIT_REQUEST_KEY ."=NetworkAuditor&". self::REQUEST_KEY ."=". $id ."\" style=\"display:none;\"/>".PHP_EOL."</body>", $dom);
		return $dom;
	}
    /**#@-*/
}