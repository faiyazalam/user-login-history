<?php
/*
Plugin Name: User Login History
Plugin URI: https://github.com/faiyazalam/wp_login_history_plugin
Description: A simple WordPress plugin for user login history.
Version: 1.0
Text Domain: fauserloginhistory
Author: Faiyaz Alam
Author URI: https://github.com/faiyazalam/
*/

define('AI_PDIR_PATH', plugin_dir_path(__FILE__ ));
add_action('plugins_loaded', 'fa_userloginhistoryt_init');

/** Start Upgrade Notice **/
global $pagenow;
if ( 'plugins.php' === $pagenow )
{
    // Better update message
    $file   = basename( __FILE__ );
    $folder = basename( dirname( __FILE__ ) );
    $hook = "in_plugin_update_message-{$folder}/{$file}";
    add_action( $hook, 'update_notification_message', 20, 2 );
}
function update_notification_message( $plugin_data, $r )
{
  $upgradenotice = "";
	$output = "<div style='color:#EEC2C1;font-weight: normal;background: #C92727;padding: 10px;border: 1px solid #eed3d7;border-radius: 4px;'><strong style='color:rgb(253, 230, 61)'>Update Notice : </strong> ".$upgradenotice."</div>";

    return print $output;
}
/** End Upgrade Notice **/

/* Activate Hook Plugin */
register_activation_hook(__FILE__,'fa_add_user_logins_table');

# Load the language files
function fa_userloginhistoryt_init(){
	load_plugin_textdomain( 'fauserloginhistory', false, plugin_basename( dirname( __FILE__ )  . '/languages/' ));
}

/**
 * Adds this plugin to the list of available contact us forms on BPMContext - Intranet Plus
   */
add_action( 'admin_init', 'fa_bpm_options_setup' );
function fa_bpm_options_setup(){

    $plugins_array['name'] = __('User Login History', 'fauserloginhistory');
    $plugins_array['url'] = 'https://wordpress.org/plugins/user-login-history/';
    $plugins_array['slug'] = 'user-login-history';
    $plugins_array['plugin_file'] = 'fa-user-login-history.php';
    $plugins_array['shortcode'] = 'fa_userloginhistory';

    do_action('bpmcontext_add_to_allowed_plugins', $plugins_array);

}

add_action('admin_notices', 'fa_bpm_admin_notice');
function fa_bpm_admin_notice() {
    global $current_user ;
    $user_id = $current_user->ID;
    if ( ! get_user_meta($user_id, 'fa_bpm_ignore_notice') ) {
    echo '<div class="updated"><p>';
    printf(__('User Login History is a Free wp plugin.'));
    echo '</p></div>';
    }
}

add_action('admin_init', 'fa_bpm_nag_ignore');
function fa_bpm_nag_ignore() {
    global $current_user;
    $user_id = $current_user->ID;
    if ( isset($_GET['fa_bpm_nag_ignore']) && '0' == $_GET['fa_bpm_nag_ignore'] ) {
    add_user_meta($user_id, 'fa_bpm_ignore_notice', 'true', true);
    }
}
/**
 * end of BPMContext Intranet Plus setup modifications
 */

add_action('admin_init', 'fa_register_fields' );
function fa_register_fields(){
	
	include_once( get_home_path().'/wp-load.php' );
	register_setting( 'fa-fields', 'fa_is_show_country' );
	

}

/*Uninstall Hook Plugin */
register_deactivation_hook(__FILE__,'fa_userloginhistory_uninstall');		

function fa_userloginhistory_uninstall(){ 
	delete_option('fa_is_show_country');
	global $wpdb;	
$fa_user_logins_table = $wpdb->prefix . "fa_user_logins";  
$sql = "DROP TABLE IF EXISTS .$fa_user_logins_table";
$wpdb->query("DROP TABLE IF EXISTS ".$fa_user_logins_table);
       
}

add_shortcode('fa_userloginhistory', 'fa_shortcode');
function fa_shortcode(){
	include_once('include/fa-userloginhistory-template.php');
}

/*Settings in Admin Menu Item*/
add_action('admin_menu','fa_userloginhistory_setting');

/*
* Setup Admin menu item
*/
function fa_userloginhistory_setting(){
	   add_menu_page(__('FA User Login History','fauserloginhistory'),__('FA User Login History','fauserloginhistory'),'manage_options','fa_userloginhistory','fa_userloginhistory_settings','','79.5');
	   global $page_options;
	   $page_options = add_submenu_page('fa_userloginhistory', __('User List','fauserloginhistory_list'), __('User List','fauserloginhistory'),'manage_options', 'fa_user_lists', 'fa_user_list');
}

