<?php
/**
 * The core support for centralized API
 * User: hesk
 * Date: 6/3/14
 * Time: 10:25 PM
 */

namespace Supports;


class json_auth_central
{
    protected function authlogin()
    {
        global $json_api;
        $nonce_id = $json_api->get_nonce_id('auth', 'generate_auth_cookie');
        if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
            $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
        }
        if (!$json_api->query->username) {
            $json_api->error("You must include a 'username' var in your request.");
        }
        if (!$json_api->query->password) {
            $json_api->error("You must include a 'password' var in your request.");
        }
        $user = wp_authenticate($json_api->query->username, $json_api->query->password);
        if (is_wp_error($user)) {
            $json_api->error("Invalid username and/or password.", 'error', '401');
            remove_action('wp_login_failed', $json_api->query->username);
        }
        return $user;
    }

    protected function display_user(WP_User $user)
    {
        return array(
            "user" => array(
                "id" => $user->ID,
                "username" => $user->user_login,
                "nicename" => $user->user_nicename,
                "email" => $user->user_email,
                "url" => $user->user_url,
                "registered" => $user->user_registered,
                "displayname" => $user->display_name,
                "firstname" => $user->user_firstname,
                "lastname" => $user->last_name,
                "nickname" => $user->nickname,
                "description" => $user->user_description,
                "capabilities" => $user->wp_capabilities,
            )
        );
    }

} 