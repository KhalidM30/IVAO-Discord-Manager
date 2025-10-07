<?php
// Load config
include("config.php");


/**
 * @return int|false the HTTP response code from the given HTTP response header or false
 * if the header is not an array or does not contain a valid status code.
 */
function get_http_response_code($http_response_header) {
    if (is_array($http_response_header) && isset($http_response_header[0])) {
        $parts = explode(' ', $http_response_header[0]);
        if (count($parts) > 1) {
            return (int)$parts[1];
        }
    }
    return false;
}

// Get all URLs we need from the server
$openid_result = file_get_contents(openid_url);
if ($openid_result === false) {
    /* Handle error */
    die('Error while getting openid data');
}
$openid_data = json_decode($openid_result, true);


if (isset($_GET['code']) && isset($_GET['state'])) {
    // User has been redirected back from the login page

    $code = $_GET['code']; // Valid only 5 minutes

    $token_req_data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => ivao_client_id,
        'client_secret' => ivao_client_secret,
        'redirect_uri' => ivao_redirect_url,
    ];
    
    // use key 'http' even if you send the request to https://...
    $token_options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($token_req_data)
        ]
    ];
    $token_context  = stream_context_create($token_options);
    $token_result = file_get_contents($openid_data['token_endpoint'], false, $token_context);
    if ($token_result === FALSE) { 
        /* Handle error */
        die('Error while getting IVAO token');
    }
    
    $token_res_data = json_decode($token_result, true);

    $access_token = $token_res_data['access_token']; // Here is the access token
    $user_options = [
        'http' => [
            'header'  => "Authorization: Bearer $access_token\r\n",
            'method'  => 'GET',
            'ignore_errors' => true,
        ]
    ];
    $user_context  = stream_context_create($user_options);
    $user_result = file_get_contents($openid_data['userinfo_endpoint'], false, $user_context);
    if ($user_result === FALSE) {
        /* Handle error */
        die('Error while getting user data');
    }

    $user_result_response_code = get_http_response_code($http_response_header);
    if ($user_result_response_code === false) {
        /* Handle error */
        die('Error while getting user data response code');
    }

    $user_res_data = json_decode($user_result, true);
    //$user_res_data['isSupervisor'] = True;

    // if staff continue else kick him
    if ($user_res_data['isStaff'] == True){
        $isSupervisor = 0;

        if($user_res_data['isSupervisor']){
            $isSupervisor = 1;
        }
        // make temp token to store it safely in the DB
        $tempToken = bin2hex(openssl_random_pseudo_bytes(12));

        // DB Query
        $query = "INSERT INTO temp_users VALUES
		    ('".$tempToken."',
		    ".$user_res_data['id'].",
            '".$user_res_data['firstName']."',
		    '".$user_res_data['divisionId']."',
		    '".json_encode($user_res_data['userStaffPositions'])."',
            '".$isSupervisor."',
		    '".date("Y-m-d H:i:s")."')";
	    
        // Run query
        mysqli_query($conn,$query);

        // Prepare the redirect url to discord oAuth
        $full_url = sprintf('%s?%s', 'https://discord.com/oauth2/authorize', http_build_query([
            'response_type' => 'code',
            'client_id' => discord_client_id,
            'scope' => 'identify guilds.join',
            'redirect_uri' => discord_oauth_url,
            'state' => $tempToken
        ]));

        header("Location: $full_url");
    } else {
        die('You are not allowed to use this login page! Contact the System Administrator!');
    }
}
?>