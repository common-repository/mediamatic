<?php

use Mediamatic\Helper;

class Mediamatic_Sidebar {

	public function __construct() 
	{
		add_filter( 'restrict_manage_posts', array($this, 'mediamaticRestrictManagePosts'));
		add_filter( 'posts_clauses', array($this, 'mediamaticPostsClauses'), 10, 2);
		
		add_action( 'admin_enqueue_scripts', array($this, 'mediamaticEnqueueStyles' )); 									// load style files
		add_action( 'admin_enqueue_scripts', array($this, 'mediamaticEnqueueScripts' ));									// load js files
		
		add_action( 'init', array($this,'mediamaticAddFolderToAttachments' ));												// register MEDIAMATIC taxonomy
		add_action( 'admin_footer-upload.php', array($this,'mediamaticInitSidebar'));										// get interface
		
		add_action( 'wp_ajax_mediamaticAjaxAddCategory', array($this,'mediamaticAjaxAddCategory'));							// ajax: add new category
		add_action( 'wp_ajax_mediamaticAjaxDeleteCategory', array($this,'mediamaticAjaxDeleteCategory'));					// ajax: delete existing category
		add_action( 'wp_ajax_mediamaticAjaxClearCategory', array($this,'mediamaticAjaxClearCategory'));						// ajax: delete existing category
		add_action( 'wp_ajax_mediamaticAjaxRenameCategory', array($this,'mediamaticAjaxRenameCategory'));					// ajax: rename existing category
		
		add_action( 'wp_ajax_mediamaticAjaxUpdateSidebarWidth', array($this,'mediamaticAjaxUpdateSidebarWidth'));			// ajax: update sidebar width
		
		add_action( 'wp_ajax_mediamaticAjaxMoveMultipleMedia', array($this,'mediamaticAjaxMoveMultipleMedia'));				// ajax: move multiple media
		add_action( 'wp_ajax_mediamaticAjaxGetTermsByMedia', array($this,'mediamaticAjaxGetTermsByMedia'));					// ajax: get terms by media for single media
		add_action( 'wp_ajax_mediamaticAjaxMoveSingleMedia', array($this,'mediamaticAjaxMoveSingleMedia'));					// ajax: move singe media
		
		add_action( 'wp_ajax_mediamaticAjaxCheckDeletingMedia', array($this,'mediamaticAjaxCheckDeletingMedia'));			// ajax: check deleting media	
		
		add_action( 'wp_ajax_mediamaticAjaxMoveCategory', array($this,'mediamaticAjaxMoveCategory'));						// move category
		add_action( 'wp_ajax_mediamaticAjaxUpdateFolderPosition', array($this,'mediamaticAjaxUpdateFolderPosition' ));		// update folder position
		
		add_option( 'mediamatic_sidebar_width', 280);																	// add option for sidebar width
		
		add_filter( 'pre-upload-ui', array($this, 'mediamaticPreUploadUserInterface'));									// upload uploader category to "Add new" 
		add_filter( 'wp_kses_allowed_html', array($this, 'mediamatic_allowed_html'), 10, 2 );
		
		
		if(MEDIAMATIC_PLUGIN_NAME != 'Mediamatic'){
			add_action( 'admin_notices', [$this, 'pro_version_notice'] );
		}
		//Support Elementor
        if (defined('ELEMENTOR_VERSION')) {
            add_action('elementor/editor/after_enqueue_scripts', [$this, 'mediamaticScripts']);
            add_action('elementor/editor/after_enqueue_scripts', [$this, 'mediamaticStyles']);
        }
		
	}
	
	public function mediamatic_allowed_html($allowed, $context){
		if (is_array($context))
		{
			return $allowed;
		}

		if ($context === 'post')
		{
			$allowed['input']['type'] = true;
			$allowed['input']['name'] = true;
			$allowed['input']['id'] = true;
			$allowed['input']['class'] = true;
			$allowed['input']['value'] = true;
			$allowed['input']['placeholder'] = true;
			$allowed['input']['autocomplete'] = true;
			
			$allowed['select']['id'] = true;
			$allowed['select']['name'] = true;
			$allowed['select']['class'] = true;
			$allowed['option']['value'] = true;
		}

		return $allowed;
	}
	
