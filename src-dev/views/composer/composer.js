var Composer = {
	"cleanPackageList": function() {
		$("#composerpackagessearch").empty();
		$("#composeroutput").empty();
	},
	/**
	 * Displays the package (passed as a JSON object).
	 */
	"displayPackage": function(packageObj, score) {
		var version = packageObj.version;
		var displayVersion = version.replace(/\.9999999/g, "");
		if (displayVersion == "9999999-dev") {
			displayVersion = "dev-master";
		}
		
		var packageDiv = $("<div/>").addClass("package");
		var iconDiv = $("<div/>").addClass("packageicon").appendTo(packageDiv);
		if (packageObj.logo) {
			$("<img/>").attr("src", packageObj.logo).appendTo(iconDiv);
		} 
		var packageTextDiv = $("<div/>").addClass("packagetext").appendTo(packageDiv);
		
		$("<span/>").addClass("packagename").html(Composer.enhanceString(packageObj.prettyName)).appendTo(packageTextDiv);
		$("<span/>").addClass("packageversion").text(' (version '+displayVersion+') ').appendTo(packageTextDiv);
		$("<a/>").attr("href", "javascript: void(0)").addClass("viewotherversions").text('(show more versions)').appendTo(packageTextDiv).hide();
		var linkToHomePage = $("<div/>").addClass("packagehompage").html('Homepage: ').appendTo(packageTextDiv);
		$("<a/>").attr("href", packageObj.homePage).attr("target", "_blanck").html(Composer.enhanceString(packageObj.homePage)).appendTo(linkToHomePage);
		$("<div/>").addClass("packagedescription").html(Composer.enhanceString(packageObj.description)).appendTo(packageTextDiv);
		$("<button/>").addClass("install").text("Install").appendTo(packageTextDiv).click(function() {
			Composer.install(packageObj);
		});
		
		packageDiv.data('packagename', packageObj.name);
		packageDiv.data('version', packageObj.version);
		packageDiv.data('package', packageObj);
		
		// Note about the HTML structure:
		// #composerpackagessearch div.data(name, name) div.data(version, version)
		
		// Let's start by trying to find the packagename in the top divs.
		var packageMainDiv = null;
		var mainPackages = $("#composerpackagessearch > div");
		mainPackages.each(function() {
			var $package = $(this);
			if ($package.data("packagename") == packageObj.name) {
				packageMainDiv = $package;
			}
		})
		if (packageMainDiv == null) {
			packageMainDiv = $("<div/>").data("packagename", packageObj.name).data('score', score);
			// TODO: at least update the score when a new package arrives if a new score is greater
			
			// Ok, let's find where we should insert this package depending on the score (of the first package passed,
			// which is not the most rigorous way of doing things...)
			var insertBeforeMainPackage = null;
			mainPackages.each(function() {
				// We return true if the compareTo version is SMALLER than the current version
				var compareTo = $(this);
				var compareToScore = compareTo.data('score');
				if (score < compareToScore) {
					return true;
				} else {
					insertBeforeMainPackage = compareTo;
					return false;
				}
			});
			if (insertBeforeMainPackage != null) {
				packageMainDiv.insertBefore(insertBeforeMainPackage);
			} else {
				packageMainDiv.appendTo($("#composerpackagessearch"));
			}
		}
		var packageVersions = packageMainDiv.find("div.package");
		if (packageVersions.size() == 0) {
			packageDiv.appendTo(packageMainDiv);
		} else {
			// Let's try to insert the version at the right place.
			var insertBeforePackage = null;
			packageVersions.each(function() {
				// We return true if the compareTo version is SMALLER than the current version
				var compareTo = $(this);
				var compareToVersion = compareTo.data('version');
				
				if (compareToVersion.indexOf("dev") != -1 && packageObj.version.indexOf("dev") == -1) {
					insertBeforePackage = compareTo;
					return false;
				} else if (compareToVersion.indexOf("dev") == -1 && packageObj.version.indexOf("dev") != -1) {
					return true;
				}

				var compareToVersionArray = compareToVersion.split(".");
				var versionArray = packageObj.version.split(".");
				for (var i=0; i<Math.min(versionArray.length, compareToVersionArray.length); i++) {
					if (versionArray[i] == compareToVersionArray[i]) {
						continue;
					} else if (versionArray[i] < compareToVersionArray[i]) {
						return true;
					} else if (versionArray[i] > compareToVersionArray[i]) {
						insertBeforePackage = compareTo;
						return false;
					}
				}
				if (versionArray.length > compareToVersionArray.length) {
					insertBeforePackage = compareTo;
					return false;
				}
				return true;
			});
			
			if (insertBeforePackage != null) {
				packageDiv.insertBefore(insertBeforePackage);
			} else {
				packageDiv.appendTo(packageMainDiv);
			}
		}
		
		
		
		//packageDiv.appendTo($("#composerpackagessearch"));
		//$("#composerpackagessearch").append(html);
	},
	"consoleOutput": function(html) {
		$("#composeroutput").append(html);
		$("#composeroutput").animate({ scrollTop: $("#composeroutput").prop("scrollHeight") }, "slow");
	},
	/**
	 * Sets some visual feedback about the search status
	 */
	"setSearchStatus": function(status) {
		if (status) {
			$(".composersearch .searchbig").addClass("searching").attr('disabled', true);
			$(".composersearch button").attr('disabled', true);
			$("#composeroutput").slideDown();
		} else {
			// Let's compute what "see more versions" buttons should be displayed.
			var mainPackages = $("#composerpackagessearch > div");
			mainPackages.each(function() {
				var $package = $(this);
				var $versions = $package.find("div.package");
				if ($versions.size() > 1) {
					$versions.first().find(".viewotherversions").show();
					$versions.first().find(".viewotherversions").click(function() {
						$(this).closest("div.package").parent().find("div.package").show();
					})
				}
			})
			
			$(".composersearch .searchbig").removeClass("searching").attr('disabled', false);
			$(".composersearch button").attr('disabled', false);
			$("#composeroutput").slideUp();
		}
	},
	/**
	 * Starts a new search for packages
	 */
	"search": function(text) {
		$("#tmploading").attr("src", MoufInstanceManager.rootUrl+"composer/search?text="+text);
	},
	/**
	 * Put the tokens detected in the search string in "strong".
	 */
	"enhanceString": function(str) {
		var text = $("<div/>").html(str).text();
		var tokens = $(".composersearch .searchbig").val().split(" ");
		for (var i=0; i<tokens.length; i++) {
			var pattern = new RegExp("("+tokens[i]+")", "ig");

			text = text.replace(pattern, "<strong>$1</strong>");
		}
		return text;
	},
	"install": function(packageObj) {
		var popup = $("#packageinstall");
		popup.find(".packagename").text(packageObj.prettyName);
		popup.find("#packagenamehidden").val(packageObj.name);
		
		$("#packageversiondropdown").empty();
		$("#packageversionmanual").val("");
		$("#packagefromsource").attr("checked", false);
		
		var version = packageObj.version;
		var versionParts = version.split(".");
		
		var proposedVersions = [];
		for (var i=0; i<versionParts.length; i++) {
			var currentVersionParts = [];
			for (var j=0; j<i; j++) {
				currentVersionParts.push(versionParts[j]);
			}
			currentVersionParts.push("*");
			proposedVersions.push(currentVersionParts.join("."));
		}
		proposedVersions.push(version);
		
		for (var i=0; i<proposedVersions.length; i++) {
			var option = $("<option/>").val(proposedVersions[i]).text(proposedVersions[i]);
			// Let's select all the versions except the minor one.
			if (i == proposedVersions.length-2) {
				option.attr("selected", true);
				$("#packageversionhidden").val(proposedVersions[i]);
			}
			$("#packageversiondropdown").append(option);
		}
		var option = $("<option/>").val("manual").text("Enter your own version requirements");
		$("#packageversiondropdown").append(option);
		
		popup.dialog('open');
	}
}

$(document).ready(function() {
	var popup = $("#packageinstall");
	popup.dialog({ 
		autoOpen: false,
		width: 500,
		modal: true
	});
	
	$("#packageversiondropdown").change(function() {
		if ($(this).val() == "manual") {
			$("#manualselectdiv").show();
		} else {
			$("#manualselectdiv").hide();
			$("#packageversionhidden").val($(this).val());
		}
	});
	
	$("#packageversionmanual").change(function() {
		$("#packageversionhidden").val($(this).val());
	});
	
})