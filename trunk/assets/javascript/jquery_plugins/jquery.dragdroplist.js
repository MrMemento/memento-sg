/**
 *   Multi-Select And Drag
 *  
 *   Not elegant solution to this problem, but the problem, despite being easily 
 *   desribed is not simple. This code is more a proof of concept, but should be
 *   extendable by anyone with the time / inclination, there I grant permission 
 *   for it to be re-used in accodance with the MIT license:
 *
 *   Copyright (c) 2009 Chris Walker (http://thechriswalker.net/)
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files (the "Software"), to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *   THE SOFTWARE.
 */
(function(jQuery){
    jQuery.fn.drag_drop_selectable = function( options, acceptable ){
        jQuery.fn.captureKeys();
        var $_this = this;
        var settings = jQuery.extend({},jQuery.fn.drag_drop_selectable.defaults,options||{});
        return jQuery(this).each(function(i){
            var $list = jQuery(this);
            var list_id = jQuery.fn.drag_drop_selectable.unique++;
            jQuery.fn.drag_drop_selectable.stack[list_id]={"selected":[ ],"all":[ ]};//we hold all as well as selected so we can invert and stuff...
            $list.attr('dds',list_id);
            jQuery.fn.drag_drop_selectable.settings[list_id] = settings;
			if (acceptable != null)
				jQuery.fn.drag_drop_selectable.acceptable[$list.attr('id')] = acceptable[$list.attr('id')];
            $list.find('li')
            //make all list elements selectable with click and ctrl+click.
            .each(function(){
                var $item = jQuery(this);
                //add item to list!
                var item_id = jQuery.fn.drag_drop_selectable.unique++;
                $item.attr('dds',item_id);
                jQuery.fn.drag_drop_selectable.stack[list_id].all.push(item_id);
                jQuery(this).bind('click.dds_select',function(e){
                    if(jQuery.fn.isPressed(CTRL_KEY) || (jQuery.fn.drag_drop_selectable.stack[jQuery.fn.drag_drop_selectable.getListId( jQuery(this).attr('dds') )].selected.length == 1 && jQuery(this).hasClass('dds_selected'))){
						//switched by memento
                        //ctrl pressed make new selection - switched by memento
                        jQuery.fn.drag_drop_selectable.replace(item_id);
                    }else{
						//switched by memento
                        //ctrl not pressed add to selection
                        jQuery.fn.drag_drop_selectable.toggle(item_id);
                    }
                }).bind('dds.select',function(){
                    jQuery(this).addClass('dds_selected').addClass( jQuery.fn.drag_drop_selectable.settings[jQuery.fn.drag_drop_selectable.getListId(jQuery(this).attr('dds'))].selectClass );
                    
                }).bind('dds.deselect',function(){
                    jQuery(this).removeClass('dds_selected').removeClass( jQuery.fn.drag_drop_selectable.settings[jQuery.fn.drag_drop_selectable.getListId(jQuery(this).attr('dds'))].selectClass );;
                }).css({cursor:'pointer'});
            })
            //OK so they are selectable. now I need to make them draggable, in such a way that they pick up their friends when dragged. hmmm how do I do that?
            .draggable({
                 helper:function(){
                    $clicked = jQuery(this);
                    if( ! $clicked.hasClass('dds_selected') ){
                        //trigger the click function.
                        $clicked.trigger('click.dds_select');
                    }
                    var list = jQuery.fn.drag_drop_selectable.getListId($clicked.attr('dds'));
                    var $helper = jQuery('<div dds_list="'+list+'"><div style="margin-top:-'+jQuery.fn.drag_drop_selectable.getMarginForDragging( $clicked )+'px;" /></div>').append( jQuery.fn.drag_drop_selectable.getSelectedForDragging( $clicked.attr('dds') ) );
                        jQuery.fn.drag_drop_selectable.getListItems( list ).filter('.dds_selected').addClass(jQuery.fn.drag_drop_selectable.settings[list].ghostClass);
                    return $helper;
                 },
                 distance:5, //give bit of leeway to allow selecting with click.
                 revert:'invalid',
                 cursor:'move',
                 stop:function(e, ui){
                    var list = jQuery.fn.drag_drop_selectable.getListId($clicked.attr('dds'));
                    jQuery.fn.drag_drop_selectable.getListItems( list ).filter('.dds_selected').removeClass(jQuery.fn.drag_drop_selectable.settings[list].ghostClass);
                 }
            });
            $list.droppable({
                drop:function(e,ui){ 
                    var oldlist = parseInt(ui.helper.attr('dds_list'));
                    ui.helper.find('li.dds_selected').each(function(){
                        var iid = parseInt( jQuery(this).attr('dds_drag') );
                        jQuery.fn.drag_drop_selectable.moveBetweenLists( iid, oldlist, list_id );
                    });
                    
                    //now call callbacks!
                    if( jQuery.fn.drag_drop_selectable.settings[oldlist] && typeof(jQuery.fn.drag_drop_selectable.settings[oldlist].onListChange) == 'function'){
                        setTimeout(function(){ jQuery.fn.drag_drop_selectable.settings[oldlist].onListChange( jQuery('ul[dds='+oldlist+']') ); },50);
                    }
                    if( jQuery.fn.drag_drop_selectable.settings[list_id] && typeof(jQuery.fn.drag_drop_selectable.settings[list_id].onListChange) == 'function'){
                        setTimeout(function(){ jQuery.fn.drag_drop_selectable.settings[list_id].onListChange( jQuery('ul[dds='+list_id+']') ); },50);
                    }
                    
                    
                },
                accept:function(d){
					//modified by memento
					drag_list_dds = jQuery.fn.drag_drop_selectable.getListId( d.attr('dds') );
					drop_list_dds = parseInt(jQuery(this).attr('dds'));
                    if(	drag_list_dds == drop_list_dds){
	 					return false;
					}
					drag_list_id = jQuery('ul[dds='+drag_list_dds+']').attr('id');
					drop_list_id = jQuery('ul[dds='+drop_list_dds+']').attr('id');
					if( jQuery.fn.drag_drop_selectable.acceptable[drop_list_id] && jQuery.inArray(drag_list_id, jQuery.fn.drag_drop_selectable.acceptable[drop_list_id]) == -1 ){
                        return false;
                    }
                    return true;
                },
                hoverClass:jQuery.fn.drag_drop_selectable.settings[list_id].hoverClass,
                tolerance:'pointer'
            });
        });  
    };
    jQuery.fn.drag_drop_selectable.moveBetweenLists=function(item_id, old_list_id, new_list_id){
        //first deselect.
        jQuery.fn.drag_drop_selectable.deselect(parseInt(item_id));
        //now remove from stack
        jQuery.fn.drag_drop_selectable.stack[old_list_id].all.splice( jQuery.inArray( parseInt(item_id),jQuery.fn.drag_drop_selectable.stack[old_list_id].all ),1);
        //now add to new stack.
        jQuery.fn.drag_drop_selectable.stack[new_list_id].all.push( parseInt(item_id) );
        //now move DOM Object.
        jQuery('ul[dds='+old_list_id+']').find('li[dds='+item_id+']').removeClass(jQuery.fn.drag_drop_selectable.settings[old_list_id].ghostClass).appendTo( jQuery('ul[dds='+new_list_id+']') );
		//added by memento, based on idea of arobas
		//tinysort plugin (http://plugins.jquery.com/project/TinySort)
		if (jQuery.tinysort)
			jQuery('ul[dds='+new_list_id+']>li').tsort({attr:'dds'});
    };
    jQuery.fn.drag_drop_selectable.getSelectedForDragging=function(item_id){
        var list = jQuery.fn.drag_drop_selectable.getListId( item_id );
        var $others = jQuery.fn.drag_drop_selectable.getListItems( list ).clone().each(function(){
            jQuery(this).not('.dds_selected').css({visibility:'hidden'});
            jQuery(this).filter('.dds_selected').addClass( jQuery.fn.drag_drop_selectable.settings[list].moveClass ).css({opacity:jQuery.fn.drag_drop_selectable.settings[list].moveOpacity});;
            jQuery(this).attr('dds_drag',jQuery(this).attr('dds'))
            jQuery(this).attr('dds','');
        });
        return $others;
    };
    jQuery.fn.drag_drop_selectable.getMarginForDragging=function($item){
        //find this items offset and the first items offset.
        var this_offset = $item.position().top;
        var first_offset = jQuery.fn.drag_drop_selectable.getListItems( jQuery.fn.drag_drop_selectable.getListId( $item.attr('dds') ) ).eq(0).position().top;
        return this_offset-first_offset;
    }
    
    jQuery.fn.drag_drop_selectable.toggle=function(id){
        if(!jQuery.fn.drag_drop_selectable.isSelected(id)){
            jQuery.fn.drag_drop_selectable.select(id);
        }else{
            jQuery.fn.drag_drop_selectable.deselect(id);
        }
    };
    jQuery.fn.drag_drop_selectable.select=function(id){
        if(!jQuery.fn.drag_drop_selectable.isSelected(id)){
            var list = jQuery.fn.drag_drop_selectable.getListId(id);
            jQuery.fn.drag_drop_selectable.stack[list].selected.push(id);
            jQuery('[dds='+id+']').trigger('dds.select');
        }
    };
    jQuery.fn.drag_drop_selectable.deselect=function(id){
        if(jQuery.fn.drag_drop_selectable.isSelected(id)){
            var list = jQuery.fn.drag_drop_selectable.getListId(id);
            jQuery.fn.drag_drop_selectable.stack[list].selected.splice(jQuery.inArray(id,jQuery.fn.drag_drop_selectable.stack[list].selected),1);
            jQuery('[dds='+id+']').trigger('dds.deselect');
        }
    };
    jQuery.fn.drag_drop_selectable.isSelected=function(id){
        return jQuery('li[dds='+id+']').hasClass('dds_selected');
    };
    jQuery.fn.drag_drop_selectable.replace=function(id){
        //find the list this is in!
        var list = jQuery.fn.drag_drop_selectable.getListId(id);
        jQuery.fn.drag_drop_selectable.selectNone(list);
        jQuery.fn.drag_drop_selectable.stack[list].selected.push(id);
        jQuery('[dds='+id+']').trigger('dds.select');
    };
    jQuery.fn.drag_drop_selectable.selectNone=function(list_id){
        jQuery.fn.drag_drop_selectable.getListItems(list_id).each(function(){
            jQuery.fn.drag_drop_selectable.deselect( jQuery(this).attr('dds') );
        });return false;
    };
    jQuery.fn.drag_drop_selectable.selectAll=function(list_id){
        jQuery.fn.drag_drop_selectable.getListItems(list_id).each(function(){
            jQuery.fn.drag_drop_selectable.select( jQuery(this).attr('dds') );
        });return false;
    };
    jQuery.fn.drag_drop_selectable.selectInvert=function(list_id){
        jQuery.fn.drag_drop_selectable.getListItems(list_id).each(function(){
            jQuery.fn.drag_drop_selectable.toggle( jQuery(this).attr('dds') );
        });return false;
    };
    jQuery.fn.drag_drop_selectable.getListItems=function(list_id){
        return jQuery('ul[dds='+list_id+'] li');
    };
    jQuery.fn.drag_drop_selectable.getListId=function(item_id){
        return parseInt(jQuery('li[dds='+item_id+']').parent('ul').eq(0).attr('dds'));
    };
    jQuery.fn.drag_drop_selectable.serializeArray=function( list_id ){
        var out = [];
        jQuery.fn.drag_drop_selectable.getListItems(list_id).each(function(){
            out.push(jQuery(this).attr('id'));
        });
        return out;
    };
    jQuery.fn.drag_drop_selectable.serialize=function( list_id ){
        return jQuery.fn.drag_drop_selectable.serializeArray( list_id ).join(", ");
    };
    
    jQuery.fn.drag_drop_selectable.unique=0;
    jQuery.fn.drag_drop_selectable.stack=[];
    jQuery.fn.drag_drop_selectable.defaults={
        moveOpacity: 0.8, //opacity of moving items
        ghostClass: 'dds_ghost', //class for "left-behind" item.
        hoverClass: 'dds_hover', //class for acceptable drop targets on hover
        moveClass: 'dds_move', //class to apply to items whilst moving them.
        selectedClass: 'dds_selected', //this default will be applied any way, but the overridden one too.
        onListChange: function(list){ /*console.log( list.attr('id') );*/ } //called once when the list changes
    }
    jQuery.fn.drag_drop_selectable.settings=[];
	//added by memento
    jQuery.fn.drag_drop_selectable.acceptable=[];
    
    jQuery.extend({
        dds:{
                selectAll:function(id){
					return jQuery.fn.drag_drop_selectable.selectAll(jQuery('#'+id).attr('dds'));
				},
                selectNone:function(id){
					return jQuery.fn.drag_drop_selectable.selectNone(jQuery('#'+id).attr('dds'));
				},
                selectInvert:function(id){
					return jQuery.fn.drag_drop_selectable.selectInvert(jQuery('#'+id).attr('dds'));
				},
                serialize:function(id){
					return jQuery.fn.drag_drop_selectable.serialize(jQuery('#'+id).attr('dds'));
				}
            }
    });
	//modified by memento
	//Stomien's browser detection (http://www.stoimen.com/blog/2009/07/16/jquery-browser-and-os-detection-plugin/)
    var CTRL_KEY = (jQuery.client && jQuery.client.os == 'Mac') ? 224 : 17;
    var ALT_KEY = 18;
    var SHIFT_KEY = 16;
    var META_KEY = 92;
    jQuery.fn.captureKeys=function(){
        if(jQuery.fn.captureKeys.capturing){ return; }
        jQuery(document).keydown(function(e){
            if(e.keyCode == CTRL_KEY){ jQuery.fn.captureKeys.stack.CTRL_KEY  = true  }
            if(e.keyCode == SHIFT_KEY){ jQuery.fn.captureKeys.stack.SHIFT_KEY = true  }
            if(e.keyCode == ALT_KEY){ jQuery.fn.captureKeys.stack.ALT_KEY   = true  }
            if(e.keyCode == META_KEY){ jQuery.fn.captureKeys.stack.META_KEY  = true  }
        }).keyup(function(e){
            if(e.keyCode == CTRL_KEY){ jQuery.fn.captureKeys.stack.CTRL_KEY  = false }
            if(e.keyCode == SHIFT_KEY){ jQuery.fn.captureKeys.stack.SHIFT_KEY = false }
            if(e.keyCode == ALT_KEY){ jQuery.fn.captureKeys.stack.ALT_KEY   = false }
            if(e.keyCode == META_KEY){ jQuery.fn.captureKeys.stack.META_KEY  = false }
        });
    };
    jQuery.fn.captureKeys.stack={ CTRL_KEY:false, SHIFT_KEY:false, ALT_KEY:false, META_KEY:false }
    jQuery.fn.captureKeys.capturing=false;
    jQuery.fn.isPressed=function(key){
        switch(key){
            case CTRL_KEY: return jQuery.fn.captureKeys.stack.CTRL_KEY;
            case ALT_KEY: return jQuery.fn.captureKeys.stack.ALT_KEY;
            case SHIFT_KEY: return jQuery.fn.captureKeys.stack.SHIFT_KEY;
            case META_KEY: return jQuery.fn.captureKeys.stack.META_KEY;
            default: return false;
        }
    }
})(jQuery);
