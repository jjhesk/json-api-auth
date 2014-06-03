## Wordpress JSON API Auth Controller

Authentication add-on controller for the Wordpress JSON API plugin utilizing the Wordpress cookie validation and generation.

As a part of JSON API plugin from http://wordpress.org/plugins/json-api/ as the dependency.

*Wordpress JSON API Plugin:*

How does it work?
In your implementation method your should include this code inside like so:
` do_action('json_api_auth_external');`

Issue:
If the app and the web browser is sharing the same api method, add an internal checking to avoid using the browser cookie
`if (!is_user_logged_in()) do_action('json_api_auth_external');`


Sample code

	class JSON_API_Awesome_Controller {
	
	   public function test_normal_function()
	    {
	        //making sure you put this line first
	       do_action('json_api_auth_external');
	       
	       
	       //do your things that require authentication here... blah blah blah
	       //for example ... 
	       
	        global $current_user;
	        return array(
	            "user" => $current_user
	        );
	        
	        
	    }
	    
	}

