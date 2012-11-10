
function addMessage(msg, cssClass) {
	if (jQuery("#messages").size() == 0) {
		jQuery("<div/>").attr("id", "messages").prependTo(jQuery('#content'));
	}
	
	jQuery("#messages").append("<div class='"+cssClass+"'>"+msg+"</div>");
}