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

There are three methods available: 
	validate_auth_cookie(), 
	generate_auth_cookie(), 
	get_currentuserinfo()

nonce can be created by calling 
`{domain}/api/get_nonce/?controller=auth&method=generate_auth_cookie`

You can then use 'nonce' value to generate cookie.

`{domain}/api/auth/generate_auth_cookie/?nonce=f4320f4a67&username=Catherine&password=password-here`

Use cookie like this with your other controller calls:

`{domain}/api/contoller-name/method-name/?cookie=Catherine|1392018917|3ad7b9f1c5c2cccb569c8a82119ca4fd`
