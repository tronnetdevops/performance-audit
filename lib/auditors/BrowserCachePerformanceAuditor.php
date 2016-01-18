<?php
/**
 * @todo THIS AUDITOR IS INCOMPLETE
 *
 * @todo Make this a shell auditor with emphasis in the generateReport() method. 
 * It will be used when collecting stats for display.
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

class BrowserCachePerformanceAuditor extends PerformanceAuditor implements iPerformanceAuditor
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
    const ID = "browsercache";

    /**
     * Long name for this Auditor.
     * @var String
     */
    const NAME = "Browser Cache Primed State Auditor";

    /**
     * Identifier to be used for `realm` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const REALM = "frontend";
    
	
    /**
     * Predefined messages used for reporting. Can by extended by public.
     * @var Array
     */
    static public $errorCodeMessages = array();
    /**#@-*/
    
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
			&& $_REQUEST[ PerformanceAuditManager::AUDIT_REQUEST_KEY ] == "BrowserCachePerformanceAuditor")
		{
			error_log("we've got a report for browsercache!");
			
			header("Content-Type: image/jpeg");
			header("Expires: Tue, 25 Feb 2014 20:00:00 GMT");
	
			// error_log(var_export($_SERVER, true));
			if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]))
			{
				header("HTTP/1.1 304 Not Modified");
			}
			else
			{
				header("Last-Modified: " . date("D, d M Y H:i:s \G\M\T"));
			}
			
			$this->save($_REQUEST);
			
			exit(0);
		}
		/**
		 * @todo Check that all boomerang pertinent files exist.
		 */
	}
	
	public function save($report=null)
	{
		return parent::save(array(
            "name"=> self::ID . " ::=> " . microtime(),
            "type"=>0,
            "start"=>floor(PERFORMANCE_AUDIT_START_TIME*100000),
            "end"=>floor(microtime()*100000),
            "realm"=>self::REALM,
            "source"=>self::ID,
            "data"=>json_encode($report)
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
		return array();
	}
	
	public function modifyDOMString($dom)
	{
		$dom = str_replace("</body>", "<img src=\"/index.php?". PerformanceAuditManager::AUDIT_REQUEST_KEY ."=BrowserCachePerformanceAuditor\" style=\"display:none;\"/>".PHP_EOL."</body>", $dom);
		return $dom;
	}
    /**#@-*/
}