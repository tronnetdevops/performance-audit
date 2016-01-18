<?php
/**
 * @brief Auditor to generate reports for Yahoo YSlow metrics.
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

/**
 * @todo Utilize PhantomJS and the YSlow.js config file to get comprehensive grades on page.
 *
 * $ phantomjs --ignore-ssl-errors=true --proxy-auth=tester:passphrasesareeasytobreak yslow.js --info grade --format tap --threshold '{"overall": "B", "ycdn": 65}' http://ontraport.local
 *
 * http://yslow.org/phantomjs/
 */
class YSlowPerformanceAuditor extends PerformanceAuditor implements iPerformanceAuditor
{
    /**
     * Class Properties
     */
}