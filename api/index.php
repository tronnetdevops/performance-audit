<?php
	
require_once( dirname(__FILE__) . "/../lib/PerformanceAuditManager.php");

if (isset($_REQUEST["source"]))
{
	if (is_array($auditors))
	{
		$auditors = $_REQUEST["source"];
	} 
	else
	{
		$auditors = array($_REQUEST["source"]);
	}
}
else
{
	$auditors = array("BoomerangPerformanceAuditor");
}

$auditors = $_REQUEST["source"];

$apiResults = array(
	"data" => null,
	"status"=>array(
		"code" => 0,
		"message" => "Success"
	)
);

$pa = new PerformanceAuditManager($auditors);

$results = $pa->getAudits(
	array(
		"from" => $_REQUEST["from"],
		"to" => $_REQUEST["to"]
	)
);

error_log(var_export($results, true));

$apiResults["data"]["totals"] = $results["totals"];

foreach($results["audits"] as $auditor=>$report)
{
	error_log("Gettings reports for: " . $auditor . " who has " . $report["total"] ." reports");
	foreach($report["reports"] as $audit)
	{
		$apiResults["data"]["realms"][$audit->getValue("realm")][$auditor][] = $audit->generateStats(true);
	}
}

echo json_encode($apiResults);