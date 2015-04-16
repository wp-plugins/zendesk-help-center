<?php
/*
Plugin Name: Zendesk Help Center Backup by BestWebSoft
Plugin URI: http://bestwebsoft.com/products/
Description: This plugin allows to backup Zendesk Help Center.
Author: BestWebSoft
Version: 0.1
Author URI: http://bestwebsoft.com/
License: GPLv3 or later
*/
 
/*  © Copyright 2015  BestWebSoft  ( http://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Function are using to add on admin-panel Wordpress page 'bws_plugins' and sub-page of this plugin */
if ( ! function_exists( 'add_zndskhc_admin_menu' ) ) {
	function add_zndskhc_admin_menu() {
		bws_add_general_menu( plugin_basename( __FILE__ ) );
		add_submenu_page( 'bws_plugins', __( 'Zendesk HC Backup Settings', 'zendesk_hc' ), 'Zendesk HC Backup', 'manage_options', "zendesk_hc.php", 'zndskhc_settings_page' );
	}
}

if ( ! function_exists ( 'zndskhc_init' ) ) {
	function zndskhc_init() {
		global $zndskhc_options, $zndskhc_plugin_info;

		/* Function adds translations in this plugin */
		load_plugin_textdomain( 'zendesk_hc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_functions.php' );

		if ( empty( $zndskhc_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$zndskhc_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_version_check( plugin_basename( __FILE__ ), $zndskhc_plugin_info, "4.0" );
	}
}

if ( ! function_exists( 'zndskhc_admin_init' ) ) {
	function zndskhc_admin_init() {
		global $bws_plugin_info, $zndskhc_plugin_info, $zndskhc_result, $zndskhc_options,  $zndskhc_result;

		if ( ! isset( $bws_plugin_info ) || empty( $bws_plugin_info ) )			
			$bws_plugin_info = array( 'id' => '208', 'version' => $zndskhc_plugin_info["Version"] );		
		
		/* Call register settings function */
		if ( isset( $_GET['page'] ) && "zendesk_hc.php" == $_GET['page'] )
			register_zndskhc_settings();	
	}
}

/* Function create column in table wp_options for option of this plugin. If this column exists - save value in variable. */
if ( ! function_exists( 'register_zndskhc_settings' ) ) {
	function register_zndskhc_settings() {
		global $zndskhc_options, $zndskhc_plugin_info, $zndskhc_options_default;
		$plugin_db_version = '1.0';

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$email = 'wordpress@' . $sitename;

		$zndskhc_options_default = array(
			'plugin_option_version'	=> $zndskhc_plugin_info["Version"],
			'plugin_db_version'		=> '',
			'subdomain'				=> '',
			'user'					=> '',
			'password'				=> '',
			'time'					=> '48',
			'backup_elements'		=> array( 
				'categories'	=> '1',
				'sections'		=> '1',
				'articles'		=> '1',
				'comments'		=> '1',
				'labels'		=> '1',
				'attachments'	=> '1'
			),
			'emailing_fail_backup'	=> '1',
			'email'					=> $email,
			'last_synch'			=> ''
		);

		/* Install the option defaults */
		if ( ! get_option( 'zndskhc_options' ) )
			add_option( 'zndskhc_options', $zndskhc_options_default );

		$zndskhc_options = get_option( 'zndskhc_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $zndskhc_options['plugin_option_version'] ) || $zndskhc_options['plugin_option_version'] != $zndskhc_plugin_info["Version"] ) {
			if ( '0' != $zndskhc_options['time'] ) {
				$time = time() + $zndskhc_options['time']*60*60;
				wp_schedule_event( $time, 'schedules_hours', 'auto_synchronize_zendesk_hc' );
			}

			$zndskhc_options = array_merge( $zndskhc_options_default, $zndskhc_options );
			$zndskhc_options['plugin_option_version'] = $zndskhc_plugin_info["Version"];
			update_option( 'zndskhc_options', $zndskhc_options );
		}

		if ( ! isset( $zndskhc_options['plugin_db_version'] ) || $zndskhc_options['plugin_db_version'] < $plugin_db_version ) {
			zndskhc_db_table();
			$zndskhc_options['plugin_db_version'] = $plugin_db_version;
			update_option( 'zndskhc_options', $zndskhc_options );
		}
	}
}

if ( ! function_exists( 'zndskhc_db_table' ) ) {
	function zndskhc_db_table() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_categories` (
			`id` int NOT NULL,
			`position` int NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`description` char(255) NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_sections` (
			`id` int NOT NULL,
			`category_id` int NOT NULL,
			`position` int NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`description` char(255) NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_articles` (
			`id` int NOT NULL,
			`category_id` int NOT NULL,
			`section_id` int,
			`position` int NOT NULL,
			`author_id` int NOT NULL,
			`comments_disabled` int(1) NOT NULL,
			`promoted` int(1) NOT NULL,
			`updated_at` datetime NOT NULL,
			`name` char(255) NOT NULL,
			`title` char(255) NOT NULL,
			`body` text NOT NULL,
			`locale` char(5) NOT NULL,
			`source_locale` char(5) NOT NULL,
			`labels` char(255),
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );	

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_labels` (
			`id` int NOT NULL,
			`name` char(255) NOT NULL,
			`updated_at` datetime NOT NULL,			
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_comments` (
			`id` int NOT NULL,
			`author_id` int NOT NULL,
			`source_type` char(255) NOT NULL,
			`source_id` int NOT NULL,
			`body` text NOT NULL,
			`locale` char(5) NOT NULL,
			`updated_at` datetime NOT NULL,			
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "zndskhc_attachments` (
			`id` int NOT NULL,
			`url` char(255) NOT NULL,
			`article_id` int NOT NULL,
			`file_name` char(255) NOT NULL,
			`content_url` char(255) NOT NULL,
			`content_type` char(255) NOT NULL,
			`size` int NOT NULL,
			`inline` TINYINT(1) NOT NULL,
			`created_at` datetime NOT NULL,			
			`updated_at` datetime NOT NULL,			
			UNIQUE KEY id (id)
		);";
		dbDelta( $sql );
	}
}

/* Function is forming page of the settings of this plugin */
if ( ! function_exists( 'zndskhc_settings_page' ) ) {
	function  zndskhc_settings_page() {
		global $wpdb, $zndskhc_options, $zndskhc_plugin_info, $zndskhc_options_default;
		$message = $error = '';

		$file_check_name = dirname( __FILE__ )  . "/backup.log";
		if ( ! file_exists( $file_check_name ) ) {
			if ( $handle = fopen( $file_check_name, "w+" ) ) {
				$log_size = 0;
				fclose( $handle );
			} else {
				$log_error = __( "Error creating log file" , 'zendesk_hc' ) . ' ' . $file_check_name . '.';	
			}
		}

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'settings' ) {			
			if ( file_exists( $file_check_name ) ) {
				$log_size = round( filesize( dirname( __FILE__ )  . "/backup.log" ) / 1024, 2 );
			}
		}

		if ( isset( $_REQUEST['zndskhc_synch'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ) ) {
			$result = zndskhc_synchronize( false );
			if ( true !== $result )
				$error = $result;
			else
				$message = __( "Data is updated successfully" , 'zendesk_hc' );	
		}

		if ( isset( $_REQUEST['zndskhc_submit_clear'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ) ) {			
			if ( $handle = fopen( $file_check_name, "w" ) ) {
				fwrite( $handle, '' );
				fclose( $handle );
				@chmod( $file_check_name, 0755 );	
				$message = __( "The log file is cleared." , 'zendesk_hc' );
				$log_size = 0;
			} else
				$error = __( "Couldn't clear log file" , 'zendesk_hc' );
		}

		if ( isset( $_REQUEST['zndskhc_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ) ) {			
			$zndskhc_options['subdomain'] 	= stripslashes( esc_html( $_REQUEST['zndskhc_subdomain'] ) );
			$zndskhc_options['user'] 		= stripslashes( esc_html( $_REQUEST['zndskhc_user'] ) );
			$zndskhc_options['password'] 	= stripslashes( esc_html( $_REQUEST['zndskhc_password'] ) );
			if ( $zndskhc_options['time'] != intval( $_REQUEST['zndskhc_time'] ) ) {
				$zndskhc_options['time'] = intval( $_REQUEST['zndskhc_time'] );
				/* Add or delete hook of auto/handle mode */
				if ( wp_next_scheduled( 'auto_synchronize_zendesk_hc' ) )
					wp_clear_scheduled_hook( 'auto_synchronize_zendesk_hc' );

				if ( '0' != $zndskhc_options['time'] ) {
					$time = time() + $zndskhc_options['time']*60*60;
					wp_schedule_event( $time, 'schedules_hours', 'auto_synchronize_zendesk_hc' );
				}				
			}
			$zndskhc_options['backup_elements']['categories'] = ( isset( $_REQUEST['zndskhc_categories_backup'] ) ) ? 1 : 0;
			$zndskhc_options['backup_elements']['sections'] = ( isset( $_REQUEST['zndskhc_sections_backup'] ) ) ? 1 : 0;
			$zndskhc_options['backup_elements']['articles'] = ( isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;
			$zndskhc_options['backup_elements']['comments'] = ( isset( $_REQUEST['zndskhc_comments_backup'] ) && isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;
			$zndskhc_options['backup_elements']['labels'] = ( isset( $_REQUEST['zndskhc_labels_backup'] ) ) ? 1 : 0;
			$zndskhc_options['backup_elements']['attachments'] = ( isset( $_REQUEST['zndskhc_attachments_backup'] ) && isset( $_REQUEST['zndskhc_articles_backup'] ) ) ? 1 : 0;

			$zndskhc_options['emailing_fail_backup'] 	=  isset( $_REQUEST['zndskhc_emailing_fail_backup'] ) ? 1 : 0;
			$zndskhc_options['email'] 	= stripslashes( esc_html( $_REQUEST['zndskhc_email'] ) );
			if ( ! is_email( $zndskhc_options['email'] ) )
				$zndskhc_options['email'] = $zndskhc_options_default['email'];

			if ( empty( $error ) ) {
				update_option( 'zndskhc_options', $zndskhc_options );
				$message = __( "Settings saved" , 'zendesk_hc' );
			}
		} ?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php _e( 'Zendesk HC Backup Settings', 'zendesk_hc' ); ?></h2>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab <?php if ( ! isset( $_GET['tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=zendesk_hc.php"><?php _e( 'Backup', 'zendesk_hc' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'settings' ) echo ' nav-tab-active'; ?>" href="admin.php?page=zendesk_hc.php&tab=settings"><?php _e( 'Settings', 'zendesk_hc' ); ?></a>
				<a class="nav-tab" href="http://bestwebsoft.com/products/zendesk-help-center/faq" target="_blank"><?php _e( 'FAQ', 'zendesk_hc' ); ?></a>
			</h2>
			<div class="updated fade" <?php if ( '' == $message || '' != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error" <?php if ( '' == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<div id="zndskhc_settings_notice" class="updated fade" style="display:none"><p><strong><?php _e( "Notice:", 'zendesk_hc' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them, please don't forget to click 'Save Changes' button.", 'zendesk_hc' ); ?></p></div>
			<?php if ( ! isset( $_GET['tab'] ) ) {
				if ( ! empty( $zndskhc_options['last_synch'] ) ) { ?>
					<p><?php _e( 'Last synchronization with Zendesk HC was on' , 'zendesk_hc' ); echo ' ' . $zndskhc_options['last_synch']; ?></p>
				<?php }
				if ( ! empty( $log_error ) ) { ?>
					<div class="error"><p><?php echo $log_error; ?></p></div>
				<?php } else
					zndskhc_get_logs(); ?>
				<form method="post" action="admin.php?page=zendesk_hc.php">					
					<p class="submit">						
						<input id="zndskhc_synch_button" type="submit" class="button-primary" value="<?php _e( 'Synchronize now', 'zendesk_hc' ); ?>" />
						<img id="zndskhc_loader" src="<?php echo plugins_url( 'images/ajax-loader.gif', __FILE__ ); ?>" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ); ?>
						<input type="hidden" name="zndskhc_synch" value="submit" />
					</p>					
				</form>
			<?php } elseif ( isset( $_GET['tab'] ) && $_GET['tab'] == 'settings' ) { ?>
				<?php if ( ! empty( $log_error ) ) { ?>
					<div class="error"><p><?php echo $log_error; ?></p></div>
				<?php } else { ?>
					<form method="post" action="admin.php?page=zendesk_hc.php&tab=settings">
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e( 'Log file size', 'zendesk_hc' ); ?>:</th>
								<td>
									<p>
										&#126; <?php echo $log_size . ' ' . __( 'Kbyte', 'zendesk_hc' ); ?>
										<?php if ( 0 != $log_size ) { ?>
											&#160;&#160;&#160;<input type="submit" class="button" value="<?php _e( 'Clear', 'zendesk_hc' ); ?>" />
										<?php } ?>
									</p>
								</td>
							</tr>
						</table>
						<input type="hidden" name="zndskhc_submit_clear" value="submit" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ); ?>
					</form>
				<?php } ?>					
				<form method="post" action="admin.php?page=zendesk_hc.php&tab=settings" id="zndskhc_settings_form">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Zendesk Information', 'zendesk_hc' ); ?></th>
							<td>
								<input type="text" name="zndskhc_subdomain" value="<?php echo $zndskhc_options['subdomain']; ?>" /> <?php _e( 'subdomain', 'zendesk_hc' ); ?><br />
								<input type="text" name="zndskhc_user" value="<?php echo $zndskhc_options['user']; ?>" /> <?php _e( 'user', 'zendesk_hc' ); ?><br />
								<input type="password" name="zndskhc_password" value="<?php echo $zndskhc_options['password']; ?>" /> <?php _e( 'password', 'zendesk_hc' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Synchronize Zendesk HC every', 'zendesk_hc' ); ?></th>
							<td>
								<input type="number" min="0" name="zndskhc_time" value="<?php echo $zndskhc_options['time']; ?>" /> (<?php _e( 'hours' , 'zendesk_hc' ); ?>)
								<br /><span class="bws_info"><?php _e( 'Set 0 to disable auto backup.', 'zendesk_hc' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Backup', 'zendesk_hc' ); ?></th>
							<td>
								<input type="checkbox" name="zndskhc_categories_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['categories'] ) echo 'checked'; ?> /> <?php _e( 'Categories' , 'zendesk_hc' ); ?><br />
								<input type="checkbox" name="zndskhc_sections_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['sections'] ) echo 'checked'; ?> /> <?php _e( 'Sections' , 'zendesk_hc' ); ?><br />
								<input type="checkbox" name="zndskhc_articles_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['articles'] ) echo 'checked'; ?> /> <?php _e( 'Articles' , 'zendesk_hc' ); ?><br />
								<input type="checkbox" name="zndskhc_comments_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['comments'] ) echo 'checked'; ?> /> <?php _e( 'Articles Comments' , 'zendesk_hc' ); ?><br />
								<input type="checkbox" name="zndskhc_labels_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['labels'] ) echo 'checked'; ?> /> <?php _e( 'Articles Labels' , 'zendesk_hc' ); ?><br />
								<input type="checkbox" name="zndskhc_attachments_backup" value="1" <?php if ( $zndskhc_options['backup_elements']['attachments'] ) echo 'checked'; ?> /> <?php _e( 'Articles Attachments' , 'zendesk_hc' ); ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Send email in case of backup failure', 'zendesk_hc' ); ?></th>
							<td>
								<input type="checkbox" name="zndskhc_emailing_fail_backup" value="1" <?php if ( $zndskhc_options['emailing_fail_backup'] ) echo 'checked'; ?> /><br />
								<input type="email" name="zndskhc_email" value="<?php echo $zndskhc_options['email']; ?>" />
							</td>
						</tr>
					</table>					
					<p class="submit">
						<input type="hidden" name="zndskhc_submit" value="submit" />
						<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'zendesk_hc' ); ?>" />						
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'zndskhc_nonce_name' ); ?>
					</p>					
				</form>				
			<?php }
			bws_plugin_reviews_block( $zndskhc_plugin_info['Name'], 'zendesk-help-center' ) ?>
		</div>
	<?php }
}

if ( ! function_exists( 'zndskhc_synchronize' ) ) {
	function zndskhc_synchronize( $auto_mode = true ) {
		global $wpdb, $zndskhc_options;

		if ( empty( $zndskhc_options ) )
			$zndskhc_options = get_option( 'zndskhc_options' );

		if ( empty( $zndskhc_options['subdomain'] ) || empty( $zndskhc_options['user'] ) || empty( $zndskhc_options['password'] ) ) {
			$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Backup failed as some plugin settings are empty. To fix it go to', 'zendesk_hc' ) . ' ' . __( 'settings page', 'zendesk_hc' );
			zndskhc_log( $log );
			if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Backup failed as some plugin settings are empty. To fix it go to', 'zendesk_hc' ) . ' <a href="' . get_admin_url( null, 'admin.php?page=zendesk_hc.php&tab=settings' ) . '">' . __( 'settings page', 'zendesk_hc' ) . '</a>.';
				zndskhc_send_mail( $log );
			}
			return $log;
		}

		/* get categories */
		if ( 1 == $zndskhc_options['backup_elements']['categories'] ) {
			$all_categories = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_categories`", ARRAY_A );
			$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/categories.json';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$json_resp = curl_exec( $ch );
			$array_resp = json_decode( $json_resp, true );
			curl_close( $ch );
			if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Categories backup', 'zendesk_hc' ) . ' - ' . __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} elseif ( isset( $array_resp['error'] ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Categories backup', 'zendesk_hc' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} else {
				$added = $updated = $deleted = 0;
				foreach ( $array_resp['categories'] as $key => $value ) {
					$category = false;
					foreach ( $all_categories as $key_cat => $value_cat ) {
						if ( $value_cat['id'] == $value['id'] ) {
							$category = $value_cat;
							unset( $all_categories[ $key_cat ] );
							break;
						}
					}

					if ( empty( $category ) ) {
						$wpdb->insert( $wpdb->prefix . "zndskhc_categories", 
							array( 'id' 			=> $value['id'], 
									'position' 		=> $value['position'],
									'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'],
									'description'	=> $value['description'],
									'locale'		=> $value['locale'],
									'source_locale'	=> $value['source_locale'] ),
							array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );	
						$added++;
					} elseif ( strtotime( $category['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
						$wpdb->update( $wpdb->prefix . "zndskhc_categories",
							array( 'position' 		=> $value['position'],
									'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'],
									'description'	=> $value['description'],
									'locale'		=> $value['locale'],
									'source_locale'	=> $value['source_locale'] ), 
							array( 'id' => $value['id'] ),
							array(  '%s', '%s', '%s', '%s', '%s', '%s' ) ); 
						$updated++;
					}

				}
				if ( ! empty( $all_categories ) ) {
					foreach ( $all_categories as $key_cat => $value_cat ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_categories` WHERE `id` = %s", $value_cat['id'] ) );
						$deleted++;
					}
				}
				if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
					$log = __( 'Categories backup', 'zendesk_hc' ) . ':';
					if ( $added != 0 )
						$log .= ' ' . $added . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated != 0 )
						$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted != 0 )
						$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
			}
		}

		/* get sections */
		if ( 1 == $zndskhc_options['backup_elements']['sections'] ) {
			$all_sections = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_sections`", ARRAY_A );
			$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/sections.json';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$json_resp = curl_exec( $ch );
			$array_resp = json_decode( $json_resp, true );
			curl_close( $ch );
			if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Sections backup', 'zendesk_hc' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} elseif ( isset( $array_resp['error'] ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Sections backup', 'zendesk_hc' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} else {
				$added = $updated = $deleted = 0;
				foreach ( $array_resp['sections'] as $key => $value ) {
					$section = false;
					foreach ( $all_sections as $key_sec => $value_sec ) {
						if ( $value_sec['id'] == $value['id'] ) {
							$section = $value_sec;
							unset( $all_sections[ $key_sec ] );
							break;
						}
					}
					
					if ( empty( $section ) ) {
						$wpdb->insert( $wpdb->prefix . "zndskhc_sections", 
							array( 'id' 			=> $value['id'], 
									'category_id'	=> $value['category_id'],
									'position' 		=> $value['position'],
									'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'],
									'description'	=> $value['description'],
									'locale'		=> $value['locale'],
									'source_locale'	=> $value['source_locale'] ),
							array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );	
						$added++;
					} elseif ( strtotime( $section['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
						$wpdb->update( $wpdb->prefix . "zndskhc_sections",
							array( 'category_id'	=> $value['category_id'],
									'position' 		=> $value['position'],
									'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'],
									'description'	=> $value['description'],
									'locale'		=> $value['locale'],
									'source_locale'	=> $value['source_locale'] ), 
							array( 'id' => $value['id'] ),
							array(  '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) ); 
						$updated++;
					}				
				}
				if ( ! empty( $all_sections ) ) {
					foreach ( $all_sections as $key_sec => $value_sec ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_sections` WHERE `id` = %s", $value_sec['id'] ) );
						$deleted++;
					}
				}
				if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
					$log = __( 'Sections backup', 'zendesk_hc' ) . ':';
					if ( $added != 0 )
						$log .= ' ' . $added . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated != 0 )
						$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted != 0 )
						$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
			}
		}
		
		/* get articles */
		if ( 1 == $zndskhc_options['backup_elements']['articles'] ) {
			$all_articles = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_articles`", ARRAY_A );
			$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles.json';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$json_resp = curl_exec( $ch );
			$array_resp = json_decode( $json_resp, true );
			curl_close( $ch );
			if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Articles backup', 'zendesk_hc' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} elseif ( isset( $array_resp['error'] ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Articles backup', 'zendesk_hc' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} else {
				$added = $updated = $deleted = 0;
				$added_comment = $updated_comment = $deleted_comment = 0;
				$added_attach = $updated_attach = $deleted_attach = 0;
				$attachments_backup_error = '';				
				
				foreach ( $array_resp["articles"] as $key => $value ) {
					if ( $value['draft'] != true ) {

						$article = $attachments_backup_error_current = $new_or_updated = false;

						foreach ( $all_articles as $key_art => $value_art ) {
							if ( $value_art['id'] == $value['id'] ) {
								$article = $value_art;
								unset( $all_articles[ $key_art ] );
								break;
							}
						}

						if ( ! isset( $value['category_id'] ) )
							$value['category_id'] = 0;
						if ( ! isset( $value['labels'] ) )
							$value['labels'] = '';

						if ( empty( $article ) ) {
							$wpdb->insert( $wpdb->prefix . "zndskhc_articles", 
								array( 'id' 				=> $value['id'], 
										'category_id'		=> $value['category_id'],
										'section_id'		=> $value['section_id'],
										'position' 			=> $value['position'],
										'author_id' 		=> $value['author_id'],
										'comments_disabled'	=> $value['comments_disabled'],
										'promoted'			=> $value['promoted'],
										'name'				=> $value['name'],
										'title'				=> $value['title'],
										'body'				=> $value['body'],
										'locale'			=> $value['locale'],
										'source_locale'		=> $value['source_locale'],
										'labels'			=> $value['labels'] ),
								array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );	
							$added++;
							$new_or_updated = true;
						} elseif ( strtotime( $article['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
							$wpdb->update( $wpdb->prefix . "zndskhc_articles",
								array( 'category_id'		=> $value['category_id'],
										'section_id'		=> $value['section_id'],
										'position' 			=> $value['position'],
										'comments_disabled'	=> $value['comments_disabled'],
										'promoted'			=> $value['promoted'],
										'name'				=> $value['name'],
										'title'				=> $value['title'],
										'body'				=> $value['body'],
										'locale'			=> $value['locale'],
										'source_locale'		=> $value['source_locale'],
										'labels'			=> $value['labels'] ), 
								array( 'id' => $value['id'] ),
								array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
							$updated++;
							$new_or_updated = true;
						}
						/* get articles comments */
						if ( $new_or_updated && 1 == $zndskhc_options['backup_elements']['comments'] ) {
							$all_article_comments = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `source_id` = '" . $value['id'] . "' AND `source_type` = 'Article'", ARRAY_A );
							$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/' . $value['id'] . '/comments.json';
							$ch = curl_init();
							curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
							curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
							curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
							curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
							curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
							$json_resp_comments = curl_exec( $ch );
							$array_resp_comments = json_decode( $json_resp_comments, true );
							curl_close( $ch );
							if ( !is_array( $array_resp_comments ) && empty( $array_resp_comments ) ) {
								$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Comments backup', 'zendesk_hc' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
								zndskhc_log( $log );
								if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
									zndskhc_send_mail( $log );
								return $log;
							} elseif ( isset( $array_resp_comments['error'] ) ) {
								$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Comments backup', 'zendesk_hc' ) . ' - ' . $array_resp_comments['error'] . ' (' . $array_resp_comments['description'] . ')';
								zndskhc_log( $log );
								if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
									zndskhc_send_mail( $log );
								return $log;
							} else {								
								foreach ( $array_resp_comments["comments"] as $comments_key => $comments_value ) {

									$comment = false;
									foreach ( $all_article_comments as $key_comment => $value_comment ) {
										if ( $value_comment['id'] == $value['id'] ) {
											$comment = $value_comment;
											unset( $all_article_comments[ $key_comment ] );
											break;
										}
									}
								
									if ( empty( $comment ) ) {
										$wpdb->insert( $wpdb->prefix . "zndskhc_comments", 
											array( 'id' 				=> $comments_value['id'], 
													'author_id' 		=> $comments_value['author_id'],
													'source_type' 		=> $comments_value['source_type'],
													'source_id' 		=> $comments_value['source_id'],
													'body'				=> $comments_value['body'],
													'locale'			=> $comments_value['locale'],
													'updated_at'		=> $comments_value['updated_at'] ),
											array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );	
										$added_comment++;
									} elseif ( strtotime( $comment['updated_at'] ) < strtotime( $comments_value['updated_at'] ) ) {
										$wpdb->update( $wpdb->prefix . "zndskhc_comments",
											array( 'author_id'			=> $comments_value['author_id'],
													'source_type'		=> $comments_value['source_type'],
													'source_id' 		=> $comments_value['source_id'],
													'body'				=> $comments_value['body'],
													'locale'			=> $comments_value['locale'],
													'updated_at'		=> $comments_value['updated_at'] ), 
											array( 'id' => $comments_value['id'] ),
											array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
										$updated_comment++;
									}
								}
								if ( ! empty( $all_article_comments ) ) {
									foreach ( $all_article_comments as $key_comment => $value_comment ) {
										$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `id` = %s AND `source_type` = 'Article'", $value_comment['id'] ) );
										$deleted_comment++;
									}
								}						
							}
						}	

						/* get attachments */
						if ( $new_or_updated && 1 == $zndskhc_options['backup_elements']['attachments'] ) {			
							$all_attachments = $wpdb->get_results( "SELECT `id`, `updated_at`, `file_name` FROM `" . $wpdb->prefix . "zndskhc_attachments`", ARRAY_A );
							$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/' . $value['id'] . '/attachments.json';
							$ch = curl_init();
							curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
							curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
							curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
							curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
							curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
							$json_resp = curl_exec( $ch );
							$array_resp_attach = json_decode( $json_resp, true );
							curl_close( $ch );
							if ( !is_array( $array_resp_attach ) && empty( $array_resp_attach ) ) {
								$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Attachments backup', 'zendesk_hc' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
								zndskhc_log( $log );
								if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
									zndskhc_send_mail( $log );
								return $log;
							} elseif ( isset( $array_resp_attach['error'] ) ) {
								$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Attachments backup', 'zendesk_hc' ) . ' - ' . $array_resp_attach['error'] . ' (' . $array_resp_attach['description'] . ')';
								zndskhc_log( $log );
								if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
									zndskhc_send_mail( $log );
								return $log;
							} else {
								foreach ( $array_resp_attach["article_attachments"] as $attach_key => $attach_value ) {
									$attachment = false;
									foreach ( $all_attachments as $key_attach => $value_attach ) {
										if ( $value_attach['id'] == $attach_value['id'] ) {
											$attachment = $value_attach;
											unset( $all_attachments[ $key_attach ] );
											break;
										}
									}

									if ( empty( $attachment ) ) {
										$result = zndskhc_attachments_backup( 'added', $attach_value['id'] . '-' . $attach_value['file_name'], $attach_value['content_url'] );
										if ( $result != true ) {
											$attachments_backup_error_current = true;
											$attachments_backup_error .= __( 'Error adding file', 'zendesk_hc' ) . ' ' . $attach_value['id'] . '-' . $attach_value['file_name'] . '. ';
										} else {
											$wpdb->insert( $wpdb->prefix . "zndskhc_attachments", 
												array( 'id' 			=> $attach_value['id'], 
														'url'			=> $attach_value['url'],
														'article_id'	=> $attach_value['article_id'],
														'file_name'		=> $attach_value['file_name'],
														'content_url'	=> $attach_value['content_url'],
														'content_type'	=> $attach_value['content_type'],
														'size'			=> $attach_value['size'],
														'inline'		=> $attach_value['inline'],
														'created_at'	=> $attach_value['created_at'],
														'updated_at'	=> $attach_value['updated_at'] ),
												array( '%s', '%s', '%s' ) );	
											$added_attach++;
										}
									} elseif ( strtotime( $attachment['updated_at'] ) < strtotime( $attach_value['updated_at'] ) ) {
										$result = zndskhc_attachments_backup( 'updated', $attach_value['id'] . '-' . $attach_value['file_name'], $attach_value['content_url'] );
										if ( $result != true ) {
											$attachments_backup_error_current = true;
											$attachments_backup_error .= __( 'Error updating file', 'zendesk_hc' ) . ' ' . $attach_value['id'] . '-' . $attach_value['file_name'] . '. ';
										} else {
											$wpdb->update( $wpdb->prefix . "zndskhc_attachments",
												array( 'url'			=> $attach_value['url'],
														'article_id'	=> $attach_value['article_id'],
														'file_name'		=> $attach_value['file_name'],
														'content_url'	=> $attach_value['content_url'],
														'content_type'	=> $attach_value['content_type'],
														'size'			=> $attach_value['size'],
														'inline'		=> $attach_value['inline'],
														'created_at'	=> $attach_value['created_at'],
														'updated_at'	=> $attach_value['updated_at'] ), 
												array( 'id' => $attach_value['id'] ),
												array(  '%s', '%s' ) ); 
											$updated_attach++;
										}
									}				
								}
								if ( ! empty( $all_attachments ) ) {
									foreach ( $all_attachments as $key_attach => $value_attach ) {						
										$result = zndskhc_attachments_backup( 'deleted', $value_attach['id'] . '-' . $value_attach['file_name'] );
										if ( $result != true ) {
											$attachments_backup_error_current = true;
											$attachments_backup_error .= __( 'Error deleting file', 'zendesk_hc' ) . ' ' . $value_attach['id'] . '-' . $value_attach['file_name'] . '. ';
										} else {
											$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_attachments` WHERE `id` = %s", $value_attach['id'] ) );
											$deleted_attach++;
										}
									}
								}	
							}
						}
						if ( ! $attachments_backup_error_current ) {
							$wpdb->update( $wpdb->prefix . "zndskhc_articles",
									array( 'updated_at'		=> $value['updated_at'] ), 
									array( 'id' => $value['id'] ),
									array( '%s' ) );
						}	
					}
				}
				if ( ! empty( $all_articles ) ) {
					foreach ( $all_articles as $key_art => $value_art ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_articles` WHERE `id` = %s", $value_art['id'] ) );
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_comments` WHERE `source_id` = %s AND `source_type` = 'Article'", $value_art['id'] ) );
						$deleted++;
					}
				}
				if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
					$log = __( 'Articles backup', 'zendesk_hc' ) . ':';
					if ( $added != 0 )
						$log .= ' ' . $added . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated != 0 )
						$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted != 0 )
						$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
				if ( $added_comment != 0 || $updated_comment != 0 || $deleted_comment != 0 ) {
					$log = __( 'Comments backup', 'zendesk_hc' ) . ':';
					if ( $added_comment != 0 )
						$log .= ' ' . $added_comment . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated_comment != 0 )
						$log .= ' ' . $updated_comment . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted_comment != 0 )
						$log .= ' ' . $deleted_comment . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
				if ( ! empty( $attachments_backup_error ) ) {
					$upload_dir = wp_upload_dir();
					$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Attachments backup', 'zendesk_hc' ) . ' ( ' . $upload_dir['basedir'] . '/zendesk_hc_attachments/' . ' ) - ' . $attachments_backup_error;
					zndskhc_log( $log );
					if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
						zndskhc_send_mail( $log );
				}
				if ( $added_attach != 0 || $updated_attach != 0 || $deleted_attach != 0 ) {
					$log = __( 'Attachments backup', 'zendesk_hc' ) . ':';
					if ( $added_attach != 0 )
						$log .= ' ' . $added_attach . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated_attach != 0 )
						$log .= ' ' . $updated_attach . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted_attach != 0 )
						$log .= ' ' . $deleted_attach . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
			}
		}			

		/* get labels */
		if ( 1 == $zndskhc_options['backup_elements']['labels'] ) {
			$all_labels = $wpdb->get_results( "SELECT `id`, `updated_at` FROM `" . $wpdb->prefix . "zndskhc_labels`", ARRAY_A );
			$product_url_stat = 'https://' . $zndskhc_options['subdomain'] . '.zendesk.com/api/v2/help_center/articles/labels.json';
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $product_url_stat );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Accept: application/json" ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERPWD, $zndskhc_options['user'] . ':' . $zndskhc_options['password'] );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			$json_resp = curl_exec( $ch );
			$array_resp = json_decode( $json_resp, true );
			curl_close( $ch );
			if ( !is_array( $array_resp ) && empty( $array_resp ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Labels backup', 'zendesk_hc' ) . ' - ' .  __( 'Undefined error has occurred while getting data from Zendesk API.', 'zendesk_hc' );
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} elseif ( isset( $array_resp['error'] ) ) {
				$log = __( 'ERROR', 'zendesk_hc' ) . ': ' . __( 'Labels backup', 'zendesk_hc' ) . ' - ' . $array_resp['error'] . ' (' . $array_resp['description'] . ')';
				zndskhc_log( $log );
				if ( $auto_mode && 1 == $zndskhc_options['emailing_fail_backup'] )
					zndskhc_send_mail( $log );
				return $log;
			} else {
				$added = $updated = $deleted = 0;
				foreach ( $array_resp["labels"] as $key => $value ) {
					$label = false;
					foreach ( $all_labels as $key_label => $value_label ) {
						if ( $value_label['id'] == $value['id'] ) {
							$label = $value_label;
							unset( $all_labels[ $key_label ] );
							break;
						}
					}
					
					if ( empty( $label ) ) {
						$wpdb->insert( $wpdb->prefix . "zndskhc_labels", 
							array( 'id' 			=> $value['id'], 
									'name'			=> $value['name'],
									'updated_at'	=> $value['updated_at'] ),
							array( '%s', '%s', '%s' ) );	
						$added++;
					} elseif ( strtotime( $label['updated_at'] ) < strtotime( $value['updated_at'] ) ) {
						$wpdb->update( $wpdb->prefix . "zndskhc_labels",
							array( 'updated_at'	=> $value['updated_at'],
									'name'			=> $value['name'] ), 
							array( 'id' => $value['id'] ),
							array(  '%s', '%s' ) ); 
						$updated++;
					}				
				}
				if ( ! empty( $all_labels ) ) {
					foreach ( $all_labels as $key_label => $value_label ) {
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "zndskhc_labels` WHERE `id` = %s", $value_label['id'] ) );
						$deleted++;
					}
				}
				if ( $added != 0 || $updated != 0 || $deleted != 0 ) {
					$log = __( 'Labels backup', 'zendesk_hc' ) . ':';
					if ( $added != 0 )
						$log .= ' ' . $added . ' ' . __( 'added', 'zendesk_hc' ) . ';';
					if ( $updated != 0 )
						$log .= ' ' . $updated . ' ' . __( 'updated', 'zendesk_hc' ) . ';';
					if ( $deleted != 0 )
						$log .= ' ' . $deleted . ' ' . __( 'deleted', 'zendesk_hc' ) . ';';
					zndskhc_log( $log );
				}
			}
		}		

		$zndskhc_options['last_synch'] = current_time( 'mysql' );
		update_option( 'zndskhc_options', $zndskhc_options );
		return true;
	}
}

