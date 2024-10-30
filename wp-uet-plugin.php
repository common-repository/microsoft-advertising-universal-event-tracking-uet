<?php
// Copyright (c) Microsoft Corporation.
// Licensed under the MIT license.

/**
 * Plugin Name: Microsoft Advertising Universal Event Tracking (UET)
 * Plugin URI: https://ads.microsoft.com/
 * Description: The official plugin for setting up Microsoft Advertising UET.
 * Version: 1.0.7
 * Author: Microsoft Corporation
 * Author URI: https://www.microsoft.com/
 * License: GPLv2 or later  
 */

 // NOTE: If you update 'Version' above, update the 'tm' parameter in the script.

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly   

 //
 // Register actions.
 //
add_action( 'wp_head', 'UetPageLoadEvent' ); // To inject UET into public pages.
add_action( 'admin_menu', 'UetAddSettingsPage' ); // To add a settings page on the admin menu.
add_action( 'admin_init', 'UetRegisterSettings' ); // To support the actual UET settings.
add_action( 'admin_notices', 'UetShowAdminNotice' ); // To show an admin banner when UET is not setup correctly.
add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'UetAddSettingsLinkOnPluginDashboard' ); // To add a link to the settings page from the plugin dashboard

register_activation_hook( __FILE__, function() {
  add_option('MsUet_Activated_Plugin','microsoft-advertising-universal-event-tracking-uet');
});

function UetIsTagAvailable() {
    $options = get_option('UetTagSettings');
	if(!empty($options['uet_tag_id']))
	{
		if(ctype_digit($options['uet_tag_id']))
		{
			return true;
		}
	}
    return false;
}

function UserSettingForEnableAutoSpaTracking() {
    $options = get_option('UetTagSettings');
	if(!empty($options['enable_spa_tracking']))
	{
		$enableSpa_bool = filter_var($options['enable_spa_tracking'], FILTER_VALIDATE_BOOLEAN);
		if($enableSpa_bool)
		{
			return 'true';
		}
	}
    return 'false';
}

function UetPageLoadEvent() {  
    if (!UetIsTagAvailable()) return null;  
  
    $options = get_option('UetTagSettings');  
    $uet_tag_id = $options['uet_tag_id'];  
    if (!ctype_digit($uet_tag_id)) {  
        $uet_tag_id = '';  
    }  
  
    $uet_tag_data = array(  
        'uet_tag_id' => esc_attr($uet_tag_id),  
        'enableAutoSpaTracking' => esc_attr(UserSettingForEnableAutoSpaTracking())  
    );  
  
    wp_register_script('uet-tag-script', plugins_url('/js/uet-tag.js', __FILE__), array(), "1.0.0", false);  
    wp_localize_script('uet-tag-script', 'uet_tag_data', $uet_tag_data);  
    wp_enqueue_script('uet-tag-script');  
  
    return null;  
} 
add_action('wp_enqueue_scripts', 'UetPageLoadEvent');  

function UetAddSettingsPage() {
    add_options_page('Microsoft Advertising UET settings', 'UET tag', 'manage_options', 'uet_tag_settings_page', 'UetRenderSettingsPage');
}

function UetRenderSettingsPage() {
    ?>
	<div style="
	  margin-top: 32px;
	  margin-left: 12px;
	  padding: 0px;
	  ">
	<span style="font-family:Segoe UI;font-size:20px;font-weight:600;color: #323130;">Microsoft Advertising UET Settings</span>
    <form action="options.php" method="post" style="margin-top: 40px;">
        <?php
        settings_fields( 'UetTagSettings' );
        do_settings_sections( 'uet_tag_settings_page' ); ?>
		<input name="submit" class="button button-primary" type="submit" value="<?php echo esc_attr( 'Save' ); ?>" />  
    </form>
<?php
}

function sanitize_uet_tag_settings( $input ) {  
    $new_input = array();  
  
	//should be number 
    if ( isset( $input['uet_tag_id'] ) ) {  
        $new_input['uet_tag_id'] = absint( $input['uet_tag_id'] );  
    }  
  
	//should be boolean value
    if ( isset( $input['enable_spa_tracking'] ) ) {  
        $new_input['enable_spa_tracking'] = filter_var( $input['enable_spa_tracking'], FILTER_VALIDATE_BOOLEAN);  
    }  
  
    return $new_input;  
}  


