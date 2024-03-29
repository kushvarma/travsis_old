<?php

	/* Add custom styles */
	add_action('wp_head','userpro_add_custom_styles', 99999);
	function userpro_add_custom_styles() {
		if (userpro_get_option('userpro_css')) {
			print '<style type="text/css">'.userpro_get_option('userpro_css').'</style>';
		}
		if(is_rtl()){
			echo '<script type="text/javascript">';
			?>
				jQuery(function(){
					jQuery('select').attr('class' , jQuery('select').attr('class')+' chosen-rtl');		
					jQuery('.chosen-container-single').attr('class' , 'chosen-container chosen-container-single chosen-rtl');
				});
			<?php 
			echo '</script>';
				}
	}
	
	/* Verify an Envato purchase */
	add_action('userpro_profile_update', 'userpro_verify_envato_purchase', 10, 2);
	function userpro_verify_envato_purchase($form, $user_id){
		global $userpro;
		if (isset($form['envato_purchase_code'])){
			$code = $form['envato_purchase_code'];
			if ($userpro->verify_purchase($code)) {
				$userpro->do_envato($user_id);
			} else {
				$userpro->undo_envato($user_id);
			}
		}
	}

	/* Enqueue Scripts */
	add_action('wp_enqueue_scripts', 'userpro_enqueue_scripts');
	function userpro_enqueue_scripts(){

		if ( userpro_get_option('googlefont') && !userpro_get_option('customfont') ) {
			if (is_ssl()){
				$fonts_url = 'https://fonts.googleapis.com/css?family='.userpro_get_option('googlefont').':400,400italic,700,700italic,300italic,300';
			} else {
				$fonts_url = 'http://fonts.googleapis.com/css?family='.userpro_get_option('googlefont').':400,400italic,700,700italic,300italic,300';
			}
			wp_register_style('userpro_google_font', $fonts_url);
			wp_enqueue_style('userpro_google_font');
		}
		
		/* CSS */
		if ( !userpro_get_option('rtl') ) {
			$css = 'css/userpro.min.css';
		} else {
			$css = 'css/userpro-rtl.min.css';
		}
		wp_register_style('userpro_min', userpro_url . $css );
		wp_enqueue_style('userpro_min');
		
		/* lightview */
		if (userpro_get_option('lightbox')) {
			wp_register_style('userpro_lightview', userpro_url . 'css/lightview/lightview.css' );
			wp_enqueue_style('userpro_lightview');
		}
		
		/* wp_enqueue_script */
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('userpro_jquery_ui_style', userpro_url . 'css/userpro-jquery-ui.css');

		if (userpro_get_option('lightbox')) {
			wp_register_script('userpro_swf', 'https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js' );
			wp_enqueue_script('userpro_swf');
			
			wp_register_script('userpro_spinners', userpro_url . 'scripts/spinners/spinners.min.js' );
			wp_enqueue_script('userpro_spinners');
			
			wp_register_script('userpro_lightview', userpro_url . 'scripts/lightview/lightview.js' );
			wp_enqueue_script('userpro_lightview');
		}

		wp_register_script('userpro_min', userpro_url . 'scripts/scripts.min.js' );
		wp_enqueue_script('userpro_min');
		
		///////////////
		$skin = userpro_get_option('skin');
		if (class_exists('userpro_sk_api') && is_dir( userpro_sk_path . 'skins/'.$skin ) ) {
			wp_register_style('userpro_skin_min', userpro_sk_url . 'skins/'.$skin.'/style.css');
			wp_enqueue_style('userpro_skin_min');
		} else {
			wp_register_style('userpro_skin_min', userpro_url . 'skins/'.$skin.'/style.css');
			wp_enqueue_style('userpro_skin_min');
		}
		if (locate_template('userpro/skins/'.$skin.'/style.css') ) {
			wp_register_style('userpro_skin_custom', get_template_directory_uri() . '/userpro/skins/'.$skin.'/style.css' );
			wp_enqueue_style('userpro_skin_custom');
		}

	}

	/* Remove bar except for admins */
	add_action('init', 'userpro_remove_admin_bar');
	function userpro_remove_admin_bar() {
		global $userpro;
		if (!current_user_can('manage_options') && !is_admin()) {
			if (userpro_get_option('hide_admin_bar')) {
				if ( userpro_get_option('allow_dashboard_for_these_roles') && userpro_is_logged_in() && $userpro->user_role_in_array( get_current_user_id(), explode(',',userpro_get_option('allow_dashboard_for_these_roles') ) ) ) {
				
				} else {
					show_admin_bar(false);
				}
			}
		}
	}
	
	/* Hook into WP normal login if panic key is used */
	add_action('login_form','userpro_panic_key');
	function userpro_panic_key(){
		if ( isset($_REQUEST['userpro_panic_key']) && userpro_get_option('userpro_panic_key') && $_REQUEST['userpro_panic_key'] == userpro_get_option('userpro_panic_key') ) {
	?>
		<input type="hidden" value="<?php echo userpro_get_option('userpro_panic_key'); ?>" id="userpro_panic_key" name="userpro_panic_key"></label>
	<?php
		}
	}
	
	/* Setup redirections */
	add_action('init','userpro_redirects');
	function userpro_redirects(){
		global $pagenow;

		// redirect dashboard
		if ('index.php' == $pagenow && is_admin()) {
			if (userpro_is_logged_in() && userpro_allow_dashboard_redirect() ){
				wp_redirect( userpro_dashboard_redirect_uri() );
				exit();
			}
		}
		
		// redirect dashboard profile
		if( 'profile.php' == $pagenow ) {
			if (userpro_is_logged_in() && userpro_allow_profile_redirect() ){
				wp_redirect( userpro_profile_redirect_uri() );
				exit();
			}
		}
		
		// redirect login
		if ('wp-login.php' == $pagenow && !isset($_REQUEST['action']) ) {
			if ( !userpro_is_logged_in() && isset($_REQUEST['userpro_panic_key']) && userpro_get_option('userpro_panic_key') && $_REQUEST['userpro_panic_key'] == userpro_get_option('userpro_panic_key') ) {
				return true;
			}
			if (userpro_allow_login_redirect() ){
				if (isset($_GET['redirect_to'])){
					$url = add_query_arg('redirect_to', urlencode( $_GET['redirect_to'] ), userpro_login_redirect_uri() );
				} else {
					$url = userpro_login_redirect_uri();
				}
				wp_redirect( $url );
				exit();
			}
		}
		
		// redirect lostpassword
		if ('wp-login.php' == $pagenow && isset($_REQUEST['action']) && $_REQUEST['action'] == 'lostpassword') {
			if (userpro_allow_login_redirect() ){
				wp_redirect( userpro_login_redirect_uri() );
				exit();
			}
		}
		
		// redirect register
		if ('wp-login.php' == $pagenow && isset($_REQUEST['action']) && $_REQUEST['action'] == 'register') {
			if (userpro_allow_register_redirect() ){
				wp_redirect( userpro_register_redirect_uri() );
				exit();
			}
		}
		
	}
	
	/**
	Clear cache on some actions
	**/
	
	add_action ('userpro_after_account_verified', "userpro_cache_clear");
	add_action ('userpro_after_account_unverified', "userpro_cache_clear");
	add_action('userpro_after_profile_updated_fb', 'userpro_cache_clear');
	add_action('userpro_after_profile_updated','userpro_cache_clear');
	add_action ('user_register', "userpro_cache_clear");
	add_action ('delete_user', "userpro_cache_clear");
	function userpro_cache_clear(){
		global $userpro;
		$userpro->clear_cache();
	}
	
	add_action('userpro_after_new_registration', "userpro_cache_clear_frontend");
	function userpro_cache_clear_frontend($user_id){
		global $userpro;
		$userpro->clear_cache();
	}
	
	add_action( 'profile_update', 'userpro_profile_updated', 10, 2 );
	function userpro_profile_updated( $user_id, $old_user_data ) {
		global $userpro;
		if ( !empty($user_id) ) {
			$current_user_data = WP_User::get_data_by( 'id', $user_id );
			$display_name = $current_user_data->display_name;
			update_user_meta($user_id, 'display_name', $display_name);
		}
		$userpro->clear_cache();
	}
	
	add_action('edit_user_profile_update', 'userpro_edit_user_profile_update');
	function userpro_edit_user_profile_update($user_id) {
		global $userpro;
		$userpro->clear_cache();
	}
	
	add_action('personal_options_update', 'userpro_personal_options_update');
	function userpro_personal_options_update($user_id) {
		global $userpro;
		$userpro->clear_cache();
	}
