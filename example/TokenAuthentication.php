<?php
defined('ABSPATH') || exit;
if (!class_exists('TokenAuthentication')) {
    /**
     * Created by PhpStorm.
     * User: hesk
     * Date: 8/23/14
     * Time: 3:26 PM
     */
    class TokenAuthentication
    {

        public
            $key_exp, $auth_user, $app_post_id,
            $app_key, $developer_id, $store_id,
            $vcoin_account_id, $app_title;

        protected $token_bank_tb, $post_register_tb, $db, $result_r, $titan;

        public function __construct($Q)
        {
            $this->key_exp = $Q->exp;
            $this->auth_user = (int)$Q->user_id;
            $this->app_post_id = (int)$Q->post_id;
            $this->app_key = $Q->appkey;
            $this->developer_id = $Q->devuser;
            $this->store_id = $Q->store_id;
            $this->vcoin_account_id = $Q->vcoin_uuid;
            $this->app_title = $Q->title;
            $this->titan = TitanFramework::getInstance('vcoinset');
        }

        /**
         * Authenication of recording the token
         * @param $token
         * @param $exp
         * @param $user
         */
        public static function genAuthTokenRecord($token, $exp, $user)
        {
            global $wpdb;
            $table = $wpdb->prefix . "app_login_token_banks";
            $insert = array(
                "token" => $token,
                "exp" => $exp,
                "user" => $user
            );
            $wpdb->insert($table, $insert);
        }

        /**
         * not ready to use now
         * @param WP_User $user
         * @param $token
         * @param $app_key
         * @internal param $app_hash
         * @return mixed
         */
        public static function second_init(WP_User $user, $token, $app_key)
        {
            global $wpdb, $app_client;
            $token_bank_tb = $wpdb->prefix . "app_login_token_banks";
            $post_register_tb = $wpdb->prefix . "post_app_registration";
            $verbose = $wpdb->prepare("SELECT
                    t1.exp AS exp,
                    t1.user AS user_id,
                    t2.app_key AS appkey,
                    t2.devuser AS developer_id,
                    t2.store_id AS store_id,
                    t2.vcoin_account AS vcoin_uuid,
                    t2.post_id AS post_id,
                    t2.app_title AS title
            FROM
            $token_bank_tb AS t1 LEFT JOIN $post_register_tb
            AS t2 ON t1.consumerid = t2.ID WHERE t1.token=%s", $token);
            $result_r = $wpdb->get_row($verbose);
            $app_client = new TokenAuthentication($result_r);
        }

        /**
         * json auth central filter method
         * @param $token_input
         * @param $app_key
         * @return int
         */
        public static function get_user_id($token_input, $app_key)
        {
            global $wpdb;
            $token_bank_tb = $wpdb->prefix . "app_login_token_banks";
            $post_register_tb = $wpdb->prefix . "post_app_registration";
            $sql = "SELECT t1.exp, t1.user, t2.app_key FROM $token_bank_tb AS t1 LEFT JOIN $post_register_tb
            AS t2 ON t1.consumerid = t2.ID WHERE t1.token=%s";
            $verbose = $wpdb->prepare($sql, $token_input);
            $result_r = $wpdb->get_row($verbose);
            //  $log = print_r($result_r, true);
            if (!$result_r) {
                // token is invalid
                return -1;
            } else {
                if ($result_r->exp < time()) {
                    //  the token is expired
                    return -2;
                } else {
                    if ($result_r->app_key != $app_key) {
                        return -3;
                    } else {
                        //set merchant role or developer role account
                        //inno_log_db::log_vcoin_third_party_app_transaction(-1, 10204, "TokenAuthentication is initiated." . $log . " / user: " . $result_r->user);
                        return $result_r->user;
                    }
                }
            }
        }

        public function get_developer_name()
        {
            $user = new WP_User($this->developer_id);
            return $user->last_name . " " . $user->first_name . " (" . $user->display_name . ")";
        }

        public function getPostID()
        {
            return $this->app_post_id;
        }

        public function getappID()
        {
            return $this->store_id;
        }

        public function getVcoinId()
        {
            return $this->vcoin_account_id;
        }

        public function isVcoinApp()
        {
            return ($this->vcoin_account_id == $this->titan->getOption("imusic_uuid"));
        }

        public function getAppTitle()
        {
            return $this->app_title;
        }
    }
}