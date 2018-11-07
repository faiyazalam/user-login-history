<?php

namespace User_Login_History\Inc\Core;

use User_Login_History as NS;
use User_Login_History\Inc\Admin\Admin;
use User_Login_History\Inc\Admin\Admin_Notice;
use User_Login_History\Inc\Admin\User_Profile;
use User_Login_History\Inc\Admin\Settings as AdminSettings;
use User_Login_History\Inc\Admin\Network_Admin_Settings;
use User_Login_History\Inc\Common\Login_Tracker;
use User_Login_History\Inc\Frontend as Frontend;
use User_Login_History\Inc\Admin\Network_Blog_Manager;
use User_Login_History\Inc\Admin\Settings_Api;
use User_Login_History\Inc\Admin\Login_List_Csv;

/**
 * The core plugin class.
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @link       http://userloginhistory.com
 * @since      1.0.0
 *
 * @author     Er Faiyaz Alam
 */
class Init {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_base_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_basename;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The text domain of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $plugin_text_domain;

    /**
     * Initialize and define the core functionality of the plugin.
     */
    public function __construct() {

        $this->plugin_name = NS\USER_LOGIN_HISTORY;
        $this->version = NS\PLUGIN_VERSION;
        $this->plugin_basename = NS\PLUGIN_BASENAME;
        $this->plugin_text_domain = NS\PLUGIN_TEXT_DOMAIN;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Loads the following required dependencies for this plugin.
     *
     * - Loader - Orchestrates the hooks of the plugin.
     * - Internationalization_I18n - Defines internationalization functionality.
     * - Admin - Defines all hooks for the admin area.
     * - Frontend - Defines all hooks for the public side of the site.
     *
     * @access    private
     */
    private function load_dependencies() {
        $this->loader = new Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Internationalization_I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @access    private
     */
    private function set_locale() {

        $plugin_i18n = new Internationalization_I18n($this->plugin_text_domain);

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @access    private
     */
    private function define_admin_hooks() {

        $Admin_Notice = new Admin_Notice($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());
        $User_Profile = new User_Profile($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());
         $Admin_Setting = new AdminSettings($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain(), new Settings_Api());
        $Admin = new Admin($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain(), $User_Profile, new Login_List_Csv(), $Admin_Setting, $Admin_Notice);



        if (is_network_admin()) {
            $Network_Blog_Manager = new Network_Blog_Manager();
            $this->loader->add_action('wpmu_new_blog', $Network_Blog_Manager, 'on_create_blog', 10, 6);
            $this->loader->add_action('deleted_blog', $Network_Blog_Manager, 'deleted_blog', 10, 1);
        }


       
   

        $Login_Tracker = new Login_Tracker($this->get_plugin_name(), $this->get_version(), NS\PLUGIN_TABLE_FA_USER_LOGINS);
        $Login_Tracker->set_is_geo_tracker_enabled($Admin_Setting->is_geo_tracker_enabled());

        $Network_Admin_Setting = new Network_Admin_Settings($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain(), $Admin_Notice);

        $this->loader->add_action('admin_init', $Admin, 'admin_init');

        if (is_network_admin()) {
            $this->loader->add_action('admin_init', $Network_Admin_Setting, 'update');
        }

        $this->loader->add_action('admin_enqueue_scripts', $Admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $Admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $Admin, 'admin_menu');
        $this->loader->add_action('network_admin_menu', $Admin, 'admin_menu');
        $this->loader->add_filter('set-screen-option', $Admin, 'set_screen', 10, 3);

        $this->loader->add_action('admin_notices', $Admin_Notice, 'show_notice');
        $this->loader->add_action('network_admin_notices', $Admin_Notice, 'show_notice');

        $this->loader->add_action('set_logged_in_cookie', $Login_Tracker, 'set_logged_in_cookie', 10, 6);
        $this->loader->add_action('wp_login_failed', $Login_Tracker, 'on_login_failed');
        $this->loader->add_action('wp_logout', $Login_Tracker, 'on_logout');
        $this->loader->add_action('init', $Login_Tracker, 'init');
        $this->loader->add_action('attach_session_information', $Login_Tracker, 'attach_session_information', 10, 2);

        $this->loader->add_action('admin_init', $Admin_Setting, 'admin_init');
        $this->loader->add_action('admin_menu', $Admin_Setting, 'admin_menu');

        $this->loader->add_action('init', $User_Profile, 'init');
        $this->loader->add_action('show_user_profile', $User_Profile, 'show_extra_profile_fields');
        $this->loader->add_action('edit_user_profile', $User_Profile, 'show_extra_profile_fields');
        $this->loader->add_action('user_profile_update_errors', $User_Profile, 'user_profile_update_errors', 10, 3);
        $this->loader->add_action('personal_options_update', $User_Profile, 'update_profile_fields');
        $this->loader->add_action('edit_user_profile_update', $User_Profile, 'update_profile_fields');

        $this->loader->add_action('network_admin_menu', $Network_Admin_Setting, 'add_setting_menu');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access    private
     */
    private function define_public_hooks() {
        $plugin_public = new Frontend\Frontend($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Retrieve the text domain of the plugin.
     *
     * @since     1.0.0
     * @return    string    The text domain of the plugin.
     */
    public function get_plugin_text_domain() {
        return $this->plugin_text_domain;
    }

}
