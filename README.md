## Wordpress JSON API Auth Controller

Authentication add-on controller for the Wordpress JSON API plugin utilizing the Wordpress cookie validation and generation.

As a part of JSON API plugin from http://wordpress.org/plugins/json-api/ as the dependency.

*Wordpress JSON API Plugin:*

How does it work?

in your implementation method your should include this code inside 
` do_action('json_api_auth_external');`

Sample code

	class JSON_API_Awesome_Controller {
	
	   public function test_normal_function()
	    {
	       do_action('json_api_auth_external');
	        global $current_user;
	        return array(
	            "user" => $current_user
	        );
	    }
	    
	}

