window.wp = window.wp || {};
var mediaConfig = mediamaticConfig;

(function($){
	"use strict";
	var media = wp.media;

	var h = media.view.AttachmentFilters.extend({
		
		tagName:   'select',
		className: 'attachment-filters',
		id:        'mediamatic-attachment-filters',

		events: {
			change: 'change'
		},
		
		
		keys: [],
		

		initialize: function() {
			this.createFilters();
			_.extend( this.filters, this.options.filters );

			// Build `<option>` elements.
			this.$el.html( _.chain( this.filters ).map( function( filter, value ) {
				return {
					el: $( '<option></option>' ).val( value ).html( filter.text )[0],
					priority: filter.priority || 50
				};
			}, this ).sortBy('priority').pluck('el').value() );

			this.listenTo( this.model, 'change', this.select );
			this.select();
		},

		/**
		 * @abstract
		 */
		createFilters: function() {
			var filters = {};
		
			_.each(mediamaticFolders || {}, function( term, key ) 
			{
				var folderID 		= term['folderID'];
				var folderName 		= $("<div/>").html(term['folderName']).text();
				filters[folderID] 	= {
					text: folderName,
					priority: key
				};
				
				filters[folderID]['props'] = {};
				filters[folderID]['props'][mediaConfig.mediamaticFolder] = folderID;
			});
		
			// related to "All" only
			filters.all = {
				text: mediaConfig.mediamaticAllTitle,
				priority: -1
			};
			filters['all']['props'] = {};
			filters['all']['props'][mediaConfig.mediamaticFolder] = null;

			this.filters = filters;
		},

		/**
		 * When the selected filter changes, update the Attachment Query properties to match.
		 */
		change: function() {
			var filter = this.filters[ this.el.value ];
			if ( filter ) {
				this.model.set( filter.props );
			}
		},

		select: function() {
			var model = this.model,
				value = 'all',
				props = model.toJSON();

			_.find( this.filters, function( filter, id ) {
				var equal = _.all( filter.props, function( prop, key ) {
					return prop === ( _.isUndefined( props[ key ] ) ? null : props[ key ] );
				});

				if ( equal ) {
					return value = id;
				}
			});

			this.$el.val( value );
		}
		
	});
		
	var curAttachmentsBrowser 		= media.view.AttachmentsBrowser;
	media.view.AttachmentsBrowser 	= media.view.AttachmentsBrowser.extend({
		createToolbar: function() {

			//set backbone for attachment container
	        var treeLoaded = jQuery.Deferred();
	        this.$el.data("backboneView", this);
          
	        this._treeLoaded = treeLoaded;
	        //end set backbon for attachment container
			
			curAttachmentsBrowser.prototype.createToolbar.apply(this,arguments);

			var self = this;
			var myNewFilter = new h({
	        		className: 'wpmediacategory-filter attachment-filters',
    				controller: self.controller,
    				model:      self.collection.props,
    				priority:   -75
    			}).render();

			this.toolbar.set('mediamatic-filter', myNewFilter);
			myNewFilter.initialize();			

		}
	});
		
	
	
	// This code responds the sidebar to appear on popup media window
	// Remove below code for lite version
	
    if (typeof window.wp !== 'undefined' && typeof window.wp.Uploader === 'function') {
        var windowMedia = window.wp.media;
        var windowModal = windowMedia.view.Modal;
        windowMedia.view.Modal = windowMedia.view.Modal.extend({
            className: "mediamatic-modal",
            initialize: function () {
                windowModal.prototype.initialize.apply(this, arguments);
            }, open: function () {
                //$(".mediamatic-modal").removeClass("mediamatic-modal");
                if (windowModal.prototype.open.apply(this, arguments)) {
					
					// We need to add this for while re-open modal window without refresh
					if(!$(".mediamatic-modal").length) {
                        if($(".supports-drag-drop").length) {
                            $(".supports-drag-drop").each(function(){
                                if($(this).css("display") == "block" || $(this).css("display") == "inline-block") {
                                    console.log("class added");
                                    $(this).addClass("mediamatic-modal");
                                }
                            });
                        }
                    }
					

                    if($(".mediamatic-modal").length) {
						
						$(".mediamatic-custom-menu").remove();
						$(".mediamatic-custom-menu-trigger").remove();
						$(".mediamatic-modal .media-frame-menu").removeClass("has-mediamatic-menu");
						
						if($(".mediamatic-modal .media-frame").length) {
							if (!$(".mediamatic-custom-menu").length) {
								$(".mediamatic-modal .media-frame-menu").addClass("has-mediamatic-menu");
								$(".mediamatic-modal .media-modal-content").append('<div class="mediamatic-custom-menu-trigger"><img class="mediamatic_be_svg" src="'+mediaConfig.assetsURL+'img/menu.svg" alt="" /></div>');
								$(".mediamatic-modal .media-modal-content").append("<div class='mediamatic-custom-menu'></div>");
								$(".mediamatic-modal .mediamatic-custom-menu").load(mediaConfig.uploadURL + " #mediamatic_sidebar", function () {
									$('.mediamatic-custom-menu #mediamatic_sidebar, .mediamatic-custom-menu .cc_mediamatic_sidebar_in').css('width', '300px');

									MediamaticCore.init();
								});
							}
						}
                        
                    } else {
                        setTimeout(function(){
							var selectedFolderMediaId = -1;
							
                            if(selectedFolderMediaId != -1) {
                                $("#media-attachment-taxonomy-filter").each(function () {
                                    $(this).val(selectedFolderMediaId);
                                    $(this).trigger("change");
                                });
                            }
                        }, 1000);
                    }
                }
            }, close: function () {
                windowModal.prototype.close.apply(this, arguments);
                //$(".mediamatic-modal").removeClass("mediamatic-modal");
            }
        });
    }
	
	
	
})( jQuery );