<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://github.com/faiyazalam
 * @since      1.4.1
 *
 * @package    User_Login_History
 * @subpackage User_Login_History/admin
 * @author     Er Faiyaz Alam
 */
?>
<?php
class User_Login_History_Admin {
    /**
     * The name of this plugin.
     *
     * @since    1.4.1
     * @access   private
     * @var      string    $name    The name of this plugin.
     */
    private $name;

    /**
     * The version of this plugin.
     *
     * @since    1.4.1
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The option prefix of this plugin.
     *
     * @since    1.4.1
     * @access   private
     * @var      string    $version   To be used with options like user meta key, key for option table etc. for the plugin.
     */
    private $option_prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.1
     * @var      string    $name       The name of this plugin.
     * @var      string    $version    The version of this plugin.
     * @var      string    $option_prefix    The option prefix of this plugin.
     */
    public function __construct($name, $version, $option_prefix) {
        $this->name = $name;
        $this->version = $version;
        $this->option_prefix = $option_prefix;
    }

    /**
     * Used to update db after plugin update.
     *
     * @since	1.4.1
     */
    private function after_plugin_update() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . ULH_TABLE_NAME;

        $sql = "CREATE TABLE $table (
id int(11) NOT NULL AUTO_INCREMENT,
user_id int(11) ,
`username` varchar(100) NOT NULL,
`time_login` datetime NOT NULL,
`time_logout` datetime NOT NULL,
`time_last_seen` datetime NOT NULL,
`ip_address` varchar(20) NOT NULL,
`browser` varchar(100) NOT NULL,
`operating_system` varchar(100) NOT NULL,
`country_name` varchar(100) NOT NULL,
`country_code` varchar(20) NOT NULL	,
`timezone` varchar(20) NOT NULL	,
`old_role` varchar(200) NOT NULL	, 
PRIMARY KEY (`id`)
) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.4.1
     */
    public function enqueue_styles() {
        global $pagenow;

        if ('options-general.php' == $pagenow) {
            wp_enqueue_style($this->name . '-admin.css', plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
        }

        if ('admin.php' == $pagenow && isset($_GET['page']) && 'user-login-history' == $_GET['page']) {
            wp_enqueue_style($this->name . '-admin-jquery-ui.min.css', plugin_dir_url(__FILE__) . 'css/jquery-ui.min.css', array(), $this->version, 'all');
            wp_enqueue_style($this->name . '-admin.css', plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
            wp_enqueue_script($this->name . '-admin-jquery-ui.min.js', plugin_dir_url(__FILE__) . 'js/jquery-ui.min.js', array(), $this->version, 'all');
            wp_enqueue_script($this->name . '-admin-custom.js', plugin_dir_url(__FILE__) . 'js/custom.js', array(), $this->version, 'all');
             wp_localize_script( $this->name . '-admin-custom.js', 'ulh_admin_custom_object',
          array( 'delete_confirm_message' => __('Are your sure?', 'user-login-history') ) );
        }
    }

    /**
     * Add admin notices
     *
     * @since	1.4.1
     */
    public function add_admin_notice($message) {
        $notices = get_transient('user_login_history_admin_notice_transient');
        if ($notices === false) {
            $new_notices[] = $message;
            set_transient('user_login_history_admin_notice_transient', $new_notices, 120);
        } else {
            $notices[] = $message;
            set_transient('user_login_history_admin_notice_transient', $notices, 120);
        }
    }

    /**
     * Show admin notices
     * @since	1.4.1
     */
    public function show_admin_notice() {
        $notices = get_transient('user_login_history_admin_notice_transient');

        if ($notices !== false) {
            foreach ($notices as $notice) {
                echo '<div class="update-nag"><p>' . $notice . '</p></div>';
            }
            delete_transient('user_login_history_admin_notice_transient');
        }
    }

    /**
     * Check plugin version on update
     *
     * @since	1.4.1
     */
    public function check_update_version() {
        // Current version
        $current_version = get_option($this->option_prefix . 'version');
        //Their version is older
        if ($current_version && version_compare($current_version, $this->version, '<')) {
            //Older than 1.4.1
            if (version_compare($current_version, '1.4.1', '<')) {
                update_option(ULH_PLUGIN_OPTION_PREFIX . 'frontend_fields', array('ip_address' => 1, 'old_role' => 1, 'country' => 1, 'login' => 1, 'logout' => 1));
                update_option(ULH_PLUGIN_OPTION_PREFIX . 'frontend_limit', '20');
                $message = sprintf(wp_kses(__('User Login History has had a change in the settings structure. Please head over to the <a href="%s">Backend Options</a> to make sure everything is correct.', 'user-login-history'), array('a' => array('href' => array()))), esc_url(admin_url() . 'options-general.php?page=user-login-history-settings&tab=backend'));
                $this->add_admin_notice($message);
            }
            //finally update DB for plugin table and current version
            $this->after_plugin_update();
            update_option($this->option_prefix . 'version', $this->version);
        }
    }

    /**
     * Register the settings page
     *
     * @since    1.4.1
     */
    public function add_admin_menu() {
       add_options_page('User Login History Settings', __('User Login History', 'user-login-history'), 'manage_options', 'user-login-history-settings', array($this, 'create_admin_interface'));
    }

    /**
     * Callback function for the admin settings page.
     *
     * @since    1.4.1
     */
    public function create_admin_interface() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/admin-display.php';
    }

    /**
     * Just to start ob.
     *
     * @since    1.4.1
     */
    public function do_ob_start() {
        ob_start();
    }

    /**
     * Creates our settings sections with fields etc.
     *
     * @since    1.4.1
     */
    public function settings_api_init() {
        /**
         * Sections functions
         */
        //Frontend section
        add_settings_section(
                'user_login_history_frontend_fields_settings_section', __('Frontend Options', 'user-login-history'), array($this, 'frontend_fields_setting_section_callback_function'), 'frontend-fields-user-login-history'
        );
        /**
         * Fields functions
         */
        // frontend columns
        add_settings_field(
                $this->option_prefix . 'frontend_fields', '<span class="btf-tooltip" title="' . __("Select the columns that you want to display on frontend listing table.", 'user-login-history') . '">?</span>' . __('Columns:', 'user-login-history'), array($this, 'frontend_fields_setting_callback_function'), 'frontend-fields-user-login-history', 'user_login_history_frontend_fields_settings_section'
        );
        // frontend limit
        add_settings_field(
                $this->option_prefix . 'frontend_limit', '<span class="btf-tooltip" title="' . __("Enter limit (0-100) to show records per page on frontend listing table.", 'user-login-history') . '">?</span>' . __('Pagination:', 'user-login-history'), array($this, 'frontend_limit_setting_callback_function'), 'frontend-fields-user-login-history', 'user_login_history_frontend_fields_settings_section'
        );
        //register all setting fields
        register_setting('frontend-fields-user-login-history', $this->option_prefix . 'frontend_fields');
        register_setting('frontend-fields-user-login-history', $this->option_prefix . 'frontend_limit');
    }

    /**
     * Callback functions for settings
     */
    // Frontend setting section callback
    public function frontend_fields_setting_section_callback_function() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/frontend/fields-section-display.php';
    }

  public  function frontend_fields_setting_callback_function() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/frontend/fields-settings-display.php';
    }

   public function frontend_limit_setting_callback_function() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/frontend/limit-settings-display.php';
    }

    /**
     * CSV Export Feature
     *   @since    1.4.1
     */
    private function export_to_CSV() {
        global $wpdb;
        $get_values = array();
        $table = $wpdb->prefix . ULH_TABLE_NAME;
        $table_usermeta = $wpdb->prefix . "usermeta";
        $table_users = $wpdb->prefix . "users";

        $sql = "select "
                . "DISTINCT(FaUserLogin.id) as id, "
                . "User.user_login,"
                . "FaUserLogin.country_name,"
                . "FaUserLogin.country_code, "
                . "FaUserLogin.ip_address, "
                . "FaUserLogin.browser, "
                . "FaUserLogin.operating_system, "
                . "FaUserLogin.time_login, "
                . "FaUserLogin.time_logout, "
                . "FaUserLogin.time_last_seen, "
                . "FaUserLogin.timezone, "
                . "FaUserLogin.old_role, "
                . "UserMeta.* "
                . "FROM $table  AS FaUserLogin  "
                . " INNER JOIN $table_users as User ON User.ID = FaUserLogin.user_id"
                . " INNER JOIN $table_usermeta AS UserMeta ON UserMeta.user_id=FaUserLogin.user_id where UserMeta.meta_key = '{$wpdb->prefix}capabilities' AND  1 ";

        $User_Login_History_List_Table = new User_Login_History_List_Table();
        $where_query = $User_Login_History_List_Table->prepare_where_query();

        if ($where_query) {
            $sql .= $where_query['sql_query'];
            $get_values = $where_query['values'];
        }
        $sql .= ' ORDER BY id DESC';
        $data = $wpdb->get_results($wpdb->prepare($sql, $get_values), 'ARRAY_A');
         
       
        //date string to suffix the file nanme: month - day - year - hour - minute
        $suffix = date('n-j-y_H-i');
        // send response headers to the browser
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=login_log_' . $suffix . '.csv');
        
         if (!$data) {
             _e('No Records Found!','user-login-history');
             exit;
        }
        
        $fp = fopen('php://output', 'w');
$unknown = 'Unknown';
$new_fields = array('old_role', 'timezone');
        $i = 0;
      
        foreach ($data as $row) {
            $row['current_role'] = implode('', array_keys(unserialize($row['meta_value'])));
            unset($row['meta_value']);
            unset($row['meta_key']);
            unset($row['umeta_id']);
            //output header row
            if (0 == $i) {
                fputcsv($fp, array_keys($row));
            }      
           
            foreach ($new_fields as $new_field) {
                if(isset($row[$new_field]) && "" == $row[$new_field])
            {
                    $row[$new_field] = $unknown;
            } 
            }
            
            fputcsv($fp, $row);
            $i++;
        }
        fclose($fp);
        die();
    }

    public function init_csv_export() {
        //Check if download was initiated
        $download = (isset($_GET['export-user-login-history']) && "csv" == $_GET['export-user-login-history']) && current_user_can('administrator') ? TRUE : FALSE;
        if ($download) {
//check_admin_referer( 'csv_nonce' );
            $where = ( isset($_GET['where']) && '' != $_GET['where'] ) ? $_GET['where'] : false;
            $where = maybe_unserialize(stripcslashes($where));
            if (is_array($where) && !empty($where)) {
                foreach ($where as $k => $v) {
                    $_GET[$k] = esc_attr($v);
                }
            }
            $this->export_to_CSV();
        }
    }

     /**
     * Delete all records from the table.
     * @since    1.4.1
     * @access   public
     */
    public function delete_all_records() {
        if (isset($_GET['delete_all_user_login_history']) && current_user_can('administrator')) {
            global $wpdb;
            $table = $wpdb->prefix . ULH_TABLE_NAME;
            $wpdb->query("TRUNCATE TABLE $table");
            $this->add_admin_notice('The record(s) has been deleted.');
            wp_redirect(admin_url() . "admin.php?page=user-login-history");
            exit;
        }
    }

}
