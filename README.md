## Wordpress JSON API Auth Controller

Authentication add-on controller for the Wordpress JSON API plugin utilizing the Wordpress cookie validation and generation.

As a part of JSON API plugin from http://wordpress.org/plugins/json-api/ as the dependency.

*Wordpress JSON API Plugin:*

How does it work
for any 

`JSON_API_{controller}_Controller class `

you need to extend from 

`extends \Supports\json_auth_central`

in your implementation method your should include this code inside 
`$auth_result = parent::auth_cookie();`


Sample code

	class JSON_API_Awesome_Controller extends \Supports\json_auth_central{
	
	   public function test_normal_function()
	    {
	        $res = parent::auth_cookie();
	        global $current_user;
	        return array(
	            "user" => $current_user
	        );
	    }
	    
	}

