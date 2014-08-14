<?php
/**
 * The core support for centralized API
 * User: hesk
 * Date: 6/3/14
 * Time: 10:25 PM
 *
 * new filter to display user data - auth_display_user_data
 */

if (!class_exists("json_auth_central", true)) {
    class json_auth_central
    {
        public static function auth_cookie_json()
        {
            self::auth_cookie();
        }

        public static function auth_check_token_json()
        {
            global $json_api, $current_user;
            if (!is_user_logged_in()) {
                if (!$json_api->query->token) {
                    throw new Exception("please get authenticated.", 1004);
                } else {
                    //same client pass user id
                    //$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
                    $authenticated_user_id = apply_filters("token_auth_api_check", $json_api->query->token);
                    if (intval($authenticated_user_id) == -1) {
                        throw new Exception("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.", 1005);
                    } else if (intval($authenticated_user_id) > 0) {
                        wp_set_current_user($authenticated_user_id);
                    } else {
                        throw new Exception("Unknown error. ", 1006);
                    }
                }
            }
        }

        public static function default_auth_token_filter($token)
        {
            /// process the
            return false;
        }

        protected static function auth_token()
        {
            global $json_api, $current_user;

            if (!$json_api->query->apitoken) {
                $json_api->error("please get authenticated.");
            } else {
                //same client pass user id
                // $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
                //  if (!has_filter('api_token_authen', 'example_alter_the_content')) {
                try {
                    $user_id = apply_filters("api_token_authen", $json_api->query->apitoken);
                } catch (Exception $e) {
                    $json_api->error($e->getMessage());
                    //the code dies here....
                }
                //"Invalid authentication token. Use the `generate_auth_cookie` Auth API method."

                if ($current_user->ID != $user_id) {
                    //  wp_clear_auth_cookie();
                    //  wp_set_auth_cookie($user_id, true);
                    wp_set_current_user($user_id);
                } else {
                    // this should be able to produce the login account
                }
            }


        }

        protected static function auth_cookie()
        {
            global $json_api, $current_user;
            if (!is_user_logged_in()) {
                if (!$json_api->query->cookie) {
                    $json_api->error("please get authenticated.");
                } else {
                    //same client pass user id
                    $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
                    if (!$user_id) {
                        $json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
                    } else if ($current_user->ID != $user_id) {
                        wp_clear_auth_cookie();
                        wp_set_auth_cookie($user_id, true);
                        wp_set_current_user($user_id);
                    } else {
                        // this should be able to produce the login account
                    }
                }
            }
        }

        /**
         * Gen Auth Cookie and notify third party system
         * @param WP_User $user
         * @internal param bool $useFilter
         * @return mixed
         */
        protected static function gen_auth_cookie(WP_User $user)
        {
            $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
            $token = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');
            //do_action("gen_new_auth_token", $token, $user->ID, $expiration);
            return $token;
        }


        /**
         * Authenticate user with normal password and username or email
         * to Acquire the token from the login information
         * @return mixed
         */
        protected static function auth_login()
        {
            global $json_api;
            $nonce_id = $json_api->get_nonce_id('auth', 'generate_auth_cookie');
            if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
                $json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method. n:" . $nonce_id . " from nonce input:" . $json_api->query->nonce);
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
            preg_match('|src="(.+?)"|', get_avatar($user->ID, 32), $avatar);
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
                    "role" => $user_info->roles,
                    "avatar" => $avatar[1]
                )
            ));
        }

    }
}