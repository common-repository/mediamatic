(function ($){

	"use strict";
	
	var mediaConfig 		= mediamaticConfig2;
	
    var MediamaticFilter 		= {
		
		// get ajax URL
		ajaxurl:						mediaConfig.ajaxUrl,
		nonce:							mediaConfig.nonce,

		moveOneFile:					mediaConfig.moveOneFile,
		moveText:						mediaConfig.move,
		filesText:						mediaConfig.files,
		
		dragItem:						null,
		
        init: function () {
			
			var self = this;
			this.appendDragger();
			this.dragItem = $("#mediamatic-dragger");
			//this.dragAndDropMedia();
			
			$( document ).ajaxComplete(function( event, xhr, settings ) {
				if(settings.data != undefined && settings.data != "" && settings.data.indexOf("action=query-attachments") != -1) {
					self.dragAndDropMedia();
					
				}
			});
			
        },
		appendDragger: function(){
			var self	= this;
			if($('#mediamatic-dragger').length === 0){
				$("body").append('<div id="mediamatic-dragger" data-id="">' + self.moveOneFile + '</div>');
			}
		},
		dragAndDropMedia: function(){
			var self		= this,
				textDrag	= self.moveOneFile;
			
			
			
			
			$('.attachments-browser li.attachment').draggable({
				
				revert: "invalid",
				containment: "document",
				cursor: 'move',
				cursorAt: {
					left: 2,
					top: 2
				},
				
				helper: function(){
					return $("<div></div>");
				},
				
				start: function(){
					
					var selectedFiles = $('.attachments li.selected').length;
					if (selectedFiles > 0) {textDrag = self.moveText + ' ' + selectedFiles + ' ' + self.filesText;}
					
					$('body').addClass('cc_draging');
					self.dragItem.html(textDrag);
					self.dragItem.addClass('active');
				},
				
				stop: function() {
					$('body').removeClass('cc_draging');
					self.dragItem.removeClass('active');
					textDrag = self.moveOneFile;
				},
				
				drag: function(){
					var id = $(this).data("id");

					self.dragItem.data("id", id);

					self.dragItem.css({
						"top": event.clientY - 15,
						"left": event.clientX - 15,
					});
				}
				
				
			});
			
			
			setTimeout(function(){
				$("li.category_item").droppable({
					accept: ".attachments-browser li.attachment",
					hoverClass: 'hover',
					classes: {
						"ui-droppable-active": "ui-state-highlight"
					},
					drop: function() {

						var folderID 	= $(this).attr('data-id');
						var IDs 		= self.getSelectedFiles();

						//console.log(IDs);

						if(IDs.length){
							self.moveMultipleMedia(IDs, folderID);
						}else{
							self.moveSingleMedia(folderID);
						}

					}
				});
			}, 100);
			
			
			
		},
		
		
		getSelectedFiles: function(){
			var selectedFiles 	= $('.attachments li.selected'),
				IDs 			= [];

			if (selectedFiles.length) {
				selectedFiles.each(function (index, item) {
					IDs.push($(item).data("id"));
				});
				return IDs;
			}

			return false;
		},
		
		moveMultipleMedia: function(IDs, folderID) {
			var self 			= this,
				currentFolder 	= $(".wpmediacategory-filter").val();
			
			var requestData 	= {
				action: 'mediamaticAjaxMoveMultipleMedia',
				IDs: IDs,
				folderID: folderID,
			};

			$.ajax({
				type: 'POST',
				url: self.ajaxurl,
				cache: false,
				data: requestData,
				success: function(data) {
					var fnQueriedObj 	= $.parseJSON(data),
						result			= fnQueriedObj.result;
					
					result.forEach(function(item){
						self.updateCount(item.from, item.to);
						if (currentFolder !== 'all') {
							$('ul.attachments li[data-id="' + item.id + '"]').detach();
						}
					});
					
					self.disableBulkSelect();
					
				},
				error: function(xhr, textStatus, errorThrown){
					console.log(errorThrown);
					console.log(textStatus);
					console.log(xhr);
				}
			});



		},
		
		disableBulkSelect: function(){
			if(!$(".wp-admin.upload-php .media-toolbar.wp-filter .media-toolbar-secondary .media-button.delete-selected-button").hasClass("hidden")){
			  	$(".wp-admin.upload-php .media-toolbar.wp-filter .media-toolbar-secondary .media-button.select-mode-toggle-button").trigger("click");
			}
		},
		
		moveSingleMedia: function(folderID){
			
			var self			= this,
				mediaID			= self.dragItem.data("id"),
				mediaItem	 	= $('.attachment[data-id="' + mediaID + '"]'),
				currentFolder 	= $(".wpmediacategory-filter").val();
			currentFolder		= $('.category_item.active').attr('data-id');
			
			
			if (folderID === 'all' || folderID 	== currentFolder){
				return false;
			}
			
			self.startPreloader();
			
			
			var requestData = {
				action: 'mediamaticAjaxGetTermsByMedia',
				nonce: self.nonce,
				ID: mediaID,
			};

			$.ajax({
				type: 'POST',
				url: self.ajaxurl,
				cache: false,
				data: requestData,
				success: function(data) {
					var fnQueriedObj 	= $.parseJSON(data),
						error			= fnQueriedObj.error;
					if(error === 'no'){
						self.moveSingleMediaAjaxProcess(fnQueriedObj.terms,folderID,mediaID,currentFolder,mediaItem);
					}else{
						self.stopPreloader();
					}
					
				},
				error: function(xhr, textStatus, errorThrown){
					console.log(errorThrown);
					console.log(textStatus);
					console.log(xhr);
				}
			});

			
		},
		
		moveSingleMediaAjaxProcess: function(result,folderID,mediaID,currentFolder,mediaItem){
			var self	= this,
				terms 	= Array.from(result, v => v.term_id);
			//check if drag to owner folder

			if (terms.includes(parseInt(folderID))) {
				self.stopPreloader();
				return;
			}
			
			var attachments = {};

			attachments[mediaID] = { menu_order: 0 };
			
			var requestData = {
				action: 'mediamaticAjaxMoveSingleMedia',
				attachments: attachments,
				mediaID: mediaID,
				folderID: folderID,
			};

			$.ajax({
				type: 'POST',
				url: self.ajaxurl,
				cache: false,
				data: requestData,
				success: function(data) {
					var fnQueriedObj 	= $.parseJSON(data);
					var error			= fnQueriedObj.error;

					if (error === 'no') {
						
						

						$.each(terms, function (index, value) {
							self.updateCount(value, folderID);
							
						});
						
						//if attachment not in any terms (folder)
						if(currentFolder === 'all' && !terms.length) {
							self.updateCount(-1, folderID);
						}

						if(parseInt(currentFolder) === -1) {
							self.updateCount(-1, folderID);
						}

						if(currentFolder !== 'all') {
							mediaItem.detach(); // remove this media if not selected "all files"
						}

					}

					self.stopPreloader();
					
				},
				error: function(xhr, textStatus, errorThrown){
					console.log(errorThrown);
					console.log(textStatus);
					console.log(xhr);
				}
			});
		},
		
		
		
		/* начать preloader */
		startPreloader: function(){
			$('.mediamatic_be_loader').addClass('active');
		},
		/* остановить preloader */
		stopPreloader: function(){
			$('.mediamatic_be_loader').removeClass('active');
		},
		
		
		updateCount: function(from, to){
			
			from 	= parseInt(from);
			to 		= parseInt(to);
			
			
			if(from !== to){
				if(from){
					var countTermFrom 	= $('ul li.category_item[data-id="' + from + '"] .cc_count').text();
					
					countTermFrom 		= parseInt(countTermFrom) -1;
					if(countTermFrom){
						$('ul li.category_item[data-id="' + from + '"] .cc_count').text(countTermFrom);
					}else{
						$('ul li.category_item[data-id="' + from + '"] .cc_count').text(0);
					}
				}
				if(to){
					var countTermTo 	= $('ul li.category_item[data-id="' + to + '"] .cc_count').text();
					countTermTo 		= parseInt(countTermTo) +1;
					$('ul li.category_item[data-id="' + to + '"] .cc_count').text(countTermTo);
				}
			}	
		},
		
		
    };
	
	$(document).ready(function(){MediamaticFilter.init();});

})(jQuery);