	public function pro_version_notice(){
		global $pagenow;
		if ( $pagenow == 'upload.php' ) {
			 echo '<div class="notice notice-warning is-dismissible">
					 <p>'.esc_html__('Mediamatic PRO has more handy features. You could rename a folder, add subfolders easily, clear folders, and search for folders. It also enables folders panel on the media pop-up window.', MEDIAMATIC_TEXT_DOMAIN).' <a href="https://frenify.net/envato/themedo/plugin/mediamatic/1/" target="_blank">Mediamatic PRO</a></p>
				 </div>';
		}
	}
	
	
	public function mediamaticEnqueueStyles(){
		$this->mediamaticStyles();
	}
	
	
	public function mediamaticStyles()
	{
		wp_enqueue_style( 'iaoalert', MEDIAMATIC_ASSETS_URL . 'css/iaoalert.css', array(), MEDIAMATIC_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediamatic-admin', MEDIAMATIC_ASSETS_URL . 'css/core.css', array(), MEDIAMATIC_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediamatic-front', MEDIAMATIC_ASSETS_URL . 'css/front.css', array(), MEDIAMATIC_PLUGIN_NAME, 'all' );
		wp_enqueue_style( 'mediamatic-rtl', MEDIAMATIC_ASSETS_URL . 'css/rtl.css', array(), MEDIAMATIC_PLUGIN_NAME, 'all' );
		
		if(MEDIAMATIC_PLUGIN_NAME == 'Mediamatic'){
			$custom_css = "#mediamatic-attachment-filters{display: none;}";
			wp_add_inline_style( 'mediamatic-admin', $custom_css );
		}
		
	}
	

	public function mediamaticEnqueueScripts()
	{
		$this->mediamaticScripts();
	}
	
