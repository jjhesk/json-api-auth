<?php

/*
    Controller Name: Auth
    Controller Description: Authentication add-on controller for the Wordpress JSON API plugin
    Controller Author: Matt Berg
               Forked: Hesk
    Controller Author Twitter: @mattberg
*/


class JSON_API_Auth_Controller extends json_auth_central
{
    public function test_normal_function()
    {
        $res = parent::auth_cookie();
        global $current_user;
        return array(
            "user" => $current_user
        );
    }

    public function generate_auth_cookie()
    {
        global $json_api;
        //this is the actual login process

        $user = parent::auth_login();
        $cookie = parent::gen_auth_cookie($user);
        $out = parent::display_user($user);
        $out["cookie"] = $cookie;
        return $out;
    }

    public function generate_auth_token()
    {
        $user = parent::auth_login();
        //  $cookie = parent::gen_auth_cookie($user);
        $expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user->ID, true);
        // $token = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');
        $out = parent::display_user($user);
        $out["exp"] = $expiration;
        return apply_filters("gen_new_auth_token", $out);
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
        return parent::display_user($user);
    }

}