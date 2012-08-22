// jQuery UI on Google Maps
// Copyright (c) 2008 Marc Grabanski
// Licensed under the MIT license.
jQuery(document).ready(function(){
	jQuery('div.gmap').each(function(key, value) {
		var mapID = jQuery(this).attr('id');
		var currCneter = new GLatLng(
			parseFloat(jQuery(this).find('#map-lat').html()),
			parseFloat(jQuery(this).find('#map-lang').html())
		);
		var map = new GMap2(document.getElementById(mapID));
		map.addMapType(G_PHYSICAL_MAP); 
		map.setCenter(currCneter, 16);
		var markers = [];
		for (var i = 0; i < 1; i++) {
			marker = new GMarker(currCneter);
			map.addOverlay(marker);
			markers[i] = marker;
		}

		jQuery(markers).each(function(i,marker){
			jQuery("<li />").addClass('ui-state-default ui-corner-all')
				.uiHover()
				.html("Point "+i)
				.click(function(){
					displayPoint(marker, i);
				})
				.appendTo("#gmap_list");

			GEvent.addListener(marker, "click", function(){
				displayPoint(this, i);
			});
		});

		jQuery('<div id="message" />').appendTo(map.getPane(G_MAP_FLOAT_SHADOW_PANE));

		function displayPoint(marker, index){
			jQuery("#message").hide().empty();

			var closeButton = jQuery(iconHTML("close"))
				.click(
					function(){ jQuery("#message").fadeOut(); 
				})
				.css({ top:'5px', right:'5px' })
				.uiHover();

			jQuery("#accordion-template").clone().show()
			//jQuery("#tabs-template").clone().show()
				.attr("id","").appendTo("#message")
				.find(".index").html(index.toString()).end()
				.find(".dialog").click(function(){ jQuery("#dialog").dialog("open"); }).end()
				.tabs()
				.find(".ui-tabs-nav").append(closeButton);

			var moveEnd = GEvent.addListener(map, "moveend", function(){
				var markerOffset = map.fromLatLngToDivPixel(marker.getPoint());
				jQuery("#message")
					.css({ top:markerOffset.y, left:markerOffset.x })
					.show("drop", { direction:"right" });

				GEvent.removeListener(moveEnd);
			});

			left = map.getBounds().getSouthWest().lat();
			right = map.getBounds().getNorthEast().lat();
			offset = 0;//(right - left) * .25;
			map.panTo(new GLatLng(marker.getPoint().lat(), marker.getPoint().lng()+offset));
		}

		GEvent.addListener(map, 'zoomend', function(){
			jQuery("#message").hide();
		});

		jQuery("#dialog").show().dialog({
			autoOpen:false,
			modal:true,
			overlay:{ background:"#000", opacity:0.7 },
			width:350, height:300
		});

		jQuery("#gmap_list").appendTo("#"+mapID).css({ top:'10px', right:'10px' });

		/* Build Controls */
		jQuery(iconHTML("up"))
			.css({ top:'10px', left:'32px' })
			.click(function(){
				map.panDirection(0, 1);
			})
			.appendTo("#"+mapID);

		jQuery(iconHTML("left"))
			.css({ top:'32px', left:'10px' })
			.click(function(){
				map.panDirection(1, 0);
			})
			.appendTo("#"+mapID);

		jQuery(iconHTML("right"))
			.css({ top:'32px', left:'54px' })
			.click(function(){
				map.panDirection(-1, 0);
			})
			.appendTo("#"+mapID);

		jQuery(iconHTML("down"))
			.css({ top:'54px', left:'32px' })
			.click(function(){
				map.panDirection(0, -1);
			})
			.appendTo("#"+mapID);

		jQuery(iconHTML("plus"))
			.css({ top:'84px', left:'32px' })
			.click(function(){
				map.zoomIn();
				jQuery("#"+mapID+" #map-slider").slider("value", map.getZoom());
			})
			.appendTo("#"+mapID);

		jQuery(iconHTML("minus"))
			.css({ top:'245px', left:'32px' })
			.click(function(){
				map.zoomOut();
				jQuery("#"+mapID+" #map-slider").slider("value", map.getZoom());
			})
			.appendTo("#"+mapID);

		jQuery("<div />").attr('id','map-slider').height(120)
			.slider({ 
				orientation: "vertical", 
				min:0, max:19, step:1, value:map.getZoom(),
				change:function(){
					map.setZoom( jQuery(this).slider("value") );
				}
			})
			.css({ top:'115px', left:'38px', position:'absolute' })
			.appendTo("#"+mapID);
	});
});

function iconHTML(type) {
	switch (type) {
		case "up" 		: iconClass = 'ui-icon-circle-arrow-n'; break;
		case "down" 	: iconClass = 'ui-icon-circle-arrow-s'; break;
		case "left" 	: iconClass = 'ui-icon-circle-arrow-w'; break;
		case "right" 	: iconClass = 'ui-icon-circle-arrow-e'; break;
		case "plus" 	: iconClass = 'ui-icon-circle-plus'; break;
		case "minus" 	: iconClass = 'ui-icon-circle-minus'; break;
		case "close"	: iconClass = 'ui-icon-closethick'; break;
	}
	return '<div class="icon ui-state-default ui-corner-all"><span class="ui-icon '+iconClass+'" /></div>';
}

jQuery.fn.uiHover = function(){
	return this.each(function(){
		jQuery(this).hover(
			function(){ jQuery(this).addClass('ui-state-hover'); },
			function(){ jQuery(this).removeClass('ui-state-hover'); }
		);
	});
}