	public function mediamaticScripts()
	{
		
		$allFilesText		= esc_html__('All Files', MEDIAMATIC_TEXT_DOMAIN);
		$uncategorizedText	= esc_html__('Uncategorized', MEDIAMATIC_TEXT_DOMAIN);
		$taxonomy 			= apply_filters('mediamatic_taxonomy', MEDIAMATIC_FOLDER);
		$dropdownOptions 	= array(
			'taxonomy'        => $taxonomy,
			'hide_empty'      => false,
			'hierarchical'    => true,
			'orderby'         => 'name',
			'show_count'      => true,
			'walker'          => new Mediamatic_Walker_Category_Mediagridfilter(),
			'value'           => 'id',
			'echo'            => false
		);
		$attachmentTerms 	= wp_dropdown_categories( $dropdownOptions );
		$attachmentTerms 	= preg_replace( array( "/<select([^>]*)>/", "/<\/select>/" ), "", $attachmentTerms );
		
		wp_register_script( 'inline-script-handle-header', '' );
		wp_enqueue_script( 'inline-script-handle-header' );
		wp_add_inline_script( 'inline-script-handle-header', '/* <![CDATA[ */ var mediamaticFolders = [{"folderID":"all","folderName":"'. esc_html($allFilesText) .'"}, {"folderID":"-1","folderName":"'. esc_html($uncategorizedText) .'"},' . wp_kses_post(substr($attachmentTerms, 2)) . ']; /* ]]> */' );
		
		
		wp_enqueue_script('jquery-ui-draggable');
    	wp_enqueue_script('jquery-ui-droppable');

		wp_register_script('iaoalert', MEDIAMATIC_ASSETS_URL . 'js/third-party-plugins/iaoalert.js',['jquery'], MEDIAMATIC_PLUGIN_NAME, false);
		wp_register_script('nicescroll', MEDIAMATIC_ASSETS_URL . 'js/third-party-plugins/nicescroll.js',['jquery'], MEDIAMATIC_PLUGIN_NAME, false);
		wp_register_script('mediamatic-resizable', MEDIAMATIC_ASSETS_URL . 'js/resizable.js',['jquery'], MEDIAMATIC_PLUGIN_NAME, false);
		wp_register_script('mediamatic-core', MEDIAMATIC_ASSETS_URL . 'js/core.js',['jquery'], MEDIAMATIC_PLUGIN_NAME, true);
		wp_register_script('mediamatic-filter', MEDIAMATIC_ASSETS_URL . 'js/filter.js',['jquery'], MEDIAMATIC_PLUGIN_NAME, false);
		wp_register_script('mediamatic-select-filter', MEDIAMATIC_ASSETS_URL . '/js/select-filter.js', ['media-views'], MEDIAMATIC_PLUGIN_NAME, true );
		wp_register_script('mediamatic-upload', MEDIAMATIC_ASSETS_URL . 'js/upload.js', ['jquery'], MEDIAMATIC_PLUGIN_NAME, false );

		wp_localize_script(
			'mediamatic-core',
			'mediamaticConfig',
			[
				'plugin' 						=> MEDIAMATIC_PLUGIN_NAME,
				'pluginURL' 					=> MEDIAMATIC_URL,
				'nonce' 						=> wp_create_nonce( 'ajax-nonce' ),
				'uploadURL' 					=> admin_url( 'upload.php' ),
				'ajaxUrl' 						=> admin_url( 'admin-ajax.php' ),
				'moveOneFile' 					=> esc_html__( 'Move 1 file', MEDIAMATIC_TEXT_DOMAIN ),
				'move' 							=> esc_html__( 'Move', MEDIAMATIC_TEXT_DOMAIN ),
		    	'files' 						=> esc_html__( 'files', MEDIAMATIC_TEXT_DOMAIN ),
				'newFolderText' 				=> esc_html__( 'New Subfolder', MEDIAMATIC_TEXT_DOMAIN ),
				'clearMediaText' 				=> esc_html__( 'Clear Media', MEDIAMATIC_TEXT_DOMAIN ),
				'renameText' 					=> esc_html__( 'Rename Folder', MEDIAMATIC_TEXT_DOMAIN ),
				'deleteText' 					=> esc_html__( 'Delete Folder', MEDIAMATIC_TEXT_DOMAIN ),
				'clearText' 					=> esc_html__( 'Clear Folder', MEDIAMATIC_TEXT_DOMAIN ),
				'cancelText' 					=> esc_html__( 'Cancel', MEDIAMATIC_TEXT_DOMAIN ),
				'confirmText' 					=> esc_html__( 'Confirm', MEDIAMATIC_TEXT_DOMAIN ),
				'areYouSure' 					=> esc_html__( 'Are you confident?', MEDIAMATIC_TEXT_DOMAIN ),
				'willBeMovedToUncategorized'	=> esc_html__( 'All media inside this folder gets moved to "Uncategorized" folder.', MEDIAMATIC_TEXT_DOMAIN ),
				'hasSubFolder'					=> esc_html__( 'This folder contains subfolders, you should delete the subfolders first!', MEDIAMATIC_TEXT_DOMAIN ),
				'slugError' 					=> esc_html__( 'Unfortunately, you already have a folder with that name.', MEDIAMATIC_TEXT_DOMAIN ),
				'enterName' 					=> esc_html__( 'Please, enter your folder name!', MEDIAMATIC_TEXT_DOMAIN ),
				'item' 							=> esc_html__( 'item', MEDIAMATIC_TEXT_DOMAIN ),
				'items' 						=> esc_html__( 'items', MEDIAMATIC_TEXT_DOMAIN ),
				'currentFolder' 				=> $this->getCurrentFolder(),
				'noItemDOM' 					=> $this->noItemForListMode(),
				'mediamaticAllTitle' 			=> esc_html__('All categories', MEDIAMATIC_TEXT_DOMAIN),
			]
		);
		wp_localize_script(
			'mediamatic-filter',
			'mediamaticConfig2',
			[
				'pluginURL' 					=> MEDIAMATIC_URL,
				'ajaxUrl' 						=> admin_url( 'admin-ajax.php' ),
				'nonce' 						=> wp_create_nonce( 'ajax-nonce' ),
				'moveOneFile' 					=> esc_html__( 'Move 1 file', MEDIAMATIC_TEXT_DOMAIN ),
				'move' 							=> esc_html__( 'Move', MEDIAMATIC_TEXT_DOMAIN ),
		    	'files' 						=> esc_html__( 'files', MEDIAMATIC_TEXT_DOMAIN ),
			]
		);
		
		wp_localize_script(
			'mediamatic-select-filter',
			'mediamaticConfig',
			[
				'mediamaticFolder' 				=> MEDIAMATIC_FOLDER,
				'mediamaticAllTitle' 			=> esc_html__('All categories', MEDIAMATIC_TEXT_DOMAIN),
				'uploadURL' 					=> admin_url( 'upload.php' ),
				'assetsURL' 					=> MEDIAMATIC_ASSETS_URL
			]
		);
		
		wp_localize_script(
			'mediamatic-upload',
			'mediamaticConfig',
			[
				'nonce' 						=> wp_create_nonce('ajax-nonce')
			]
		);

		wp_enqueue_script( 'iaoalert' );
		wp_enqueue_script( 'nicescroll' );
		wp_enqueue_script( 'mediamatic-resizable' );
		wp_enqueue_script( 'mediamatic-core' );
		wp_enqueue_script( 'mediamatic-filter' );
		wp_enqueue_script( 'mediamatic-select-filter' );
		wp_enqueue_script( 'mediamatic-upload' );
		
		
		
	}
	
