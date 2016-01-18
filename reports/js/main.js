window.oppa = {
	"fixtures": {
		"progressions": {
			"5%": 30,
			"15%": 100,
			"35%": 450,
			"40%": 700,
			"45%": 900,
			"50%": 1000,
			"68%": 1080,
			"78%": 1120,
			"99%": 1300,
			"100%": 1500
		},
		"progress": function()
		{
			for (prog in window.oppa.fixtures.progressions)
			{
				$(".progress-bar:not([data-comp-type='progress-bar'])").each(function()
				{
					oprand = Math.floor(Math.random() * 2),
						timerand = Math.floor(Math.random() * 100) * (oprand % 2 ? 1 : -1),
						time = window.oppa.fixtures.progressions[prog] + timerand;
			
					setTimeout(function($progBar, prog, time){
						$progBar.css("width", prog);
						if (prog=="100%")
						{
							setTimeout(function($progBar){
								$progBar.parent().fadeOut();
							}, 150, $progBar);
						}
					}, time, $(this), prog, time);
				});
			}
		},
		"sources": {
			"BoomerangPerformanceAuditor": {
				"name": "Yahoo's Boomerang",
				"id": "BoomerangPerformanceAuditor",
				"selected": true
			},
			"BrowserCachePerformanceAuditor": {
				"name": "Browser Cache",
				"id": "BrowserCachePerformanceAuditor"
			},
			"XHProfPerformanceAuditor": {
				"name": "Facebook's XHProf",
				"id": "XHProfPerformanceAuditor",
				"selected": true
			},
			"NetworkAuditor": {
				"name": "Network Profiler",
				"id": "NetworkAuditor",
				"selected": true
			}
		},
		"realmSourceMap": {
			"frontend": ["BoomerangPerformanceAuditor", "BrowserCachePerformanceAuditor"],
			"backend": ["XHProfPerformanceAuditor"],
			"network": ["NetworkAuditor"]
		}
	},
	
	"dashboards": {},
	"_dashboards": {
		"raw-stats": {},
		"graphs": {},
		"recommendations": {},
		"more-info": {}
	},
	
	"widgets": {},
	"_widgets": {
		
		"more-info": {},
		
		"raw-stats": {
			"stats": [],
			"init": function()
			{
				this.progressLoader(0);
				
				if (!this.initialized)
				{
					this.bindings();
				}
				
				this.stats = this.generateStats();
				
				this.progressLoader(15);
				
				return this.render();
			},
			
			"render": function()
			{
				var _this = this;
				var $lists = this.target.find("[data-comp-type='tab-content'][data-comp-widget='"+this.name+"']");
				var $tabBar = this.target.find("[data-comp-type='tab-bar'][data-comp-widget='"+this.name+"']");
				
				$lists.fadeOut().children().remove().end().each(function()
				{
					$(this).append( $("<li class=\"list-group-item oppa-stat-empty\">").text("No stats yet...") );
				});
				
				this.progressLoader(50);
				
				for(var listName in this.stats)
				{
					var $list = $lists.filter("[data-comp-name='"+listName+"']");
					this.stats[ listName ].map(function(stat)
					{
						if (stat.stat == "break")
						{
							$list.append( $("<h4>").text(stat.name).prepend($("<hr/>")) );
						}
						else
						{
							$list.append( 
								$("<li class=\"list-group-item\">")
									.text( stat.name )
									.append( $("<span class=\"badge\">").text( stat.stat ) )
									.hide().fadeIn().css("display","block")
							);
						}
					});
				}
				
				$lists.each(function()
				{
					if ($(this).children().length > 1)
					{
						$(this).find(".list-group-item.oppa-stat-empty").remove();
					}
				});
				
				this.progressLoader(100);
				
				this.target.find("[data-comp-type='tab'][data-comp-tab-bar='"+$tabBar.data("compName")+"'][data-comp-tab-content='available']")
					.trigger("click");
				
				return true;
			},
			
			"generateStats": function()
			{
				var results = {
					"available": [],
					"averages": [],
					"other": []
				};
				
				// Order matters
				var statSections = {
					"totalReportsFromAuditors": {
						"list": "available",
						"stats": [
							{
								"name": "Reports obtained from Auditors",
								"stat": "break"
							}
						]
					},
					"totalsFromBackend": {
						"list": "available",
						"stats": [
							{
								"name": "Totals returned from backend",
								"stat": "break"
							}
						]
					},
					"frontEndAverages": {
						"list": "averages",
						"stats": [
							{
								"name": "Front End Averages",
								"stat": "break"
							}
						]
					},
					"backEndAverages": {
						"list": "averages",
						"stats": [
							{
								"name": "Back End Averages",
								"stat": "break"
							}
						]
					}
				};
				
				var data = oppa.data;
				
				if (data)
				{
					if (data.realms && data.realms.frontend)
					{
						
						for(var sourceName in data.realms.frontend)
						{
							statSections.totalReportsFromAuditors.stats.push({
								"name": sourceName,
								"stat": data.realms.frontend[ sourceName ].length
							});
						}
						
						if (data.realms.frontend.BoomerangPerformanceAuditor)
						{
							var boomerangAuditorMetrics = data.realms.frontend.BoomerangPerformanceAuditor;
							var boomerangCounts = {
								"total": 0,
								"count": 0,
							};
						
							for(var i=0;i<boomerangAuditorMetrics.length;i++)
							{
								var metrics = boomerangAuditorMetrics[i];
								var metricData = JSON.parse(metrics.data);
								boomerangCounts.total += metricData.paneLoad;
								boomerangCounts.count++;
							}
				
							statSections.frontEndAverages.stats.push({
								"name": "Average wait until Paneload event is fired. [in seconds]",
								"stat": ((boomerangCounts.total / boomerangCounts.count) / 100 / 60).toFixed(3) + "s"
							});
						}
					}
					
					if (data.realms && data.realms.backend)
					{
						for(var sourceName in data.realms.backend)
						{
							statSections.totalReportsFromAuditors.stats.push({
								"name": sourceName,
								"stat": data.realms.backend[ sourceName ].length
							});
						}
						
						if (data.realms.backend.XHProfPerformanceAuditor)
						{
							var xhprofAuditorMetrics = data.realms.backend.XHProfPerformanceAuditor;
							var xhprofCounts = {
								"total": 0,
								"count": 0,
								"funcs": {}
							};
							
							for(var i=0;i<xhprofAuditorMetrics.length;i++)
							{
								var metrics = xhprofAuditorMetrics[i];
								var metricData = JSON.parse(metrics.data);
								var highestUsageFunc = {};
								for (var metricDataFuncName in metricData)
								{
									var xhprofStat = metricData[metricDataFuncName];
									if (!highestUsageFunc.wt || xhprofStat.wt > highestUsageFunc.wt)
									{
										xhprofStat["func"] = metricDataFuncName;
										highestUsageFunc = xhprofStat;
									}
								}
								xhprofCounts.total += highestUsageFunc.wt;
								xhprofCounts.count++;
							}
							
							statSections.backEndAverages.stats.push({
								"name": "Average wait until Paneload event is fired. [in seconds]",
								"stat": ((xhprofCounts.total / xhprofCounts.count) / 100 / 60).toFixed(3) + "s"
							});
						}
					}
					
					if (data.realms && data.realms.network)
					{
						
					}
					
					if (data.totals)
					{
						for(var totalName in data.totals)
						{
							statSections.totalsFromBackend.stats.push({
								"name": totalName,
								"stat": data.totals[ totalName ]
							});
						}
					}
				}
				
				for(var statSectionName in statSections)
				{
					var statSection = statSections[ statSectionName ];
					$.merge(results[ statSection.list ], statSection.stats);
				}
				
				return results;
			},
			
			"bindings": function()
			{
				var _this = this;
				
				this.target.find("[data-comp-type='tab-bar']").delegate("li", "click", function(){
					var tabContentName = $(this).data("compTabContent");
					
					$(this).addClass("active").siblings().removeClass("active");
					
					_this.target.find("[data-comp-type='tab-content'][data-comp-widget='"+_this.name+"']")
						.hide().filter("[data-comp-name='"+tabContentName+"']").fadeIn();
				});
			}
		},
		
		
		"graph-realms": {
			"init": function()
			{
				
				return this.render();
			},
			
			"render": function()
			{
				var $graph = this.target.find("[data-comp-type='graph']").filter("[data-comp-widget='"+ this.name +"']");
				var $container = $graph.parent();
				var width = $container.parent().innerWidth() - parseInt($container.find(".panel-body").css("padding-left"));
				
				$graph.attr("width",  width);
				
				this.context = $graph.get(0).getContext("2d");
				this.chart = new Chart(this.context).Radar({
					labels : ["Eating","Drinking","Sleeping","Designing","Coding","Partying","Running"],
					datasets : [
						{
							fillColor : "rgba(220,220,220,0.5)",
							strokeColor : "rgba(220,220,220,1)",
							pointColor : "rgba(220,220,220,1)",
							pointStrokeColor : "#fff",
							data : [65,59,90,81,56,55,40]
						},
						{
							fillColor : "rgba(151,187,205,0.5)",
							strokeColor : "rgba(151,187,205,1)",
							pointColor : "rgba(151,187,205,1)",
							pointStrokeColor : "#fff",
							data : [28,48,40,19,96,27,100]
						}
					]
				});
				
				return true;
			}
		},
		
		
		"graph-yslow": {
			"init": function()
			{
				
				return this.render();
			},
			
			"render": function()
			{
				var $graph = this.target.find("[data-comp-type='graph']").filter("[data-comp-widget='"+ this.name +"']");
				var $container = $graph.parent();
				var width = $container.parent().innerWidth() - parseInt($container.find(".panel-body").css("padding-left"));
				
				$graph.attr("width",  width);
				
				this.context = $graph.get(0).getContext("2d");
				this.chart = new Chart(this.context).Line({
					labels : ["January","February","March","April","May","June","July"],
					datasets : [
						{
							fillColor : "rgba(220,220,220,0.5)",
							strokeColor : "rgba(220,220,220,1)",
							pointColor : "rgba(220,220,220,1)",
							pointStrokeColor : "#fff",
							data : [65,59,90,81,56,55,40]
						},
						{
							fillColor : "rgba(151,187,205,0.5)",
							strokeColor : "rgba(151,187,205,1)",
							pointColor : "rgba(151,187,205,1)",
							pointStrokeColor : "#fff",
							data : [28,48,40,19,96,27,100]
						}
					]
				});
				
				return true;
			}
		},
		
		
		"graph-browsercache": {
			"init": function()
			{
				
				return this.render();
			},
			
			"render": function()
			{
				var $graph = this.target.find("[data-comp-type='graph']").filter("[data-comp-widget='"+ this.name +"']");
				var $container = $graph.parent();
				var width = $container.parent().innerWidth() - parseInt($container.find(".panel-body").css("padding-left"));
				
				$graph.attr("width",  width);
				
				this.context = $graph.get(0).getContext("2d");
				this.chart = new Chart(this.context).PolarArea([
					{
						value : 30,
						color: "#D97041"
					},
					{
						value : 90,
						color: "#C7604C"
					},
					{
						value : 24,
						color: "#21323D"
					},
					{
						value : 58,
						color: "#9D9B7F"
					},
					{
						value : 82,
						color: "#7D4F6D"
					},
					{
						value : 8,
						color: "#584A5E"
					}
				]);
				
				return true;
			}
		}
	},
	
	"components": {
		
		"Dashboard": (function()
		{
			var Dashboard = function(config)
			{
				this.widgets = {};
				
				for(var propName in config)
				{
					this[propName] = config[propName];
				}
				console.log("Creating dashboard \""+ this.name +"\"!");
				
				this.init && this.init();
				
				this.initialized = true;
			}

			Dashboard.prototype.progressLoader = function(percentage)
			{
				var _this = this;
				if (!this.progressBar)
				{
					this.progressBar = this.target.find("[data-comp-type='progress-bar']").filter("[data-comp-dashboard='"+ this.name +"']");
				}
				
				if (+percentage)
				{
					percentage = percentage+"%";
				}
				
				if (percentage == "100%")
				{
					this.progressBarDone = true;
					setTimeout(function(){
						_this.progressBar.fadeOut();
					}, 250);
				}
				else if (this.progressBarDone)
				{
					this.progressBarDone = false;
					this.progressBar.children().first().css("width", "%1").hide();
					this.progressBar.fadeIn();
				}
				
				this.progressBar.children().first().show().css("width", percentage);
			};

			return Dashboard;
		})(),
		"Widget": (function()
		{
			var Widget = function(config)
			{
				for(var propName in config)
				{
					this[propName] = config[propName];
				}
				console.log("Creating widget \""+ this.name +"\"!");
				
				this.init && this.init();
				
				this.initialized = true;
			}

			Widget.prototype.progressLoader = function(percentage)
			{
				var _this = this;
				if (!this.progressBar)
				{
					this.progressBar = this.target.find("[data-comp-type='progress-bar']").filter("[data-comp-widget='"+ this.name +"']");
				}
				
				if (+percentage)
				{
					percentage = percentage+"%";
				}
				
				if (percentage == "100%")
				{
					this.progressBarDone = true;
					setTimeout(function(){
						_this.progressBar.fadeOut();
					}, 250);
				}
				else if (this.progressBarDone)
				{
					this.progressBarDone = false;
					this.progressBar.children().first().css("width", "%1").hide();
					this.progressBar.fadeIn();
				}
				
				this.progressBar.children().first().show().css("width", percentage);
			};

			return Widget;
		})()
	},
	"init": function(data)
	{
		var _this = window.oppa;
		var $compElements = $("[data-comp-name]");
		
		this.data = data;
		
		this.fixtures.progress();
		
		$compElements.filter("[data-comp-type='dashboard']").each(function()
		{ 
			var data = $(this).data();
			
			if (_this.dashboards[ data["compName"] ])
			{
				_this.dashboards[ data["compName"] ].init && _this.dashboards[ data["compName"] ].init();
			}
			else
			{
				var definition = _this._dashboards[ data["compName"] ];
			
				definition["name"] = data["compName"];
				definition["target"] = $(this)
				definition["data"] = data
				
				_this.dashboards[ data["compName"] ] = new _this.components.Dashboard(definition);
			}
		});
		
		$compElements.filter("[data-comp-type='widget']").each(function()
		{ 
			var data = $(this).data();
			
			if (_this.widgets[ data["compName"] ])
			{
				_this.widgets[ data["compName"] ].init && _this.widgets[ data["compName"] ].init();
			}
			else
			{
				var definition = _this._widgets[ data["compName"] ];
				
				definition["dashboard"] = data["compDashboard"];
				definition["name"] = data["compName"];
				definition["target"] = $(this)
				definition["data"] = data
			
				_this.dashboards[ data["compDashboard"] ].widgets[ data["compName"] ] = _this.widgets[ data["compName"] ] = new _this.components.Widget(definition);
			}
		});
	}
};


