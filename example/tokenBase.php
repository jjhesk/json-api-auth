<?php
defined('ABSPATH') || exit;
/**
 * Created by PhpStorm.
 * User: Hesk
 * Date: 14年8月6日
 * Time: 下午4:41
 */
if (!class_exists('tokenBase')):
    class tokenBase
    {
        /**
         * initialization of using authentication module under the folder
         * /app/wp-content/plugins/jsonapiauth
         */
        public function __construct()
        {
            add_filter("api_token_authen", array(__CLASS__, "authen"), 10, 1);
            add_filter("token_auth_api_check", array("TokenAuthentication", "get_user_id"), 9, 2);
            add_filter("gen_token_SDK", array(__CLASS__, "api_token_sdk_oauth"), 10, 3);
            add_filter("jsonapi_output_error", array(__CLASS__, "jsonapi_output_error"), 10, 2);
            add_filter("display_user_data_after_auth", array(__CLASS__, "display_auth_data"), 15, 3);
            add_action("after_token_verified", array("TokenAuthentication", "second_init"), 10, 3);
        }

        public static function jsonapi_output_error($data_message, $code)
        {
            return messagebox::translateError($data_message, $code);
        }

        /**
         * @param $output
         * @param $user_id
         * @param $login_method
         * @return array
         */
        public static function display_auth_data($output, $user_id, $login_method)
        {
            global $wpdb;
            $user = new WP_User($user_id);
            $output["profile_picture"] = userBase::get_personal_profile_image($user);
            unset($output['url']);
            unset($output['role']);
            unset($output['avatar']);
            if ($login_method == "generate_auth_token") {
                $expiration = $output['exp'];
                $newtoken = self::get_new_token($expiration . '.');
                $output['token'] = $newtoken;
                TokenAuthentication::genAuthTokenRecord($newtoken, $expiration, $user_id);
                try {
                    $n = new app_download();
                    $n->start_sdk_app_v2($_REQUEST["appkey"]);
                    $output["open_app_result"] = $n->get_result();
                } catch (Exception $e) {
                    $output["open_app_result"] = $e->getMessage();
                }
                unset($expiration);
                unset($newtoken);
                return array("data" => $output);
            } elseif ($login_method == "generate_auth_token_third_party") {
                $output['country'] = array(
                    "ID" => get_user_meta($user_id, "countrycode", true),
                    "name" => get_user_meta($user_id, "country", true),
                );
                $output["birthday"] = get_user_meta($user_id, "birthday", true);

                return $output;
            } else {
                $output['country'] = array(
                    "ID" => get_user_meta($user_id, "countrycode", true),
                    "name" => get_user_meta($user_id, "country", true),
                );
                $output["setting_push_sms"] = intval(get_user_meta($user_id, "setting_push_sms", true));
                $output["sms_number"] = get_user_meta($user_id, "sms_number", true);

                $output["gender"] = get_user_meta($user_id, "gender", true);
                $birthday = get_user_meta($user_id, "birthday", true);
                $output["birthday"] = $birthday;
                $output["age"] = Date_Difference::findAge($birthday);
                return $output;
            }
        }


        /**
         * authen for 3rd party
         * @param $user
         * @param $key
         * @param $hash
         * @internal param $user_token
         * @return int
         */
        public static function api_token_sdk_oauth(WP_User $user, $key, $hash)
        {
            global $wpdb;
            $table = $wpdb->prefix . "post_app_registration";
            $verbose = $wpdb->prepare("SELECT * FROM $table WHERE app_key=%s", $key);
            $result_r = $wpdb->get_row($verbose);
            if (!$result_r) {
                return -1;
            } else {
                unset($verbose);
                unset($table);
                if ($result_r->status == 'dead') {
                    return -3;
                } else if (self::hashMatch($hash, $result_r->app_key, $result_r->app_secret)) {
                    $token = self::success_auth_sdk($result_r, $user);
                    return $token;
                } else {
                    unset($result_r);
                    unset($key);
                    unset($hash);
                    return -2;
                }
            }
        }

        /**
         * @param $result
         * @param WP_User $user
         * @return string
         */
        private static function success_auth_sdk($result, WP_User $user)
        {
            global $wpdb;
            $table = $wpdb->prefix . "app_login_token_banks";

            // inno_log_db::log_vcoin_error(-1, 19920, "testing i52");
            $expiration = self::get_project_exp();
            $token = self::get_new_token($expiration . $result->secret);
            // inno_log_db::log_vcoin_error(-1, 19920, "testing i21");
            $insert = array(
                "consumerid" => $result->ID,
                "exp" => $expiration,
                "token" => $token,
                "user" => $user->ID
            );
            $result_of_the_row = $wpdb->insert($table, $insert);

            return $token;
        }

        /**
         * @param $token
         * @return mixed
         * @throws Exception
         */
        public static function authen($token)
        {
            global $wpdb;
            //inno_log_db::log_vcoin_error(-1, 19919, "testing i1");
            $table = $wpdb->prefix . "app_login_token_banks";
            $verbose = $wpdb->prepare("SELECT * FROM $table WHERE token=%s", $token);
            //  $wpdb->select();
            $result_r = $wpdb->get_row($verbose);
            if (!$result_r) throw new Exception("Invalid authentication token. Use the `generate_auth_cookie` Auth API method.", 1001);
            $exp = $result_r->exp;
            // inno_log_db::log_vcoin_error(-1, 19920, "testing i2");
            if ($exp > time()) throw new Exception("Invalid, expired token.", 1002);
            // $verbose_2 = $wpdb->prepare("SELECT * FROM $table WHERE token=%s", $token);


            return $result_r->user;
        }

        /**
         * @param $str
         * @return int
         */
        private static function sha1_64bitInt($str)
        {
            $u = unpack('N2', sha1($str, true));
            return ($u[1] << 32) | $u[2];
        }

        /**
         * @param $hash
         * @param $key
         * @param $secret
         * @return bool
         */
        public static function hashMatch($hash, $key, $secret)
        {
            //inno_log_db::log_vcoin_error(-1, 19921, "testing i3");
            $gen_hash = hash('sha512', $key . $secret);
            if ($gen_hash != $hash)
                inno_log_db::log_vcoin_login(-1, 19922, "unmatched hash: input: " + $hash . ",
                 calculated: " . $gen_hash);

            return $gen_hash == $hash;
        }

        /**
         * @param $str
         * @return string
         */
        private static function get_new_token($str)
        {
            return hash_hmac('ripemd160', $str, LOGGED_IN_SALT);
        }

        private static function get_project_exp()
        {
            if (!class_exists('TitanFramework')) return 0;
            $settings = TitanFramework::getInstance("vcoinset");
            $exp_future = (int)$settings->getOption("token_exp_limit");
            $settings = NULL;
            return time() + $exp_future;
        }

        public static function renew_token($Q)
        {
            global $json_api, $wpdb;
            try {

                if (!isset($Q->wasted)) throw new Exception("wasted key is not presented", 1720);
                if (!isset($Q->hash)) throw new Exception("hash for renewal is not presented", 1721);
                if (!isset($Q->app_k)) throw new Exception("app key for renewal is not presented", 1722);
                if (!isset($Q->nouce)) throw new Exception("app nouce for renewal is not presented", 1723);
                $nonce_id = $json_api->get_nonce_id('auth', 'generate_auth_cookie');
                if (!wp_verify_nonce($Q->nonce, $nonce_id)) throw new Exception("Your 'nonce' value was incorrect. n:" . $nonce_id . ":" . $Q->nonce . 1724);
                $token_bank_tb = $wpdb->prefix . "app_login_token_banks";
                $post_register_tb = $wpdb->prefix . "post_app_registration";

                $verbose_bank = $wpdb->prepare("SELECT
                    t1.exp AS exp,
                    t1.user AS user_id,
                    t2.app_key AS appkey,
                    t2.app_secret AS app_secret,
                    t2.consumerid AS row_id
                FROM
                $token_bank_tb AS t1 LEFT JOIN $post_register_tb
                AS t2 ON t1.consumerid = t2.ID WHERE t1.token=%s", $Q->wasted);


                $old_token_data = $wpdb->get_row($verbose_bank);
                if (!$old_token_data) throw new Exception("Invalid token.", 1725);

                if ((int)$old_token_data->exp < time()) throw new Exception("This token is not expired.", 1726);
                if (!self::hashMatch($Q->hash, $Q->app_k, $old_token_data->app_secret)) throw new Exception("calculation not matched", 1727);

                // inno_log_db::log_vcoin_error(-1, 19920, "testing i52");
                $e_future_time = self::get_project_exp();
                $token = self::get_new_token($e_future_time . $old_token_data->app_secret);
                // inno_log_db::log_vcoin_error(-1, 19920, "testing i21");
                $insert = array(
                    "consumerid" => $old_token_data->row_id,
                    "exp" => $e_future_time,
                    "token" => $token,
                    "user" => $old_token_data->user_id
                );

                $wpdb->delete($token_bank_tb, array("token" => $Q->wasted), array("%s"));
                $result_of_the_row = $wpdb->insert($token_bank_tb, $insert);
                $old_token_data = $insert = NULL;

                return array(
                    "token" => $token,
                    "exp" => $e_future_time
                );
            } catch (Exception $e) {
                throw $e;
            }
        }
    }
endif;