/* attachment deleted/added/updated */
if ( ! function_exists( 'zndskhc_attachments_backup' ) ) {
	function zndskhc_attachments_backup( $status, $filename, $content_url = false ) {
		$upload_dir = wp_upload_dir();
		if ( ! $upload_dir["error"] ) {
			$cstm_folder = $upload_dir['basedir'] . '/zendesk_hc_attachments';
			if ( ! is_dir( $cstm_folder ) )
				wp_mkdir_p( $cstm_folder, 0755 );
		}
		$uploadfile			=	$cstm_folder . '/' . $filename;
		if ( 'deleted' == $status ) {
			if ( ! file_exists( $uploadfile ) )
				return true;
			if ( unlink( $uploadfile ) )
				return true;
		} else if ( 'added' == $status || 'updated' == $status ) {
			if ( $file_get_contents = file_get_contents( $content_url ) ) {
				if ( file_put_contents( $uploadfile, $file_get_contents ) )
					return true;
			}						
		}
		return false;
	}
}

/* Add log to the file */
if ( ! function_exists( 'zndskhc_log' ) ) {
	function zndskhc_log( $log ) {
		$log = date( 'd.m.Y h:i:s' ) . '	' . $log . "\n"; 
		@error_log( $log, 3, dirname( __FILE__ )  . "/backup.log" );
		@chmod( dirname( __FILE__ )  . "/backup.log", 0755 );	
	}
}