	public function noItemForListMode()
	{
		return '<tr class="no-items"><td class="colspanchange" colspan="6">'.esc_html__('No media files found.', MEDIAMATIC_TEXT_DOMAIN).'</td></tr>';
	}
	
	public function getCurrentFolder()
	{
		if(isset($_GET['cc_mediamatic_folder'])){
			return sanitize_text_field($_GET['cc_mediamatic_folder']);
		}
		return '';
	}
	
	public function mediamaticRestrictManagePosts()
	{
	    $scr 	= get_current_screen();
	    if($scr->base !== 'upload'){
	        return;
	    }
	    echo '<select id="mediao-attachment-filters" class="wpmediacategory-filter attachment-filters" name="cc_mediamatic_folder"></select>';
	}

	public function getSidebarWidth()
	{
		$sidebarWidth 		= (int) get_option('mediamatic_sidebar_width', 380);
		if($sidebarWidth < 250 || $sidebarWidth > 750){
			$sidebarWidth 	= 380;
		}
		return $sidebarWidth;
	}

	public function mediamaticInitSidebar()
	{
		$output  		= '';
		$helper	 		= new Helper;
		$sidebarWidth 	= $this->getSidebarWidth().'px;';
		
		$output .= '<div class="cc_mediamatic_temporary">';
			$output .= '<div id="mediamatic_sidebar" class="cc_mediamatic_sidebar" style="width:'.$sidebarWidth.'">';
				$output .= '<div class="cc_mediamatic_sidebar_in" style="width:'.$sidebarWidth.'">';
					$output .= $helper->getSidebarHeader();
					$output .= $helper->getSidebarContent();
					$output .= '<input type="hidden" id="mediamatic_hidden_terms">';
				$output .= '</div>';
			$output .= '</div>';
			$output .= $this->splitter();
		$output .= '</div>';
		
		
		echo wp_kses_post($output);
	}
	
	public function splitter()
	{
		if(MEDIAMATIC_PLUGIN_NAME == 'Mediamatic'){
			$html = '<div class="mediamatic_splitter active">
					<span class="splitter_holder">
						<span class="splitter_a"></span>
						<span class="splitter_b"></span>
						<span class="splitter_c"></span>
					</span>
				</div>';
		}else{
			$html = '<div class="mediamatic_splitter"></div>';
		}
		return $html;
	}
	
	public function mediamaticPreUploadUserInterface() 
	{
		$helper	 	 	= new Helper;
        $terms 		 	= $helper->mediamaticTermTreeArray(MEDIAMATIC_FOLDER, 0);
		$otherOptions 	= $helper->mediamaticTermTreeOption($terms);
		$text 		 	= esc_html__("New files go to chosen category", MEDIAMATIC_TEXT_DOMAIN);
		$output			= '';
		
		// top section
		$output		.= '<p class="cc_upload_paragraph attachments-category">';
			$output		.= $text;
		$output		.= '</p>';
		
		// select section
		$output		.= '<p class="cc_upload_paragraph">';
			$output		.= '<select name="ccFolder" class="mediamatic-editcategory-filter">';
				$output		.= '<option value="-1">1.'.esc_html__('Uncategorized', MEDIAMATIC_TEXT_DOMAIN).'</option>';
				$output		.= $otherOptions;
			$output		.= '</select>';
		$output		.= '</p>';
		
		// echo result
		echo wp_kses_post($output);
	}
	
