<form class="navbar-form pull-right" style="margin-right: 5px">
<button id="menupurgecodecache" class="btn btn-success" data-loading-text="Purging code cache..." data-toggle="button">Purge code cache</button>
<button id="menupurgecodecachedone" class="btn btn-success" disabled="disabled" style="display:none">Code cache purged</button>
</form>
<script type="text/javascript">
jQuery(document).ready(function() {
	
	jQuery("#menupurgecodecache").click(function() {
		jQuery('#menupurgecodecache').button('loading');

		var url = MoufInstanceManager.rootUrl+"src/direct/purge_code_cache.php?selfedit="+(MoufInstanceManager.selfEdit?"true":"false");
		 
		jQuery.ajax(url)
			.done(function(data) {
				if (data) {
					addMessage("An error occured while purging code cache:<br/>"+data, "alert alert-error");
				}
				jQuery("#menupurgecodecache").hide();
				jQuery("#menupurgecodecachedone").show();
				setTimeout(function() {
					jQuery("#menupurgecodecachedone").hide();
					jQuery("#menupurgecodecache").show();
					jQuery('#menupurgecodecache').button('reset');
				}, 1000);
			}).fail(function() {
				addMessage("An error occured while purging code cache", "alert alert-error");
			});
		
		return false;
	})
});
</script>