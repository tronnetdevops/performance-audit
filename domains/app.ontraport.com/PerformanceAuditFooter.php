<?php
if (PERFORMANCE_AUDIT_MODE)
{
	/**
	 * Currently, none of the backend Auditors require actual interaction with the codebase, so init
	 * can be called at the very end.
	 *
	 * In order maintain future encapsulation, see if we can introduce hooks into the code base that 
	 * Auditors can utilize.
	 */
	$PerfAuditManager->init();
	
	$PerfAuditManager->stop();
	$PerfAuditManager->finish();
	
	if (PAGELOADING)
	{
		$DOM = ob_get_contents();
		
		ob_end_clean();
		
		$out = $PerfAuditManager->BufferDOMLoader( $DOM );
		
		echo $out;
	}
}