	public function mediamaticAjaxAddCategory()
	{
		$categoryName 	= sanitize_text_field($_POST["categoryName"]);
		$parent 		= sanitize_text_field($_POST["parent"]);
		
		
		// check category name
		$name 			= self::mediamaticCheckMetaName($categoryName, $parent);
		$newTerm 		= wp_insert_term($name, MEDIAMATIC_FOLDER, array(
			'name' 		=> $name,
			'parent' 	=> $parent
		));

		if (is_wp_error($newTerm)){
			echo 'error';
		}else{
			add_term_meta( $newTerm["term_id"], 'folder_position', 9999 );
			
			
			$buffyArray = array(
				'termID' 			=> $newTerm["term_id"],
				'termName' 			=> $name,
			);

			die(json_encode($buffyArray));
		}
		
	}
	
	public function mediamaticAjaxDeleteCategory()
	{
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$selectedTerm 		= get_term($categoryID , MEDIAMATIC_FOLDER );
		$count 				= $selectedTerm->count ? $selectedTerm->count : 0;
		$deleteTerm			= wp_delete_term( $categoryID, MEDIAMATIC_FOLDER );
		
		
		if(is_wp_error($deleteTerm)){
			$error		= 'yes';
		}else{
			$error		= 'no';
		}
		$buffyArray 	= array(
			'error' 	=> $error,
			'count' 	=> $count,
		);
		
		die(json_encode($buffyArray));
		
	}
	