/* Get last logs */
if ( ! function_exists( 'zndskhc_get_logs' ) ) {
	function zndskhc_get_logs() {
		$content = file_get_contents( dirname( __FILE__ )  . "/backup.log" );
		if ( ! empty( $content ) ) {
			echo '<h4>' . __( 'Last log entries', 'zendesk_hc' ) . ':</h4>';
			$content_array = explode( "\n", $content );
			if ( is_array( $content_array ) ) {
				$content_reverse = array_reverse( $content_array );
				$i = 0;
				foreach ( $content_reverse as $key => $value ) {
					if ( $i < 12 ) {
						echo '<div';
						if ( false != strpos( $value, __( 'ERROR', 'zendesk_hc' ) ) )
							echo ' class="zndskhc_error_log"';
						echo '>' . $value . '</div>';
						$i++;
					} else
						break;
				}
			} else
				echo $content;
		}
	}
}

/* Get last logs */
if ( ! function_exists( 'zndskhc_send_mail' ) ) {
	function zndskhc_send_mail( $message ) {
		global $zndskhc_options;
		if ( empty( $zndskhc_options ) )
			$zndskhc_options = get_option( 'zndskhc_options' );

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		/* send message to user */
		$headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . $from_email . '>';
		$subject = __( "Zendesk HC Backup error on", 'zendesk_hc' ) . ' ' . esc_attr( get_bloginfo( 'name', 'display' ) );;
		wp_mail( $zndskhc_options['email'], $subject, $message, $headers );
	}
}

