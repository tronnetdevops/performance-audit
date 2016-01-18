<?php 
/**
 * @brief Performance Auditor Base Class
 *
 * ## Overview
 * This class should be extended by all Auditors to acquire basic functionality utilized by
 * the PerformanceAuditManager.
 *
 * @abstract
 *
 * @author <smurray@ontraport.com>
 * @date 02/17/2014
 */

abstract class PerformanceAuditor
{
    /**
     * Class Properties
     */
    
    /**#@+
     * @access public
     */
    
    /**
     * Can be used by public to determine if an Auditor is currently auditing.
     * @var Integer
     */
    public $running = 0;

    /**
     * Status of an audit. Anything other than 0 means there is an issue, and audit will be aborted with a message.
     * @var Integer
     */
    public $status = 0;
    /**#@-*/

    /**#@+
     * @access protected
     */

    /**
     * Reports collected when audit finishes, generally when calling the `PerformanceAuditor->stop()` method.
     * @var Array
     */
    protected $_reports = array();
    /**#@-*/
    
    /**
     * Class Methods
     */

    /**#@+
     * @access public
     */
	
    /**
     * @brief Starts an audit.
     *
     * @see InitialController::loadChrome();
     *
     * @return {Boolean} Return status from `xhprof_enable` function.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function start()
	{
		return true;
	}
    
    /**
     * @brief Stops an audit.
     *
     * ## Overview
     * Only here to satisfy interface contract.
	 *
     * @see PerformanceAuditManager->stop();
     *
     * @return {Boolean} True always from appending a new report to `XHProfPerformanceAuditor->_reports`.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function stop($save=true)
	{
		return true;
	}

    /**
     * @brief Saves all reports to database.
     *
     * ## Overview
     * This will loop through each report and save it to the database as `PerformanceMetrics` in 
	 * the `performance_audit`.`metrics` table.
     *
     * @uses PerformanceAuditor->save()
     * @see PerformanceAuditManager->generateReport();
     *
     * @return {Array} Metrics derived from audit reports' data.
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function generateReport($criteria)
    {
		$caller = get_called_class();
		$entries = array();
		$results = array();
		$db = PerformanceMetric::GetDB();

		if ($db)
		{
			$query = "SELECT `id` FROM `". PerformanceMetric::_TABLE ."` WHERE `source` = '".$caller::ID."'";
			$where = array();
			
			if (is_array($criteria))
			{
				if (isset($criteria["from"]))
				{
					$where["`created` >= ?"] = date("Y-m-d H:i:s", strtotime($criteria["from"]));
				}
				
				if (isset($criteria["to"]))
				{
					$where["`created` <= ?"] = date("Y-m-d H:i:s", strtotime($criteria["to"]));
				}
				
				if (count($where))
				{
					$query .= " AND " . implode(" AND ", array_keys($where));
				}
			}
			
			// error_log("Executing query: " . $query);
			// error_log("Executing keys: " . implode(",", array_values($where)));
			
			$statement = $db->prepare($query);
			$statement->execute(array_values($where));
			
			$entries = $statement->fetchAll(PDO::FETCH_ASSOC);
		}
		else
		{
			
		}
		
		foreach($entries as $entry)
		{
			$results[] = new PerformanceMetric($entry["id"]);
		}
		
		return array(
			"total" => count($results),
			"reports" => $results
		);
    }

    /**
     * @brief Saves reports as `PerformanceMetric` in the database.
     *
     * @uses PerformanceAuditManager::Save()
     * @see XHProfPerformanceAuditor->generateReport()
     *
     * @return {Null}
     *
     * @author <smurray@ontraport.com>
     * @date 02/19/2014
     */
    public function save($report=null)
    {
		if (is_array($report))
		{
			$report = array_merge($report, array(
				"source" => self::ID,
				"realm" => self::REALM
			));
			
	        PerformanceAuditManager::Save($report);
			return $report;
		}
		else
		{
	        foreach($this->_reports as $report)
	        {
				$report = array_merge($report, array(
					"source" => self::ID,
					"realm" => self::REALM
				));
				
	            PerformanceAuditManager::Save($report);
	        }
			
			return $this->_reports;
		}
    }
	
	public function modifyDOMString($dom)
	{
		return $dom;
	}
    /**#@-*/
}