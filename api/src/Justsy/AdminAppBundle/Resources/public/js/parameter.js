var parameter = {
	toggle:function(ev){
	  var id = $(ev).attr("parameter_type");
	  $(".mb_menu_area .mb_menu_active").attr("class","mb_menu");
	  $(ev).attr("class","mb_menu_active");
	  $(".parameter_content .parameter_panel").hide();
	  $(".parameter_content #"+id).show();
	}
};