$(function()
{
	$(".oppa-results-set-container").children().fadeOut();
	
	$("body").delegate("#oppa-search-submit", "click", function(){
		var from = $("#oppa-search-from").val() || "2 weeks ago",
			to = $("#oppa-search-to").val() || "yesterday";
		
			/**
			 * @todo Change common time terms
			 *     "(in the|at) morning"=>"9:00:00"
			 *     "(in the|at) afternoon"=>"12:00:00"
			 *     "(in the|at) evening"=>"17:00:00"
			 *     "at dusk"=>"19:00:00"
			 */
		
		$(this).find(".glyphicon").removeClass("glyphicon-chevron-right").addClass("glyphicon-chevron-down")
		
		$.ajax({
			"url": "../api/", 
			"data": { 
				"source": $("#oppa-search-source").val(),
				"realm": $("#oppa-search-realm").val(),
				"from": from,
				"to": to
			}
		}).success(function(resp)
		{
			if (resp)
			{
				var data = JSON.parse(resp);
				
				$(".oppa-results-set-container").removeClass("hide").children().fadeIn();
				
				$("body").animate({
					"scrollTop": $(".jumbotron").outerHeight() - 50 
				});
				
				window.oppa.init(data.data); 
			}
		})
	});
	
	$("body").delegate("#oppa-search-realm", "change", function()
	{
		var realms = $(this).val() || [];
		var sources = [];
		var $sourceSelect = $("#oppa-search-source");
		
		$sourceSelect.find("option").remove();
		
		realms.map(function(realm){
			$.merge(sources, window.oppa.fixtures.realmSourceMap[ realm ] );
		});
		
		sources.map(function(source)
		{
			var data = window.oppa.fixtures.sources[source];
			$sourceSelect.append(
				$("<option>").attr({
					"value": source,
					"selected": data.selected
				}).text(data.name)
			)
		});
	});
	
	$("#oppa-search-realm").val("frontend").trigger("change");
	
});