	public function mediamaticAjaxClearCategory()
	{
		global $wpdb;
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$selectedTerm 		= get_term($categoryID , MEDIAMATIC_FOLDER );
		$count 				= $selectedTerm->count ? $selectedTerm->count : 0;
		
		$wpdb->query($wpdb->prepare( "UPDATE {$wpdb->prefix}term_taxonomy SET count=%d WHERE term_id=%d AND taxonomy=%s", 0, $categoryID, MEDIAMATIC_FOLDER));
		$wpdb->query($wpdb->prepare( "DELETE FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id=%d", $categoryID));
		
		$buffyArray 	= array(
			'error' 	=> 'no',
			'count' 	=> $count,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediamaticAjaxRenameCategory()
	{
		$categoryID 		= sanitize_text_field($_POST["categoryID"]);
		$categoryTitle		= sanitize_text_field($_POST["categoryTitle"]);
		$newSlug			= $this->mediamaticSlugGenerator($categoryTitle,$categoryID);
		$renameCategory		= wp_update_term($categoryID, MEDIAMATIC_FOLDER, array(
			'name' 			=> $categoryTitle,
			'slug' 			=> $newSlug
		));
		
		if(is_wp_error($renameCategory)){
			$error			= 'yes';
		}else{
			$error			= 'no';
		}
		$buffyArray 		= array(
			'error' 		=> $error,
			'title' 		=> $categoryTitle,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediamaticAjaxUpdateSidebarWidth()
	{
		$width 	= sanitize_text_field($_POST['width']);
		$error	= 'yes';
		
		if(update_option( 'mediamatic_sidebar_width', $width )){
			$error			= 'no';
		}
		
		$buffyArray 		= array(
			'error' 		=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	
	public function recursive_sanitize_text_field($array_or_string) {
		if( is_string($array_or_string) ){
			$array_or_string = sanitize_text_field($array_or_string);
		}elseif( is_array($array_or_string) ){
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = recursive_sanitize_text_field($value);
				}
				else {
					$value = sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}
	
	
	public function mediamaticAjaxMoveMultipleMedia()
	{
		$IDs 		= $this->recursive_sanitize_text_field($_POST['IDs']);
		$folderID	= sanitize_text_field($_POST['folderID']);
        $result 	= array();

        foreach ($IDs as $ID){
            $termList 	= wp_get_post_terms( sanitize_text_field($ID), MEDIAMATIC_FOLDER, array( 'fields' => 'ids' ) );
            $from 		= -1;

            if(count($termList)){
                $from 	= $termList[0];
            }

            $obj 		= (object) array('id' => $ID, 'from' => $from, 'to' => $folderID);
            $result[] 	= $obj;

            wp_set_object_terms( $ID, intval($folderID), MEDIAMATIC_FOLDER, false );

        }

		
		$buffyArray 		= array(
			'result' 		=> $result,
		);
		die(json_encode($buffyArray));
		
	}
	
	public function mediamaticAjaxGetTermsByMedia()
	{
		$error		= 'no';
		$nonce 		= sanitize_text_field($_POST['nonce']);
		$terms		= array();
		
		if(!wp_verify_nonce($nonce, 'ajax-nonce')){
			$error 	= 'yes';
		}
        if(!isset($_POST['ID'])){
            $error 	= 'yes';
        }else{
			$ID		= (int) sanitize_text_field($_POST['ID']);
			$terms  = get_the_terms($ID, MEDIAMATIC_FOLDER);
		}
		
		$buffyArray 		= array(
			'terms' 		=> $terms,
			'error' 		=> $error,
			'id' 			=> $ID,
		);
		die(json_encode($buffyArray));
	}
	
	public function mediamaticAjaxMoveSingleMedia()
	{
		$error							= 'no';
		
		if (!isset($_POST['mediaID'])){
			 $error 					= 'yes';
		}else{
			$mediaID 					= absint(sanitize_text_field($_POST['mediaID']));
			
			if(empty($_POST['attachments']) || empty($_POST['attachments'][ $mediaID ])){
				 $error 				= 'yes';
			}else{
				$attachment_data 		= $_POST['attachments'][ $mediaID ];
				$post 					= get_post( $mediaID, ARRAY_A );
				if('attachment' != $post['post_type']){
					$error 				= 'yes';
				}else{
					$post 				= apply_filters( 'attachment_fields_to_save', $post, $attachment_data );

					if(isset($post['errors'])){
						$errors 		= $post['errors']; 
						unset( $post['errors'] );
					}

					wp_update_post($post);

					wp_set_object_terms( $mediaID, intval(sanitize_text_field($_POST['folderID'])), MEDIAMATIC_FOLDER, false );
					if (!$attachment 	= wp_prepare_attachment_for_js($mediaID)){
						$error 			= 'yes';
					}
				}
			}
		}
		
		
		$buffyArray 		= array(
			'attachment' 		=> $attachment,
			'error' 			=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	
	public function mediamaticSlugGenerator($categoryName,$ID)
	{
		global $wpdb;
		$categoryName 	= strtolower($categoryName);
	   	$newSlug		= preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName);
		
		$ID = sanitize_text_field($ID);
		$ID = esc_sql($ID);
		
		$count 			= $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}terms WHERE slug=%s AND term_id<>%d", $newSlug, $ID ));
		
		
		if($count > 0){
			$newSlug	= $newSlug . '1';
			$newSlug	= $this->mediamaticSlugGenerator($newSlug,$ID);
		}
		return $newSlug;
	}
	
	public function mediamaticAjaxUpdateFolderPosition()
	{
		$results 	= sanitize_text_field($_POST["data"]);
		$results 	= explode('#', $results);
		foreach ($results as $result) {
			$result = explode(',', $result);
			update_term_meta($result[0], 'folder_position', $result[1]);
		}
		die();
	}
	
	public function mediamaticAjaxMoveCategory()
	{
		$current 		= sanitize_text_field($_POST["current"]);
		$parent 		= sanitize_text_field($_POST["parent"]);
		
		
		$checkError 	= wp_update_term($current, MEDIAMATIC_FOLDER, array(
			'parent' 	=> $parent
		));
				

		if(is_wp_error($checkError)){
			$error		= 'yes';
		}else{
			$error		= 'no';
		}
		$buffyArray 	= array(
			'error' 	=> $error,
		);
		die(json_encode($buffyArray));
		
	}
	
	public static function mediamaticCheckMetaName($name, $parent)
	{
		if(!$parent){ $parent = 0; }
 		
		$terms 	= get_terms( MEDIAMATIC_FOLDER, array('parent' => $parent, 'hide_empty' => false) );
		$check 	= true;

		if(count($terms)){
			foreach ($terms as $term){
				if($term->name === $name){
					$check = false;
					break;
				}
			}
		}else{
			return $name;
		}

		
		if($check){
			return $name;			
		}

		$arr = explode('_', $name);	

		if($arr && count($arr) > 1){	
			$suffix = array_values(array_slice($arr, -1))[0];

			array_pop($arr);

			$originName = implode($arr);

			if(intval($suffix)){
				$name = $originName . '_' . (intval($suffix)+1);
			}

		}else{
			$name = $name . '_1';
		}		

		$name = self::mediamaticCheckMetaName($name, $parent);

		return $name;

	}
	
	public function mediamaticAddFolderToAttachments()
	{
		register_taxonomy(	MEDIAMATIC_FOLDER, 
			 array( "attachment" ), 
		 	 array( "hierarchical" 				=> true, 
				    "labels"					=> array(), 
					'show_ui' 					=> true,
					'show_in_menu' 				=> false,
					'show_in_nav_menus'			=> false,
					'show_in_quick_edit'		=> false,
					'update_count_callback' 	=> '_update_generic_term_count',
					'show_admin_column'			=> false,
					"rewrite" 					=> false 
			)
		);
	}
	
	
	public function mediamaticPostsClauses($clauses, $query)
	{
		global $wpdb;
		
		if (isset($_GET['cc_mediamatic_folder'])){
			
			$folder 		= sanitize_text_field($_GET['cc_mediamatic_folder']);
			
			if (!empty($folder) != ''){
				$folder 	= (int)$folder;
				$wpdbPrefix	= $wpdb->prefix;
				
				if($folder > 0){
					$clauses['where'] 	.= ' AND ('.$wpdbPrefix.'term_relationships.term_taxonomy_id = '.$folder.')';
					$clauses['join'] 	.= ' LEFT JOIN '.$wpdbPrefix.'term_relationships ON ('.$wpdbPrefix.'posts.ID = '.$wpdbPrefix.'term_relationships.object_id)';
				}else{
					
					$folders = get_terms(MEDIAMATIC_FOLDER, array(
						'hide_empty' => false
					));
					$folderIDs = array();
					foreach ($folders as $k => $folder) {
						$folderIDs[] = $folder->term_id;
					}
					
					$folderIDs = esc_sql($folderIDs);
					
					$extraQuery = "SELECT `ID` FROM ".$wpdbPrefix."posts LEFT JOIN ".$wpdbPrefix."term_relationships ON (".$wpdbPrefix."posts.ID = ".$wpdbPrefix."term_relationships.object_id) WHERE (".$wpdbPrefix."term_relationships.term_taxonomy_id IN (".implode(', ', $folderIDs)."))";
					$clauses['where'] .= " AND (".$wpdbPrefix."posts.ID NOT IN (".$extraQuery."))";
				}
			}
		}
		
		return $clauses;
	}
	
	
	
	public function mediamaticAjaxCheckDeletingMedia()
	{
		$attachmentID	= '';
		$error			= 'no';
		$terms			= array();
		$ajaxNonce		= sanitize_text_field($_POST['ajaxNonce']);

		if(!wp_verify_nonce($ajaxNonce,'ajax-nonce' )){
			$error		= 'yes';
		}
		
		if(!isset($_POST['attachmentID'])){
           $error		= 'yes';
        }
        if($error == 'no'){
			$attachmentID	= absint(sanitize_text_field($_POST['attachmentID']));
        	$terms  		= get_the_terms($attachmentID, MEDIAMATIC_FOLDER);
		}
		
		$buffyArray 	= array(
			'error' 	=> $error,
			'terms' 	=> $terms,
		);
		die(json_encode($buffyArray));
    }

}
new Mediamatic_Sidebar();


// Custom Category Walker
class Mediamatic_Walker_Category_Mediagridfilter extends \Walker_CategoryDropdown 
{
    function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{
		$space 				= str_repeat( '&nbsp;', $depth * 3 );
		
		if(isset($category->name)){
			$folderName		= $category->name;
			$folderID		= $category->term_id;
			$folderName 	= apply_filters( 'list_cats', $folderName, $category );
			
			$output .= ',{"folderID":"' . $folderID . '",';
			$output .= '"folderName":"' . $space . $folderName . '"}';
			
		}	
    }
}