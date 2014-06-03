<?php
/**
 * The core support for centralized API
 * User: hesk
 * Date: 6/3/14
 * Time: 10:25 PM
 *
 * new filter to display user data - auth_display_user_data
 */

namespace Supports;


class json_auth_central
{
    protected static function auth_cookie()
    {
        global $json_api, $current_user;
        if (!is_user_logged_in()) {
            if (!$json_api->query->cookie) {
                $json_api->error("please get authenticated.");
            } else {
                $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
                if (!$user_id) {
                    $json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
                }
            }
        }
    }

    /**
     * @param WP_User $user
     * @return mixed
     */
    protected static function gen_auth_cookie(WP_User $user)
    {
        $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
        return $cookie = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');
    }

    /**
     * Authenticate user with normal password and username or email
     * @return mixed
     */
    protected static function auth_login()
    {
        global $json_api;
        $nonce_id = $json_api->get_nonce_id('auth', 'generate_auth_cookie');
        if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
            $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
        }
        if (!$json_api->query->username && $json_api->query->email) {
            $json_api->error("You must include either 'username' or 'email' var in your request.");
        }

        if (!$json_api->query->password) {
            $json_api->error("You must include a 'password' var in your request.");
        }

        if ($json_api->query->email && !$json_api->query->username) {
            $userob = get_user_by('email', $json_api->query->email);
            $user = wp_authenticate($userob->username, $json_api->query->password);
        } else {
            $user = wp_authenticate($json_api->query->username, $json_api->query->password);
        }

        if (is_wp_error($user)) {
            $json_api->error("Invalid username and/or password.", 'error', '401');
            remove_action('wp_login_failed', $json_api->query->username);
        }

        return $user;
    }

    protected static function display_user(WP_User $user)
    {
        $user_info = get_userdata($user->ID);
        return apply_filters("auth_display_user_data", array(
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
                "role" => $user_info->roles
            )
        ));
    }

} 