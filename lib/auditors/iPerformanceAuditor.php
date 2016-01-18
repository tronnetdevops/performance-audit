<?php
/**
 * @brief Performance Auditor Interface
 *
 * ## Overview
 * This interface should be implemented by all Auditors as a contract to assure successful 
 * interactions with the PerformanceAuditManager.
 *
 * @abstract
 *
 * @author <smurray@ontraport.com>
 * @date 02/17/2014
 */
interface iPerformanceAuditor
{
    /**
     * Class Properties
     */
	
    /**#@+
     * @static
     * @access private
     */
	
    /**
     * @brief Starts an audit.
     *
     * ## Overview
     * Used by PerformanceAuditManager to start an audit session. This will generally be as close to initial
	 * entry point as possible. Setup should be done in `PerformanceAuditor->__construct` where possible. This
	 * should at most start timers and other initialization specifically for auditing.
	 *
     * @see PerformanceAuditManager->start()
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function start();
    
    /**
     * @brief Stops an audit.
     *
     * ## Overview
     * Used by `PerformanceAuditManager` to end an audit session. Avoid doing data normalization or metrics
	 * in this if possible and instead do in `PerformanceAuditor::generateReport`.
	 *
     * @see PerformanceAuditManager->stop()
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function stop($save=true);
    
    /**
     * @brief Generates a usable metrics report.
     *
     * ## Overview
     * Used by `PerformanceAuditManager` to generate metrics reports to be displayed to the end user.
	 * This is where most if not all data crunching should happen, as it is called after all audits
	 * have been stopped. That will assure that performance metrics aren't canablizing themselves.
     *
     * @see PerformanceAuditManager->generateReport();
     *
     * @return {Array} Metrics derived from audit reports' data.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function generateReport($criteria);
    
    /**
     * @brief Saves reports as `PerformanceMetric` in the database.
	 *
     * ## Overview
	 * Used generally when generating reports, or somewhere at the end of a session, ideally after all
	 * Auditors have been stopped to avoid performance metrics distortion.
     *
     * @see PerformanceAuditor->generateReport()
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function save($report=null);
	
	public function modifyDOMString($dom);
    /**#@-*/
}