/*
* Admin menu icons
*/
add_action( 'admin_head', 'fa_cf_add_menu_icons_styles' );
function fa_cf_add_menu_icons_styles() { ?>
	<style type="text/css" media="screen">
		#adminmenu .toplevel_page_fa_userloginhistory div.wp-menu-image:before {
			content: '\f314';
		}
	</style>
<?php }

add_action('admin_enqueue_scripts', 'fa_load_admin_scripts');
function fa_load_admin_scripts($hook) {
	global $page_options;
	if( $hook != $page_options )
		return;
}

function fa_add_user_logins_table(){	
	global $wpdb;
	
	$fa_user_logins_table = $wpdb->prefix . "fa_user_logins";			
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	  
	
	$wpdb->query("DROP TABLE IF EXISTS ".$fa_user_logins_table);

	$fa_sql_contact = "CREATE TABLE IF NOT EXISTS $fa_user_logins_table (
   id int(11) NOT NULL AUTO_INCREMENT,
   user_id int(11) ,
  `time_login` datetime NOT NULL,
  `time_logout` datetime NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `browser` varchar(100) NOT NULL,
  `operating_system` varchar(100) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `country_code` varchar(20) NOT NULL	,		  					  
   PRIMARY KEY (`id`)
	) ";
        
      

    dbDelta($fa_sql_contact);
}

function fa_userloginhistory_settings(){
	include AI_PDIR_PATH."/include/fa_settings.php";
}

function fa_user_list(){
	include AI_PDIR_PATH."/include/fa_user_list.php";
}



function fa_scripts(){
	if(isset($_GET['page']) && preg_match('/^fa_/', @$_GET['page']) ){
		wp_enqueue_script( 'fa_script', plugins_url( '/js/fa_script.js' , __FILE__ ) );		
		wp_enqueue_script( 'fa_script_table', plugins_url('/js/jquery.dataTables.js' , __FILE__), array( 'jquery' ) );
		wp_enqueue_style('wp-datatable',  plugins_url('/user-login-history/css/data_table.css'));
	}
}  
add_action( 'admin_enqueue_scripts', 'fa_scripts' );

if(!is_admin()){
	wp_localize_script( 'my-ajax-request', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );	
}


function fa_save_user_login()
{

   
  	global $wpdb,$table_prefix, $current_user ;
	$fa_user_logins_table = $table_prefix . 'fa_user_logins';
	$ipAddress=getVisitorIpAddress();
	$currentDate = fa_getCurrentDateTime();
	$Unknown = "Unknown";
	$userId = $current_user->ID;
        $timeLogin = $currentDate;
        $browser = fa_getVisitorBrowser();
        $operatingSystem = fa_getVisitorOperatingSystem();
       
        $visitorCountryInfo =  fa_getVisitorCountryInfo();
        $countryName = $visitorCountryInfo->geoplugin_countryName?$visitorCountryInfo->geoplugin_countryName:$Unknown;
        $countryCode = $visitorCountryInfo->geoplugin_countryCode?$visitorCountryInfo->geoplugin_countryCode:$Unknown;

$sql=" insert into $fa_user_logins_table(user_id,time_login,ip_address,browser,operating_system,country_name, country_code) values('$userId','$timeLogin','$ipAddress','$browser','$operatingSystem','$countryName', '$countryCode'); ";

return  $wpdb->query($sql);
         



	

}

/**
 * Perform automatic login.
 */
function fa_custom_login() {
 if(is_user_logged_in())
    {
        return;
    }
    
    $user = wp_signon();
 
    if ( is_wp_error( $user ) ) {
        echo $user->get_error_message();
        return;
    }
	wp_set_current_user($user->ID); //update the global user variables
       return fa_save_user_login();
         
        
   
}
 
// Run before the headers and cookies are sent.
add_action( 'after_setup_theme', 'fa_custom_login');
function getVisitorIpAddress()
{
   
	$ipaddress = $_SERVER['REMOTE_ADDR'];
	
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	
	return  $ipaddress ;
}

function fa_debugVar($param) {
echo '<pre>'.print_r($param, TRUE).'</pre>';
}


