<?php
/**
 * @brief Checks if Performance Audit database exists.
 *
 * ## Overview
 * This file is responsible for attempting to establish a connection to the `performance_audit` database.
 * It will import credentials from the normal 3.0 config files. If database doesn't exist, this will 
 * save the credentials to a file and exit with a status code of "2". Once exited, the credentials are then 
 * picked up by server_update.sh, removed and then used to create the database with the SQL in the 
 * `/library/PerformanceAudit/data/performance_audit.database.sql` file.
 *
 * @date 02/26/2014
 * @author <smurray@ontraport.com>
 */
if (PHP_SAPI)
{
	define("DB_CREDENTIALS_KEY", "moonray_creds");
	define("DB_DATA_KEY", "performance_audit");
	define("DB_CREDS_OUTFILE", "/../data/dbcreds.dat");
		
	$dir = dirname(__FILE__);
	$confDir = $dir . "/../../../config";
	$creds = array();
	
	require_once($confDir . "/config.php");
	
	$creds = array_merge($creds, $config["db"][ DB_DATA_KEY ]);
	
	require_once($confDir . "/dbconfig.php");
	
	$creds = array_merge($creds, $config[ DB_CREDENTIALS_KEY ]);

	try{
		$db = new PDO("mysql:host=" . $creds["host"] . ";dbname=".$creds["name"], $creds["user"], $creds["pass"],  array(PDO::ATTR_TIMEOUT, 1));
	} catch(PDOException $e) {
		$credsCVS = implode(",", $creds);
		
		/**
		 * Save creds so that we can create the database. File will be removed by `server_update.sh`
		 */
		file_put_contents( $dir . DB_CREDS_OUTFILE, $credsCVS);
		
		/**
		 * Exit status of 2 is picked up by `server_update.php` in order to determine if 
		 * connection request was successful.
		 */
		exit(2);
	}
}