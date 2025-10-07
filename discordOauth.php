<?php
// Check if the code & state is set, else kick
if (isset($_GET['code']) && isset($_GET['state'])){
    // Load config & objects
    include("config.php");
    include("objects.php");

    // Set the code & state
    $discord_code = $_GET['code'];
    $tempToken = $_GET['state'];


    /*=====================================================
    INTERNAL --> Exchange the tempToken to get user's info from db
    =====================================================*/
    $row = exchangeTempToken($tempToken, $conn);
    
    // If the token not in the DB
    if (is_null($row)){
        die("Wrong token. Please try again later!");
    };

    // Set the user's values
    $vid = $row["vid"];
    $name = $row["name"];
    $division = $row["division"];
    $positions = $row["positions"];
    $isSupervisor = false;

    // Check if user is supervisor
    if($row['supervisor'] == 1){
        $isSupervisor = true;
    }

    /*=====================================================
    INTERNAL --> Generate user's nickname & roles
    =====================================================*/
    
    // Create User object
    $user = new User($vid, $name, $division, json_decode($positions, true), $conn, $isSupervisor);
    
    // Get the guilds & nickname & roles
    $guilds = $user -> getGuilds();
    $nicknames = $user -> generateNickname($guilds);
    $roles = $user -> getRoles($guilds);

    /*=====================================================
    DISCORD --> Exchange the code and get access token
    =====================================================*/
    
    // Create data array
    $data = array(
        'code'=>$discord_code,
        'client_id'=> discord_client_id,
        'client_secret'=> discord_client_secret,
        'grant_type'=>'authorization_code',
        'redirect_uri'=> discord_oauth_url,
    );

    // Exchange the Discord token
    $exchangedDiscordToken = exchangeDiscordToken(discord_token_url,$data);

    // Get Access and Refresh Tokens & Expire datetime
    $access_token = $exchangedDiscordToken['access_token'];
    $refresh_token = $exchangedDiscordToken['refresh_token'];
    $expire_token = date("Y-m-d H:i:s", time() + $exchangedDiscordToken['expires_in']);
    
    /*=====================================================
    DISCORD --> Get Discord user ID 
    =====================================================*/

    $user_id = getDiscordUserID(discord_user_url, $access_token);

    /*=====================================================
    INTERNAL --> Check if the VID is already registered on the same discord user or not
    =====================================================*/
    checkUserInDB($vid, $user_id, $conn);

    /*=====================================================
    DISCORD --> Check if the user in the guild or not
                    if he is not in the guild:
                        Add him to the guild
                        Change his Nickname
                        Add all the roles
                        Add him to the DB
                    else:
                        Change  his Nickname
                        Add all the required roles
                        Update the DB
    =====================================================*/

    // Loop the guilds
    foreach ($guilds as $guild) {
        $guild = $guild['id'];
        addUserInGuild(
            access_token: $access_token,
            nicknames: $nicknames,
            roles: $roles[$guild] ?? null,
            user_id: $user_id,
            discord_server_id: $guild,
            discord_bot_token: discord_bot_token,
            conn: $conn,
            vid: $vid,
            name: $name,
            division: $division,
            positions: $positions,
            supervisor: $isSupervisor,
            refresh_token: $refresh_token,
            expire_token: $expire_token,
            encryption_key: encryption_key
        );
    };
    // Remove tempToken data from DB
    $query = "DELETE FROM temp_users WHERE vid = ".$vid;
    $query = mysqli_query($conn,$query);
} else{
    die('You are not allowed to use this page! Contact the System Administrator!');
}
?>