function fa_getCurrentDateTime() {
    return date('Y-m-d h:i:s');
}
function fa_getVisitorBrowser()
{

$userAgent= $_SERVER['HTTP_USER_AGENT'];
		$browsers = array(
		'Opera' => 'Opera',
		'Firefox'=> '(Firebird)|(Firefox)', 
		'Galeon' => 'Galeon',
		'Chrome'=>'Chrome',
		'MyIE'=>'MyIE',
		'Lynx' => 'Lynx',
		'Netscape' => '(Mozilla/4\.75)|(Netscape6)|(Mozilla/4\.08)|(Mozilla/4\.5)|(Mozilla/4\.6)|(Mozilla/4\.79)',
		'Konqueror'=>'Konqueror',
		'SearchBot' => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)',
		'Internet Explorer 8' => '(MSIE 8\.[0-9]+)',
		'Internet Explorer 9' => '(MSIE 9\.[0-9]+)',
        'Internet Explorer 7' => '(MSIE 7\.[0-9]+)',
		'Internet Explorer 6' => '(MSIE 6\.[0-9]+)',
		'Internet Explorer 5' => '(MSIE 5\.[0-9]+)',
		'Internet Explorer 4' => '(MSIE 4\.[0-9]+)',
	);

	foreach($browsers as $browser=>$pattern) { 

		if(eregi($pattern, $userAgent)) {
			return $browser; 
		}
	}
	return 'Unknown'; 

}
function fa_getVisitorCountryInfo($option = FALSE)
{
    /*
     {
  "geoplugin_request":"xxxx.xxxx.xxxx.xxxx",
  "geoplugin_status":206,
  "geoplugin_credit":"Some of the returned data includes GeoLite data created by MaxMind, available from <a href='http:\/\/www.maxmind.com'>http:\/\/www.maxmind.com<\/a>.",
  "geoplugin_city":"",
  "geoplugin_region":"",
  "geoplugin_areaCode":"0",
  "geoplugin_dmaCode":"0",
  "geoplugin_countryCode":"IN",
  "geoplugin_countryName":"India",
  "geoplugin_continentCode":"AS",
  "geoplugin_latitude":"20",
  "geoplugin_longitude":"77",
  "geoplugin_regionCode":"",
  "geoplugin_regionName":"",
  "geoplugin_currencyCode":"INR",
  "geoplugin_currencySymbol":"&#8360;",
  "geoplugin_currencySymbol_UTF8":"\u20a8",
  "geoplugin_currencyConverter":66.6555
}
     */
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];
  
    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));

    
    return $ip_data?$ip_data: FALSE;
   
}

function fa_getVisitorOperatingSystem()
{
$userAgent= $_SERVER['HTTP_USER_AGENT'];
		$oses = array (
		'iPhone' => '(iPhone)',
		'Windows 3.11' => 'Win16',
		'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)', 
		'Windows 98' => '(Windows 98)|(Win98)',
		'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
		'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
		'Windows 2003' => '(Windows NT 5.2)',
		'Windows Vista' => '(Windows NT 6.0)|(Windows Vista)',
		'Windows 7' => '(Windows NT 6.1)|(Windows 7)',
		'Windows 8' => '(Windows NT 6.2)|(Windows 8)',
		'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
		'Windows ME' => 'Windows ME',
		'Open BSD'=>'OpenBSD',
		'Sun OS'=>'SunOS',
		'Linux'=>'(Linux)|(X11)',
		'Safari' => '(Safari)',
		'Macintosh'=>'(Mac_PowerPC)|(Macintosh)',
		'QNX'=>'QNX',
		'BeOS'=>'BeOS',
		'OS/2'=>'OS/2',
		'Search Bot'=>'(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)'
	);

	foreach($oses as $os=>$pattern){ 

		if(eregi($pattern, $userAgent)) { 
			return $os; 
		}
	}
	return 'Unknown';
}
add_action( 'wp_logout', 'fa_save_user_logout' );
function fa_save_user_logout()
{
	global $wpdb,$table_prefix,$current_user;
    $userId=$current_user->ID;
    $emptyTime = '0000-00-00 00:00:00';
    $timeLogout = fa_getCurrentDateTime();
    
	$fa_user_logins_table = $table_prefix . 'fa_user_logins';
	$sql=" select id from $fa_user_logins_table where user_id='$userId' and time_logout='$emptyTime' order by id desc limit 1 ; ";
	$results = $wpdb->get_results($sql);
        
	$result=$results[0];
	$id = $result->id;	
      
	if($id)	
	{
		$sql=" update $fa_user_logins_table set time_logout='$timeLogout' where id=$id ; ";
                
		$wpdb->query($sql);
	}
	
        
      
} 

 function fa_pagination($options = array()) {
            
      global $wpdb;
                    $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
        $limit = isset($options['limit'])?$options['limit']:10;
$offset = ( $pagenum - 1 ) * $limit;
$total = $wpdb->get_var($options['countQuery']);
$num_of_pages = ceil( $total / $limit );
$sqlQuery = $options['sqlQuery']. " LIMIT  $offset, $limit";
$rows = $wpdb->get_results($sqlQuery);
       
$page_links = paginate_links( array(
    'base' => add_query_arg( 'pagenum', '%#%' ),
    'format' => '',
    'prev_text' => __( '&laquo;', 'text-domain' ),
    'next_text' => __( '&raquo;', 'text-domain' ),
    'total' => $num_of_pages,
    'current' => $pagenum
) );
return array('page_links'=>$page_links, 'rows' => $rows) ;
        }
?>