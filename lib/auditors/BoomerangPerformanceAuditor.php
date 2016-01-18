<?php
/**
 * @brief Boomerang Front-End Auditor.
 * @todo THIS AUDITOR IS INCOMPLETE
 *
 * ## Overview
 * This Auditor utilizes the `boomerang` Javascript plugin for profiling the frontend call stack. 
 * With `boomerang` having been developed by the Yahoo development team, it is highly optimized
 * for production level code and should bear negligible overhead during an audit.
 *
 * The data gathered during a normal audit includes:
 *
 * ```
 * ## Beacon Parameters
 * 
 * The beacon that hits your server will have several parameters. Each plugi$enabledResourceswn parameters, so if you have custom plugins set up, you'll get parameters from them as well. This is what you get from the default install:
 * 
 * ## Boomerang Parameters
 * 
 * v
 *      Version number of the boomerang library in use.
 * u
 *      URL of page that sends the beacon.
 *
 * ## Roundtrip Plugin Parameters
 * 
 * t_done
 *      [optional] Perceived load time of the page.
 * t_page
 *      [optional] Time taken from the head of the page to page_ready.
 * t_resp
 *      [optional] Time taken from the user initiating the request to the first byte of the response.
 * t_other
 *      [optional] Comma separated list of additional timers set by page developer. Each timer is of the format name|value. The following timers may be included:
 * t_load
 *      [optional] If the page were prerendered, this is the time to fetch and prerender the page.
 * t_prerender
 *      [optional] If the page were prerendered, this is the time from start of prefetch to the actual page display. It may only be useful for debugging.
 * t_postrender
 *      [optional] If the page were prerendered, this is the time from prerender finish to actual page display. It may only be useful for debugging.
 * boomerang
 *      The time it took boomerang to load up from first byte to last byte
 * boomr_fb
 *      [optional The time it took from the start of page load to the first byte of boomerang. Only included if we know when page load started.
 * r
 *      URL of page that set the start time of the beacon.
 * r2
 *      [optional] URL of referrer of current page. Only set if different from r and strict_referrer has been explicitly turned off.
 * rt.start
 *      Specifies where the start time came from. May be one of cookie for the start cookie, navigation for the W3C navigation timing API, csi for older versions of Chrome or gtb for the Google Toolbar.
 * rt.bstart
 *      The timestamp when boomerang showed up on the page
 * rt.end
 *      The timestamp when the done() method was called
 * 
 * ## Bandwidth & Latency Plugin
 * 
 * bw
 *      User's measured bandwidth in bytes per second
 * bw_err
 *       95% confidence interval margin of error in measuring user's bandwidth
 * lat
 *       User's measured HTTP latency in milliseconds
 * lat_err
 *       95% confidence interval margin of error in measuring user's latency
 * bw_time
 *       Timestamp (seconds since the epoch) on the user's browser when the bandwidth and latency was measured
 * ```
 *
 * @uses iPerformanceAuditor
 * @uses PerformanceAuditor
 *
 * @author <smurray@ontraport.com>
 * @date 02/27/2014
 */

class BoomerangPerformanceAuditor extends PerformanceAuditor implements iPerformanceAuditor
{
    /**
     * Class Properties
     */
    
    /**#@+
     * @static
     * @access public
     */
    
    /**
     * Identifier to be used for `source` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const ID = "boomerang";

    /**
     * Long name for this Auditor.
     * @var String
     */
    const NAME = "Yahoo's Boomerang Javascript Profiling Plugin Auditor";

    /**
     * Identifier to be used for `realm` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const REALM = "frontend";

    /**
     * Strings of HTML that will be injected into the head of `initial.html` to load boomerang plugin.
     * @var Array
     */
	static private $_frontEndScripts = array(
		"../assets/boomerang/init.js",
		"../assets/boomerang/boomerang.js"
	);
    /**#@-*/
    
    /**
     * Class Methods
     */

    /**#@+
     * @access public
     */

    /**
     * @brief Creates a new Boomerang Auditor.
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
			&& $_REQUEST[ PerformanceAuditManager::AUDIT_REQUEST_KEY ] == "BoomerangPerformanceAuditor")
		{
			if (isset($_REQUEST["img"]))
			{
				$imgName = explode("?", $_REQUEST["img"]);
				$imgName = $imgName[0];
				
				$name = dirname(__FILE__) . "/../build/boomerang/images/" . $imgName;
				$fp = fopen($name, "rb");

				header("Content-Type: image/png");
				header("Content-Length: " . filesize($name));

				fpassthru($fp);
				exit(0);
			}

			$this->save($_REQUEST);
			exit(0);
		}
		/**
		 * @todo Check that all boomerang pertinent files exist.
		 */
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
		// $report = array();
		// $entries = parent::generateReport($criteria);
		// // 
		// // foreach($entries as $entry)
		// // {
		// // 	
		// // }
		// 
		// return array(
		// 	"total" => count($entries),
		// 	"reports" => $entries
		// );
		
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
		foreach(self::$_frontEndScripts as $filename)
		{
			$contents = file_get_contents( dirname(__FILE__) . "/" . $filename );
			$dom = str_replace("<head>", "<head><script type=\"text/javascript\">".$contents.PHP_EOL."</script>", $dom);
		}
		
		return $dom;
	}
    /**#@-*/
	
	
	static public function GenerateStats(PerformanceMetric $pm, $criteria = null)
	{

		$result = $pm->getValues();
		
		$result["ho baby"] = "yeah";
		
		return $result;
	}
}