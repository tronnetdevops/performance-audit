<?php
// error_reporting(-1);
define("PERFORMANCE_AUDIT_START_TIME", microtime());
if (!isset($_GET["__opc"]))
{
	/**
	 * Forceably preventing performance audits until ready to go live.
	 */
	$rand = 1; //rand(1,25);
	define("PAGELOADING", 1);
	
	if(isset($_REQUEST["__oppa"]) && session_id() == "") {
		error_log("starting session");
		require dirname(__FILE__) . "/../../../../bootstrap.php";
		
	    session_start();
	}

	ob_start();
}
else
{
	/**
	 * Forceably preventing performance audits until ready to go live.
	 */
	$rand = 1; //rand(1,100);
}

/** 
 * Conditional performance audit stuff!
 * Only included if a front end Auditor is sending back stats, in which case, 
 * continue the audit all the way through, or on a 1/100 chance if AJAX request, 
 * or 1/25 change if initial page load, for randomized sampling
 */
if (isset($_REQUEST["__oppa"]) ||  $rand == 1)
{
	define("PERFORMANCE_AUDIT_MODE", 1);

	require_once(dirname(__FILE__) . "/../../lib/PerformanceAuditManager.php");
	if (!isset($_GET["__opc"]))
	{
		$PerfAuditManager = new PerformanceAuditManager(
			array(
				"BoomerangPerformanceAuditor",
				"BrowserCachePerformanceAuditor",
				"NetworkAuditor"
			)
		);
	}
	else
	{
		$PerfAuditManager = new PerformanceAuditManager(
			array(
				"XHProfPerformanceAuditor",
			)
		);
	}
}
else
{
	define("PERFORMANCE_AUDIT_MODE", 0);
}