<?php

/*
    Controller Name: Auth
    Controller Description: Authentication add-on controller for the Wordpress JSON API plugin
    Controller Author:Hesk
*/

class JSON_API_Auth_Controller
{
    /**
     * generating the auth app.
     * @return array
     */
    public function generate_auth_cookie()
    {
        global $json_api;
        //this is the actual login process
        $user = json_auth_central::auth_login();
        $cookie = json_auth_central::gen_auth_cookie($user);

        $out = json_auth_central::display_user_data($user, array(
            "cookie" => $cookie
        ), "generate_auth_cookie");

        return $out;
    }

    /**
     * needing app key and app secret (hash)
     *
     * @return array
     */
    public function generate_auth_token_third_party()
    {
        global $json_api;
        try {
            $user = json_auth_central::auth_login();
            $appdata = json_auth_central::generate_token_sdk($user, $json_api->query);
            return array("data" => json_auth_central::display_user_data($user, array("token" => $appdata), "generate_auth_token_third_party"));
        } catch (Exception $e) {
            $json_api->error($e->getMessage());
        }
    }

    /**
     *
     * needing app key and app secret (hash)
     * this will replace the old method generate_auth_token_third_party on V1.0
     * @return array
     */
    public function generate_token()
    {
        global $json_api;
        try {
            $user = json_auth_central::auth_login();
            $appdata = json_auth_central::generate_token_sdk($user, $json_api->query);
            //  $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
            return array(
                "data" => json_auth_central::display_user_data($user,
                        array(
                            "token" => $appdata,
                            // "exp" => $expiration
                        ),
                        "generate_auth_token_third_party"));
        } catch (Exception $e) {
            $json_api->error($e->getMessage());
        }
    }

    /**
     * does not need app key and app secret
     * @return array
     */
    public function generate_auth_token()
    {
        try {
            global $json_api;
            $user = json_auth_central::auth_login();
            $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
            $out = json_auth_central::display_user_data($user, array(
                "exp" => $expiration
            ), "generate_auth_token");
            return $out;
        } catch (Exception $e) {
            $json_api->error($e->getMessage());
        }
    }

    public function get_currentuserinfo()
    {
        global $json_api;
        if (!$json_api->query->cookie) {
            $json_api->error("You must include a 'cookie' var in your request. Use the `generate_auth_cookie` Auth API method.");
        }
        $user_id = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in');
        if (!$user_id) {
            $json_api->error("Invalid authentication cookie. Use the `generate_auth_cookie` Auth API method.");
        }
        $user = get_userdata($user_id);
        $out = json_auth_central::display_user_data($user, array(), "get_currentuserinfo");
        return $out;
    }

}
