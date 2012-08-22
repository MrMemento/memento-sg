jQuery(document).ready(function(){
	mychange = function ( $list ){
		jQuery( '#code_deps' ).val( jQuery.dds.serialize('dep_codes') );
		jQuery( '#style_deps' ).val( jQuery.dds.serialize('dep_styles') );
		jQuery( '#script_deps' ).val( jQuery.dds.serialize('dep_scripts') );
	}
	//modified by memento
	//second argument is an array of lists from where drop list accepts drops
	//if not defined, accepts from every other list
	jQuery('div.dragdrop_panel>div>ul').drag_drop_selectable({
		onListChange:mychange
	},{
		avail_codes:['dep_codes'],
		dep_codes:['avail_codes'],
		avail_styles:['dep_styles'],
		dep_styles:['avail_styles'],
		avail_scripts:['dep_scripts'],
		dep_scripts:['avail_scripts']
	});
	if (jQuery('dep_codes').length > 0){
		jQuery( '#code_deps' ).val( jQuery.dds.serialize('dep_codes') );
	}
	if (jQuery('dep_styles').length > 0){
		jQuery( '#style_deps' ).val( jQuery.dds.serialize('dep_styles') );
	}
	if (jQuery('dep_scripts').length > 0){
		jQuery( '#script_deps' ).val( jQuery.dds.serialize('dep_scripts') );
	}
});