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

        /**
         * @throws Exception
         */
        public static function auth_check_token_json()
        {
            global $json_api, $current_user;
            if (!isset($json_api->query->token)) throw new Exception("token is required for authentication.", 1504);
            if (!isset($json_api->query->appkey)) throw new Exception("app key is needed.", 1509);
            //-same client pass user id
            //-$user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
            $authenticated_user_id = apply_filters("token_auth_api_check", $json_api->query->token, $json_api->query->appkey);


            if (intval($authenticated_user_id) == -1) {
                throw new Exception("Invalid authentication token. Use the `generate_token` Auth API method.", 1505);
            } else if (intval($authenticated_user_id) == -2) {
                throw new Exception("This token is currently expired. Use the `generate_token` Auth API method.", 1507);
            } else if (intval($authenticated_user_id) == -3) {
                throw new Exception("Unmatched App Key, please go back and double check.", 1508);
            } else if (intval($authenticated_user_id) > 0) {
                wp_set_current_user($authenticated_user_id);
                do_action("after_token_verified", $current_user, $json_api->query->token, $json_api->query->appkey);
            } else {
                throw new Exception("Unknown error - " . $authenticated_user_id, 1506);
            }
        }

        //using the token to access the server api for third party level
        public static function oauthentication_sdk()
        {
            global $json_api, $current_user;
            if (!$json_api->query->apitoken) {
                $json_api->error("please get authenticated.");
            } else {
                try {
                    $user_id = apply_filters("api_token_sdk_oauth", $json_api->query->apitoken);
                    if ($current_user->ID != $user_id) {
                        wp_set_current_user($user_id);
                    } else {
                        // this should be able to produce the login account
                    }
                } catch (Exception $e) {
                    $json_api->error($e->getMessage());
                    //the code dies here....
                }

            }
        }

        //using token to access the server api
        public static function auth_token()
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

        public static function auth_cookie()
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
        public static function gen_auth_cookie(WP_User $user)
        {
            $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
            $token = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');
            //do_action("gen_new_auth_token", $token, $user->ID, $expiration);
            return $token;
        }


        /**
         * Authenticate user with normal password and username or email
         * to Acquire the token from the login information
         * @throws Exception
         * @return mixed
         */
        public static function auth_login()
        {
            global $json_api;
            $nonce_id = $json_api->get_nonce_id('auth', 'generate_auth_cookie');
            if (!wp_verify_nonce($json_api->query->nonce, $nonce_id)) {
                throw new Exception("Your 'nonce' value was incorrect. Use the 'get_nonce' API method. n:" . $nonce_id . " from nonce input:" . $json_api->query->nonce, 1781);
                //$json_api->error();
            }
            if (!$json_api->query->username && !$json_api->query->email) {
                //  $json_api->error("You must include either 'username' or 'email' var in your request.");
                throw new Exception("You must include either 'username' or 'email' var in your request.", 1782);
            }

            if (!$json_api->query->password) {
                // $json_api->error("You must include a 'password' var in your request.");
                throw new Exception("You must include a 'password' var in your request.", 1783);
            }

            if ($json_api->query->email && !$json_api->query->username) {
                $userob = get_user_by('email', $json_api->query->email);
                $user = wp_authenticate($userob->username, $json_api->query->password);
            } else {
                $user = wp_authenticate($json_api->query->username, $json_api->query->password);
            }
            if (is_a($user, 'WP_User'))
                return $user;
            else if (is_wp_error($user)) {
                //$json_api->error("Invalid username and/or password.", 'error', '401');
                remove_action('wp_login_failed', $json_api->query->username);
                throw new Exception("Invalid username and/or password.", 1784);
            }
            return $user;
        }

        /**
         * get the token from the current login user
         * @param WP_User $user
         * @param $query
         * @throws Exception
         * @return
         */
        public static function generate_token_sdk(WP_User $user, $query)
        {
            global $json_api;
            if (!isset($query->key)) {
                throw new Exception("the app key is not presented.");
            }
            if (!isset($query->hash)) {
                throw new Exception("the app hash is not presented.");
            }
            $result = apply_filters('gen_token_SDK', $user, $query->key, $query->hash);
            if ($result == -1) throw new Exception("The app key is not found from the system.");
            if ($result == -3) throw new Exception("The app key is currently unlisted.");
            if ($result == -2) throw new Exception("The app key and the secret calculation is not matched.");
            //integer $result
            return $result;
        }

        /**
         * get the token from the current login user
         * @param WP_User $user
         * @param $query
         * @throws Exception
         * @return
         */
        public static function generate_token_simple(WP_User $user, $query)
        {
            global $json_api;
            $result = apply_filters('gen_token_SDK', $user, "", "");
            if ($result == -1) throw new Exception("The app key is not found from the system.");
            if ($result == -3) throw new Exception("The app key is currently unlisted.");
            if ($result == -2) throw new Exception("The app key and the secret calculation is not matched.");
            return $result;
        }

        /**
         * @param WP_User $user
         * @param array $other_data
         * @param $login_method
         * @return array
         */
        public static function display_user_data(WP_User $user, $other_data = array(), $login_method = "")
        {
            preg_match('|src="(.+?)"|', get_avatar($user->ID, 32), $avatar);
            $user_info = get_userdata($user->ID);
            return apply_filters("display_user_data_after_auth", array_merge(array(
                "id" => intval($user->ID),
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
            ), $other_data
            ), $user->ID, $login_method);
        }
    }
}