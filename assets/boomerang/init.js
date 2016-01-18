/**
 * @brief Initializes boomerang.js audit.
 *
 * ## Overview
 * This initializes the boomerang plugin with basic configurations. Once initialized, timers
 * can be set via the `BOOMR.plugins.RT` plugin, and events fired from boomerang can be subscribed
 * to via the `BOOMR.subscribe` method.
 * 
 * This function is required due to the nature of how the ontraport architecture is defined and 
 * derived through steal and jsmvc. For this reason, this acts as a primer for the boomerang 
 * component that is invoked as close to the entry point as possible so as to get undiluted 
 * metrics.
 *
 * @see initial.html
 *
 * @author <smurray@ontraport.com>
 * @date 02/17/2014
 */
(function(BOOMR)
{
	/**
	 * AJAX Tracking
	 */
	
	/**@* {Integer} Used to count amount of ajax requests */
	BOOMR.ajaxRequests = 0;

	/**
	 * Override the `XMLHttpRequest.open` method so that we can acurately detect
	 * the first AJAX request. 
	 */
	this.XMLHttpRequest.prototype._open = this.XMLHttpRequest.prototype.open;
	this.XMLHttpRequest.prototype.open = function()
	{
		if (!BOOMR.ajaxRequests++)
		{
			BOOMR.plugins.RT.startTimer("open_ajax_req");
			BOOMR.plugins.RT.startTimer("first_ajax_req");
		}
		
		return this._open.apply(this, arguments);
	}
	
	/**
	 * Initialize boomerang plugin.
	 */
	BOOMR.init({
		"autorun": false,
		/**
		 * @todo This can't be obtained accuretly due to load balancers, reapply when available.
		 */
		// "user_ip": "10.0.0.1", 
		"BW": {
			"base_url": "/?__oppa=BoomerangPerformanceAuditor&img="
		}
	});
	
	/**
	 * Start polling to determine when all AJAX requests have finished returning.
	 */
	(function(){
		var activeAjaxLister = this;

		activeAjaxLister.count = 0;
		activeAjaxLister.threshold = 5; // == 5 * 100ms = 500mm before calling quits
		activeAjaxLister.listener = window.setInterval(function(){
			
			/**
			 * Don't start checks until atleast the boomerang component has been loaded.
			 */
			if (this.auditReady || "jQuery" in this)
			{
				this.auditReady = true;
				
				jQuery(window).bind("paneLoad", function()
				{
					window.BOOMR.t_end = (new Date()).getTime()
					window.BOOMR.plugins.RT.endTimer("first_ajax_req");
					window.BOOMR.page_ready();
				});
			}
			else
			{
				return false;
			}
			
			if (!$.active)
			{
				if (!this.initialized)
				{
					this.initialized = 1;
				}
				activeAjaxLister.count++;
			}
			else
			{
				activeAjaxLister.count = 0;
			}

			if (this.initialized && activeAjaxLister.count > activeAjaxLister.threshold)
			{
				BOOMR.plugins.RT.endTimer("open_ajax_req");
				window.clearInterval(activeAjaxLister.listener);
			}
		}, 100);
	})();
	
	BOOMR.parseRequest = function(data)
	{
		var subdata = {};
		data.t_other.split(",").map(function(val)
		{
			var splt=val.split("|");
			subdata[splt[1]] = splt[0];
		});
	
		return {
			"staticPageLoad": data.t_done,
			"paneLoad": data.t_page,
			"cookiesSize": document.cookie && document.cookie.length || -1,
			"ajaxBlock": subdata.open_ajax_req,
			"fromFirstXHR": subdata.first_ajax_req,
			"cacheEmpty": null,
			"ajaxCalls": BOOMR.ajaxRequests,
			"_data": data
		};
	}
	
	BOOMR.counter = 0;
	
	BOOMR.subscribe("before_beacon", function(data)
	{
		var parsed = BOOMR.parseRequest(data);
		$.ajax({
			"url": "/index.php",
			"type": "POST",
			"data": {
				"__oppa": "BoomerangPerformanceAuditor",
				"data": JSON.stringify(parsed),
				"name": "boomerang ::=> " +(new Date()),
				"start": window._sf_startpt,
				"end": window._sf_endpt,
				"counter": BOOMR.counter++
			}
		}).done(function(data)
		{
			console.log("==========>>>>>>>>> Posted data!", data);
		});
	});

}).call(window, window.BOOMR);