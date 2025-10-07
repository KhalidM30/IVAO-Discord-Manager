<?php
// Load config & objects
include("../config.php");
include("../objects.php");

// Get each row in the users DB
$query = "SELECT * FROM `discord_users` WHERE discord_token_expires BETWEEN NOW() - INTERVAL 20 DAY AND NOW() + INTERVAL 2 DAY;";
$query = mysqli_query($conn,$query);

if ($query){
    while($row = mysqli_fetch_assoc($query)){
        $refresh_token = decryptToken(encryptedToken: $row['discord_refresh_token'], ENCRYPTION_KEY: encryption_key);

        $data = array(
            'grant_type'=>'refresh_token',
            'refresh_token'=> $refresh_token,
            'client_id'=> discord_client_id,
            'client_secret'=> discord_client_secret,
        );
        $exchangedDiscordToken = exchangeDiscordToken(discord_token_url: discord_token_url, data: $data);

        $access_token = encryptToken(token: $exchangedDiscordToken['access_token'], ENCRYPTION_KEY: encryption_key);
        $refresh_token = encryptToken(token: $exchangedDiscordToken['refresh_token'], ENCRYPTION_KEY: encryption_key);
        $expire_token = date("Y-m-d H:i:s", time() + $exchangedDiscordToken['expires_in']);

        $query2 = "UPDATE discord_users SET 
            discord_access_token = '".$access_token."', 
            discord_refresh_token = '".$refresh_token."', 
            discord_token_expires = '".$expire_token."'
            WHERE user_id = ".$row['user_id']."";
        mysqli_query($conn,$query2);
    }
}
?>