/* Add time for cron viev */
if ( ! function_exists( 'zndskhc_schedules' ) ) {
	function zndskhc_schedules( $schedules ) {
		global $zndskhc_options;
		if ( empty( $zndskhc_options ) )
			$zndskhc_options = get_option( 'zndskhc_options' );
		$schedules_hours = ( '' != $zndskhc_options['time'] ) ? $zndskhc_options['time'] : 48;

	    $schedules['schedules_hours'] = array( 'interval' => $schedules_hours*60*60, 'display' => 'Every ' . $schedules_hours . ' hours' );
	    return $schedules;
	}
}

/* Positioning in the page. End. */
if ( !function_exists( 'zndskhc_action_links' ) ) {
	function zndskhc_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename( __FILE__ );

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=zendesk_hc.php&tab=settings">' . __( 'Settings', 'zendesk_hc' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
} /* End function zndskhc_action_links */

/* Function are using to create link 'settings' on admin page. */
if ( !function_exists( 'zndskhc_links' ) ) {
	function zndskhc_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[]	=	'<a href="admin.php?page=zendesk_hc.php&tab=settings">' . __( 'Settings','zendesk_hc' ) . '</a>';
			$links[]	=	'<a href="http://wordpress.org/plugins/zendesk-help-center/faq/" target="_blank">' . __( 'FAQ','zendesk_hc' ) . '</a>';
			$links[]	=	'<a href="http://support.bestwebsoft.com">' . __( 'Support','zendesk_hc' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'zndskhc_admin_js' ) ) {
	function zndskhc_admin_js() {
		if ( isset( $_REQUEST['page'] ) && 'zendesk_hc.php' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'zndskhc_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'zndskhc_script', plugins_url( 'js/script.js', __FILE__ ) );
		}
	}
}

