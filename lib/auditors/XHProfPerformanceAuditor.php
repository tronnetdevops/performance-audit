<?php 
/**
 * @brief XHProf PHP extention Auditor.
 *
 * ## Overview
 * This Auditor utilizes the `xhprof` PHP extension for profiling backend call stack. With
 * `xhprof` having been developed by the Facebook development team, it is highly optimized
 * for production level code and should bear negligible overhead during an audit.
 *
 * The data gathered during a normal audit includes:
 *
 * ```
 * Array
 * (
 *    [foo==>bar] => Array
 *        (
 *            [ct] => 2        # number of calls to bar() from foo()
 *            [wt] => 37       # time in bar() when called from foo()
 *            [cpu] => 0       # cpu time in bar() when called from foo()
 *            [mu] => 2208     # change in PHP memory usage in bar() when called from foo()
 *            [pmu] => 0       # change in PHP peak memory usage in bar() when called from foo()
 *        )
 * )
 * ```
 *
 * @uses iPerformanceAuditor
 * @uses PerformanceAuditor
 *
 * @author <smurray@ontraport.com>
 * @date 02/20/2014
 */

class XHProfPerformanceAuditor extends PerformanceAuditor implements iPerformanceAuditor
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
    const ID = "xhprof";

    /**
     * Long name for this Auditor.
     * @var String
     */
    const NAME = "XHProf PHP Profiling Auditor";

    /**
     * Identifier to be used for `realm` column in the `performance_audit`.`metric` table.
     * @var String
     */
    const REALM = "backend";
    
    /**
     * Error code thrown if `xhprof` extension isn't available. Also used for error message reported.
     * @var Integer
     */
    const ERR_XHPROF_NOT_INSTALLED = 1;
    
    /**
     * Different modes in which to call the `xhprof_enable` function with. Extendable by public.
     * @var Array
     */
    static public $modes = array();
    
    /**
     * Functions that `xhprof` should ignore profiling during audit. Passed to `xhprof_enable`. Extendable by public.
     * @var Array
     */
    static public $ignored = array(
        "ignored_functions" =>  array(
            "call_user_func",
            "call_user_func_array"
        )
    );
    
    /**
     * Predefined messages used for reporting. Can by extended by public.
     * @var Array
     */
    static public $errorCodeMessages = array(
        1 => "Couldn't find \"xhprof_enable\" function."
    );
    
    /**
     * Mode to be used for `xhprof_enable`.
     * @var Integer
     */
    public $mode = 0;
    /**#@-*/
    
    /**
     * Class Methods
     */
         
    /**#@+
     * @access public
     */
    
    /**
     * @brief Creates a new XHProf Auditor.
     *
     * ## Overview
     * Checks if `xhprof` extension is available and throws an Exception if not. If it is, then it will 
     * frontload the `XHProfPerformanceAuditor::$modes` array with known desirable modes. It will also
     * modify the default `xhprof.output_dir` php ini setting to be the current file's directory.
     *
     * @uses ini_set()
     * @see PerformanceAuditManager->__construct();
     *
     * @return {Null} Always unless fatal error or exception is thrown.
     *
     * @author <smurray@ontraport.com>
     * @date 02/20/2014
     */
    public function __construct($mode = 0)
    {
        if (!function_exists("xhprof_enable"))
        {
            throw new Exception(self::$errorCodeMessages[self::ERR_XHPROF_NOT_INSTALLED], self::ERR_XHPROF_NOT_INSTALLED);
        }
        
        if (!count(self::$modes))
        {
            self::$modes = array_merge( self::$modes, array(
                0 => null,
                1 => XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY, // Extra info flag
                2 => XHPROF_FLAGS_NO_BUILTINS // Skip PHP built-ins like strlen, etc.
            ));
        }
        
        $this->mode = $mode;
        
        ini_set("xhprof.output_dir", dirname(__FILE__));
    }
    
    /**
     * @brief Starts an audit.
     *
     * @uses xhprof_enable()
     * @see PerformanceAuditManager->start();
     *
     * @return {Boolean} Return status from `xhprof_enable` function.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function start()
    {
        return xhprof_enable(self::$modes[ $this->mode ] /*, self::$ignored */);
    }
    
    /**
     * @brief Stops an audit.
     *
     * ## Overview
     * This stops the `xhprof` profiler and collects the stats returns. Then it will package the stats
     * into a JSON object and create a report. Reports are saved in `XHProfPerformanceAuditor->_reports`.
     *
     * @uses xhprof_disable()
     * @see PerformanceAuditManager->stop();
     *
     * @return {Boolean} True always from appending a new report to `XHProfPerformanceAuditor->_reports`.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function stop($save=true)
    {
        $data = json_encode(xhprof_disable());
        return $this->_reports[] = array(
            "name"=> self::ID . " ::=> " . microtime(),
            "type"=>0,
            /**
             * @todo Collect a real start time from the start function.
             */
            "start"=>microtime(),
            "end"=>microtime(),
            "realm"=>self::REALM,
            "source"=>self::ID,
            "data"=>$data
        );
    }
    
    /**
     * @brief Generates a report derived from `xhprof` data.
     *
     * ## Overview
     * This will loop through each report and extract data and create usable metrics.
     *
     * @uses xhprof_disable()
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
    /**#@-*/
}