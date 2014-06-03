## Wordpress JSON API Auth Controller

Authentication add-on controller for the Wordpress JSON API plugin utilizing the Wordpress cookie validation and generation.

As a part of JSON API plugin from http://wordpress.org/plugins/json-api/ as the dependency.

*Wordpress JSON API Plugin:*

How does it work
for any 

JSON_API_{controller}_Controller class 

you need to extend from 

extends \Supports\json_auth_central


example
``
JSON_API_{controller}_Controller extends \Supports\json_auth_central{

   public function test_normal_function()
    {
        $res = parent::auth_cookie();
        global $current_user;
        return array(
            "user" => $current_user
        );
    }
    
}
``