/* Function for delete options from table `wp_options` */
if ( ! function_exists( 'delete_zndskhc_settings' ) ) {
	function delete_zndskhc_settings() {
		global $wpdb;
		delete_option( 'zndskhc_options' );

		/* delete plugin`s tables */
		$wpdb->query( "DROP TABLE IF EXISTS
			`" . $wpdb->prefix . "zndskhc_categories`, 
			`" . $wpdb->prefix . "zndskhc_sections`,
			`" . $wpdb->prefix . "zndskhc_articles`, 
			`" . $wpdb->prefix . "zndskhc_labels`, 
			`" . $wpdb->prefix . "zndskhc_comments`, 
			`" . $wpdb->prefix . "zndskhc_attachments`;" 
		);
		/* delete plugin`s upload_dir */
		$upload_dir = wp_upload_dir();
		if ( ! $upload_dir["error"] ) {
			$cstm_folder = $upload_dir['basedir'] . '/zendesk_hc_attachments';
			if ( is_dir( $cstm_folder ) )
				rmdir( $cstm_folder );
		}
		/* Delete hook if it exist */
		wp_clear_scheduled_hook( 'auto_synchronize_zendesk_hc' );
	}
}

register_activation_hook( __FILE__, 'zndskhc_db_table' );

add_action( 'admin_menu', 'add_zndskhc_admin_menu' );
add_action( 'init', 'zndskhc_init' );
add_action( 'admin_init', 'zndskhc_admin_init' );
add_action( 'admin_enqueue_scripts', 'zndskhc_admin_js' );

/* Add time for cron viev */
add_filter( 'cron_schedules', 'zndskhc_schedules' );
/* Function that update all plugins and WP core in auto mode. */
add_action( 'auto_synchronize_zendesk_hc', 'zndskhc_synchronize' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'zndskhc_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'zndskhc_links', 10, 2 );

register_uninstall_hook( __FILE__, 'delete_zndskhc_settings' );