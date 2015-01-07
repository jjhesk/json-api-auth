<?php
/*
  Plugin Name: JSON API Auth
  Plugin URI: https://github.com/jjhesk/json-api-auth-Wordpress
  Description: Extends the JSON API for user authentication utilizing the Wordpress cookie validation and generation.
  Version: 1.5.5
  Author: Hesk
  Author URI: https://github.com/jjhesk/json-api-auth-Wordpress
  License: GPLv3
 */

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
define('JSON_API_AUTH_HOME', dirname(__FILE__));
require_once JSON_API_AUTH_HOME . "/authsupport/json_auth_central.php";
require_once JSON_API_AUTH_HOME . "/authsupport/api_handler_auth.php";
if (!is_plugin_active('json-api/json-api.php')) {
    add_action('admin_notices', 'pim_auth_draw_notice_json_api');
    return;
}

add_filter('json_api_controllers', 'pimAuthJsonApiController');
add_filter('json_api_auth_controller_path', 'setAuthControllerPath');
load_plugin_textdomain('json-api-auth', false, basename(dirname(__FILE__)) . '/languages');
add_action('json_api_auth_external', array("json_auth_central", "auth_cookie_json"));
//add_action('auth_api_token_check', array("json_auth_central", "auth_check_token_json"));
//add_action('gen_new_auth_token', array("json_auth_central", "auth_cookie_json"));
function pim_auth_draw_notice_json_api()
{
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>JSON API Auth</strong></a> requires the JSON API plugin to be activated. Please <a href="wordpress.org/plugins/json-api/‎">install / activate JSON API</a> first.', 'json-api-user');
    echo '</p></div>';
}

function pimAuthJsonApiController($aControllers)
{
    $aControllers[] = 'Auth';
    return $aControllers;
}

function setAuthControllerPath($sDefaultPath)
{
    return dirname(__FILE__) . '/controllers/auth.php';
}

add_filter('display_user_data_after_auth', 'display_user_data_sample', 10, 3);
function display_user_data_sample($array, $user_id, $login_method)
{
    return $array;
}

?>