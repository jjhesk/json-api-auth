## Wordpress JSON API Auth Controller

Authentication add-on controller for the Wordpress JSON API plugin utilizing the Wordpress 

1. cookie validation
2. cookie generation
3. custom token validation
4. custom token generation

As a part of JSON API plugin from http://wordpress.org/plugins/json-api/ as the dependency.

*Wordpress JSON API Plugin:*

How does it work?
In your implementation method your should include this code inside like so:
` do_action('json_api_auth_external');`

Issue:
If the app and the web browser is sharing the same api method, add an internal checking to avoid using the browser cookie
`if (!is_user_logged_in()) do_action('json_api_auth_external');`


###Sample code
```php
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
```
There are three methods available: 
```php
validate_auth_cookie()
	
generate_auth_cookie()

get_currentuserinfo()

```

nonce can be created by calling 
`{domain}/api/get_nonce/?controller=auth&method=generate_auth_cookie`

You can then use 'nonce' value to generate cookie.

`{domain}/api/auth/generate_auth_cookie/?nonce=f4320f4a67&username=Catherine&password=password-here`

Use cookie like this with your other controller calls:

`{domain}/api/contoller-name/method-name/?cookie=Catherine|1392018917|3ad7b9f1c5c2cccb569c8a82119ca4fd`

### writing your token application for login

You will need to implement the follow filters to make the token login activated.
```php
  add_filter("gen_new_auth_token", array(__CLASS__, "gen_new_auth_token"), 10, 1);
  add_filter("api_token_authen", array(__CLASS__, "api_token_authen"), 10, 1);
  add_filter("token_auth_api_check", array(__CLASS__, "token_auth_api_check"), 10, 1);

```
* gen_new_auth_token
adding a new token key in the array as to display the new generated token
sample filter code:
```php
 public static function gen_new_auth_token($output)
    //add your token logics here 
    $output['token'] = "XXXXXXXXXXtokenXXXXXXXXX";
    return $output;
    }
```
* api_token_authen
sample filter code:
```php
public static function api_token_authen ($token)
  global $wpdb;
      //your logics here
      if (!$result_r) throw new Exception("Invalid authentication token. Use the `generate_auth_cookie` Auth API method.", 1001);
      //your logics here to find the token expiration
      if ($exp > time()) throw new Exception("Invalid, expired token.", 1002);
      // your logic here to return the WP_User object
       return $result_r->user;
```
* token_auth_api_check

```php
public static function token_auth_api_check ($token_input)
            global $wpdb;
            //your logics here
             if (!$result_r) {
             //not success
                return -1;
            } else {
            //success and return the WP_User object
                return $result_r->user;
            }
```

### new feature with token API login Usage

###Step 1

Server side API endpoint using GET method
`{domain}/api/get_nonce/?controller=auth&method=generate_auth_cookie`

Response example
{"status":"ok","controller":"auth","method":"generate_auth_cookie","nonce":"2d0edc3b41"}

###Step 2

Initialize login information with the key value nonce from the step 1
Server side API endpoint using GET method

`{domain}/api/auth/generate_auth_token/?nonce={nonce}&username={username}&password={password}`

Response example

```
{status: "ok", user: {...}, exp: 1408614869, token: "1779a5c71a8e0e07fc6c2be50cb7bba326043d31"}
```

###Step 3

Server side API endpoint using GET method

explanation
`{domain}/api/{any_controllers}/{refered_method}?token={token}`

Please pass the obtained token from step 2 and pass it into the parameter as described on the left side.

###check pass-in token with other JSON API controllers sample code

Please add and implement the following code for check login token

```php
        public static function sample_check_login()
        {
            {
                global $json_api, $current_user;
                try {
                    // do_action('auth_api_token_check');
                    if (class_exists("json_auth_central")) {
                        json_auth_central::auth_check_token_json();
                        //============================================================
                        // your code for after login success starts here
                        //
                        //==============================================================
                        return array("status" => "okay", "user" => $current_user, "result" => "");
                    } else {
                        throw new Exception("module not installed", 1007);
                    }
                } catch (Exception $e) {
                    return array("status" => "failure", "message" => $e->getMessage(), "code" => $e->getCode());
                }
            }
        }
        
```
