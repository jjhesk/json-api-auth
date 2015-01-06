<?php
/*
  Controller name: Authentication Support API
  Controller description: These APIs are dedicated for app-use <br>Author: Heskemo
 */
if (!class_exists('JSON_API_Authen_Controller')) {
    class JSON_API_Authen_Controller
    {
        public static function renew()
        {
            global $json_api;
            try {
                $result = tokenBase::renew_token($json_api->query);
                api_handler::outSuccessData($result);
            } catch (Exception $e) {
                api_handler::outFail($e->getCode(), $e->getMessage());
            }
        }
    }
}