function UetRegisterSettings() {
	register_setting('UetTagSettings', 'UetTagSettings', 'sanitize_uet_tag_settings' );  
    add_settings_section('uet_general_settings_section', '', 'UetRenderGeneralSettingsSectionHeader', 'uet_tag_settings_page');
    add_settings_field('uet_tag_id', 'UET Tag ID', 'UetEchoTagId', 'uet_tag_settings_page', 'uet_general_settings_section');
	add_settings_field('enable_spa_tracking', "Enable SPA Tracking", "UetEchoEnableSpa", 'uet_tag_settings_page', 'uet_general_settings_section');
    
	if(is_admin() && get_option('MsUet_Activated_Plugin') == 'microsoft-advertising-universal-event-tracking-uet') {
		delete_option('MsUet_Activated_Plugin');
		
		$options = get_option('UetTagSettings');
		include 'tagid.php';
		if (empty($options['uet_tag_id']) && !empty($tagid)) {
			if (ctype_digit($tagid)) {
				$options['uet_tag_id'] = $tagid;
				update_option('UetTagSettings', $options);
			}
		}

		if(empty($options['enable_spa_tracking'])&& !empty($enableSpaTracking)){
			if (filter_var($enableSpaTracking, FILTER_VALIDATE_BOOLEAN)){
					$options['enable_spa_tracking'] = $enableSpaTracking;
			}
			else
			{
					$options['enable_spa_tracking'] = 'false';
			}
			update_option('UetTagSettings', $options);
		}
	}
}

function UetRenderGeneralSettingsSectionHeader() {
?>
	<div><span style="font-family: 'Segoe UI';font-size: 14px;height: 100%;color: #323130;">Please configure the UET tag ID from your Microsoft Advertising Account. After you login to your Microsoft Advertising account, you can find the UET tag ID by going to <span style="font-weight:600;">Tools &gt; Conversion Tracking &gt; UET Tag.</span> Learn more about UET Tag <a href="https://go.microsoft.com/fwlink/?linkid=2155938" style="color: #0078D4;">here</a>.</span></div>
<?php
}

function UetEchoTagId() {
    $options = get_option('UetTagSettings');
    $uet_tag_id = '';
    if(isset($options['uet_tag_id']) && ctype_digit($options['uet_tag_id'])){
        $uet_tag_id = $options['uet_tag_id'];
		echo "<input id='uet_tag_id' name='UetTagSettings[uet_tag_id]' type='text' value='" .esc_attr($uet_tag_id) ."' />";
    }
	else
	{
		echo "<input id='uet_tag_id' name='UetTagSettings[uet_tag_id]' type='text' value='' />";
	}
}

function UetEchoEnableSpa() {
    $options = get_option('UetTagSettings');
    $enableSpa = '';
    if(isset($options['enable_spa_tracking']) && filter_var($options['enable_spa_tracking'], FILTER_VALIDATE_BOOLEAN)){
		echo "<input id='enable_spa_tracking' name='UetTagSettings[enable_spa_tracking]' type='text' value='" .esc_attr('true') ."' />";
    }
	else
	{
		echo "<input id='enable_spa_tracking' name='UetTagSettings[enable_spa_tracking]' type='text' value='" .esc_attr('false') ."' />";
	}
}

function UetShowAdminNotice() {
	if (UetIsTagAvailable()) return;
	global $pagenow;
    if ( $pagenow != 'index.php' && $pagenow != 'plugins.php') return;
	?>
	<div class="notice notice-warning is-dismissible"><p><span style="font-weight: 600;">Set up Microsoft Advertising Universal Event Tracking</span> Please complete UET tag setup by 
	<a href='<?php echo esc_url(admin_url('options-general.php?page=uet_tag_settings_page'))?>'>configuring the UET tag ID</a>.</p></div>
	<?php
}

function UetAddSettingsLinkOnPluginDashboard( $links ) {
	$uet_settings_link = '<a href="' .  
       admin_url( 'options-general.php?page=uet_tag_settings_page' ) .  
       '">Settings</a>';  
	array_unshift($links, $uet_settings_link);
	return $links;
}

?>