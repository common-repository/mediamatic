
(function( $ ) {
    "use strict";
	
	
  
    $(document).ready(function(){
        var wp 				= window.wp;
		
		localStorage.setItem('mediamaticModalActiveFolder', '');
		
		if (wp.media) {
			wp.media.view.Modal.prototype.on('open', function() {
				mediamatic_cc_recallMe();
				mediamatic_cc_ifModalOpen();
			});
		}
      
        if (typeof wp !== 'undefined' && typeof wp.Uploader === 'function') {
            $.extend( wp.Uploader.prototype, {
                progress: function() {},
                init : function() {
                  
                    if (this.uploader) {
                      
                        
                        this.uploader.bind('FileFiltered', function( up, file ) {
                           
                        });
                       
                        this.uploader.bind('BeforeUpload', function(uploader, file) {
							var folderID,params;
							
							if(localStorage.getItem('mediamaticModalActiveFolder') !== ''){
								folderID 	= Number(localStorage.getItem('mediamaticModalActiveFolder'));
							}else{
								folderID 	= Number($( ".wpmediacategory-filter" ).val());
							}
							params 			= uploader.settings.multipart_params;
							
							params.ccFolder = folderID;
                        });
						
                        this.uploader.bind('UploadProgress', function(up, file) {
							$('.uploader-window').hide().css('opacity', 0);
							mediamatic_eug_start_preloader(); 
                        });


						//run after FilesAdded
                        this.uploader.bind('UploadComplete', function(up, files) {
							var currentFolderID;
							if(localStorage.getItem('mediamaticModalActiveFolder') !== ''){
								currentFolderID = localStorage.getItem('mediamaticModalActiveFolder');
								mediamatic_eug_set_active_folder(currentFolderID);
								mediamatic_cc_ifModalOpen(); // all will be broken, if up this code to one line
							}else{
								currentFolderID = $(".wpmediacategory-filter").val();
								mediamatic_eug_set_active_folder(currentFolderID);
							}
                        });

                        this.uploader.bind('FilesAdded', function( up, files ) {
                            var currentFolderID;
							
							
							if(localStorage.getItem('mediamaticModalActiveFolder') !== ''){
								currentFolderID	= localStorage.getItem('mediamaticModalActiveFolder');
							}else if($('.media-frame-content').attr('aria-labelledby') === 'menu-item-browse'){
								currentFolderID	= $(".wpmediacategory-filter").val();
							}else{
								currentFolderID	= $(".wpmediacategory-filter").val();
							}
							
							
                            files.forEach(function(file){
                                if(currentFolderID === 'all'){
                                    mediamatic_eug_update_count(null, -1);
                                }else if(Number(currentFolderID) === -1){
                                    mediamatic_eug_update_count(null, -1);
                                }else{
                                    mediamatic_eug_update_count(null, currentFolderID);
                                }
                            });
                            
                        });

                    }

                }
            });
        }

        
    });
	
	function mediamatic_eug_start_preloader(){
		$('.mediamatic_be_loader').addClass('active');
	}
	function mediamatic_eug_stop_preloader(){
		$('.mediamatic_be_loader').removeClass('active');
	}
	
	
	function mediamatic_eug_increase_count(folderID){
		var folderCount 	= $('ul li.category_item[data-id="' + folderID + '"] .cc_count').text();
		folderCount			= parseInt(folderCount) - 1;
		$('ul li.category_item[data-id="' + folderID + '"] .cc_count').text(folderCount);
		var totalCount	 	= $('ul li.category_item[data-id="all"] .cc_count').text();
		totalCount			= parseInt(totalCount) - 1;
		$('ul li.category_item[data-id="all"] .cc_count').text(totalCount);
	}
	
	function mediamatic_eug_update_count(from,to){
		
		from 	= parseInt(from);
		to 		= parseInt(to);
		
		if(from !== to){
			if(from){
				var countTermFrom 	= $('ul li.category_item[data-id="' + from + '"] .cc_count').text();

				countTermFrom 		= parseInt(countTermFrom) - 1;
				if(countTermFrom){
					$('ul li.category_item[data-id="' + from + '"] .cc_count').text(countTermFrom);
				}else{
					$('ul li.category_item[data-id="' + from + '"] .cc_count').text(0);
				}
			}else{
				var all				= $('ul li.category_item[data-id="all"]');
				var count			= all.find('.cc_count').text();
				count				= parseInt(count) + 1;
				all.find('.cc_count').text(count);
			}
			if(to){
				var countTermTo 	= $('ul li.category_item[data-id="' + to + '"] .cc_count').text();
				countTermTo 		= parseInt(countTermTo) +1;
				$('ul li.category_item[data-id="' + to + '"] .cc_count').text(countTermTo);
			}
		}
		
	}
	
	function mediamatic_eug_set_active_folder(currentFolderID){
		mediamatic_eug_start_preloader();
		
		
		$('.wpmediacategory-filter').val(currentFolderID);
		$('.wpmediacategory-filter').trigger('change');
		
		
		var sidebar 	= $('.cc_mediamatic_sidebar');
		var backbone 	= mediamatic_eug_getBackboneOfMedia(sidebar);
		if (backbone.browser.length > 0 && typeof backbone.view == "object") {
			try{
				backbone.view.collection.props.set({ ignore: (+ new Date()) });
			}catch(e){
				console.log(e);
			}
		}else{
			sidebar 	= $('.media-modal-content');
			backbone 	= mediamatic_eug_getBackboneOfMedia(sidebar);
			if (backbone.browser.length > 0 && typeof backbone.view == "object") {
				try{
					backbone.view.collection.props.set({ ignore: (+ new Date()) });
				}catch(e){
					console.log(e);
				}
			}
		}
		

		$('.attachments').css('height', 'auto');
		
		// stop preloader
		mediamatic_eug_stop_preloader();

	}
	
	function mediamatic_eug_getBackboneOfMedia(obj) {
		
		var browser,
			backboneView,
			parentModal = obj.parents(".media-modal");
		if (parentModal.length > 0){
			browser 	= parentModal.find(".attachments-browser");
		}else{
			browser 	= $("#wpbody-content .attachments-browser");
		}
		backboneView 	= browser.data("backboneView");
		return { browser: browser, view: backboneView };
	}
	
	function mediamatic_cc_ifModalOpen(){
		var myFilter		= $(".mediamatic-editcategory-filter");
		var activeFolderID	= myFilter.val();
		localStorage.setItem('mediamaticModalActiveFolder', activeFolderID);
		
		if($('.media-frame-content').attr('aria-labelledby') === 'menu-item-browse'){
			activeFolderID	= $(".wpmediacategory-filter").val();
			localStorage.setItem('mediamaticModalActiveFolder', activeFolderID);
		}
		myFilter.on('change', function() {
			activeFolderID 	= this.value;
			localStorage.setItem('mediamaticModalActiveFolder', activeFolderID);
		});
		
		$(".wpmediacategory-filter").on('change',function(){
			activeFolderID 	= this.value;
			if($('.media-frame-content').attr('aria-labelledby') === 'menu-item-browse'){
				localStorage.setItem('mediamaticModalActiveFolder', activeFolderID);
			}
		});
	}
	
	function mediamatic_cc_recallMe(){
		$('.media-menu-item').on('click',function(){
			setTimeout(function(){
				mediamatic_cc_ifModalOpen();
			},3);
		});
	}

	
    jQuery(document).ajaxSend(function (e, xhs, req) {
        
        try {
            if(req.data.indexOf("action=delete-post") > -1){
                var attachmentID 	= req.context.id;
				
				var requestData		= {
					attachmentID: attachmentID,
					action: 'mediamaticAjaxCheckDeletingMedia',
					ajaxNonce: mediamaticConfig.nonce
				};
				
                jQuery.ajax({
                  type: "POST",
                  data: requestData,
                  url: ajaxurl,
                  success: function (fromdata){
					var fnQueriedObj	= jQuery.parseJSON(fromdata),
						result			= fnQueriedObj.terms,
						error			= fnQueriedObj.error,
						hiddenValue		= '';
					if(error === 'no'){
						if(result.length){
							$.each(result,function(index,value){
								hiddenValue += '' + value.term_id +  ',';
							});
							hiddenValue = hiddenValue.slice(0, hiddenValue.length - 1);
						}
						$('#mediamatic_hidden_terms').val(hiddenValue);
					}
				  }
                });
				
            }
        }catch(e) {}

    }.bind(this));


    jQuery(document).ajaxComplete(function (e, xhs, req) {
        try{
            if(req.data.indexOf("action=delete-post") > -1){
				
                var hiddenTermValue 	= $('#mediamatic_hidden_terms').val();
				
                if(hiddenTermValue){
                    var terms = hiddenTermValue.split(",");
                    $.each(terms, function(index, value){
                        mediamatic_eug_increase_count(value);
                    });
                }
				
            }
        }catch(e){}
    }.bind(this));
	
	
	

})( jQuery );



(function($){
	
    "use strict";
	
    var mediamaticHook 			= {};
	
    mediamaticHook.uploadMedia 	= function(){

        if (!$("body").hasClass("media-new-php")){
            return;
        }
		
        setTimeout(function(){
            if(uploader){
                uploader.bind('BeforeUpload', function(uploader, file) {
                    var params 		= uploader.settings.multipart_params;
                    params.ccFolder = $('.mediamatic-editcategory-filter').val();
                });
            }
        }.bind(this), 500);
    };

    $(document).ready(function(){
        var wp = window.wp;
        mediamaticHook.uploadMedia();

    });
})(jQuery);