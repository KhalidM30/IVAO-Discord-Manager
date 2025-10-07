<?php
// Load environment variables
$ENV = parse_ini_file('.env');

/*=====================================================
Database information & setup
=====================================================*/
$host = '127.0.0.1';
$user = '';
$port = 3306;
$dbname = '';
$password = '';

// Check Connection
if (!($conn = @mysqli_connect($host, $user, $password, '' , $port))) {	
     echo mysqli_connect_error();
}
 
// Check Database
if(!($db = @mysqli_select_db($conn,$dbname))) {
	die("<p align='center'><big><img src='img/redx.png'><br/><strong>It wasn't possible to connect to database <i>$dbname</i>. Please, check the configurations.</strong></big></p>");
}

// Create Connection
$conn = mysqli_connect($host, $user, $password, $dbname , $port);

/*=====================================================
Definitions
=====================================================*/
define('openid_url', $ENV['OPENID_URL']);
define('ivao_client_id', $ENV['IVAO_CLIENT_ID']);
define('ivao_client_secret', $ENV['IVAO_CLIENT_SECRET']);
define('ivao_redirect_url', $ENV['IVAO_REDIRECT_URL']);
define('discord_client_id', $ENV['DISCORD_CLIENT_ID']);
define('discord_client_secret', $ENV['DISCORD_CLIENT_SECRET']);
define('discord_oauth_url', $ENV['DISCORD_OAUTH_URL']);
define('discord_token_url', $ENV['DISCORD_TOKEN_URL']);
define('discord_user_url', $ENV['DISCORD_USER_URL']);
define('discord_main_server_id', $ENV['DISCORD_MAIN_SERVER_ID']);
define('discord_bot_token', $ENV['DISCORD_BOT_TOKEN']);
define('encryption_key', $ENV['ENCRYPTION_KEY']);
?>
