<?php
// Load config & objects
include("../config.php");
include("../objects.php");


// Get each row in the users DB
$query = "SELECT * FROM `discord_users`";
$query = mysqli_query($conn,$query);

if ($query){
    while($row = mysqli_fetch_assoc($query)){
        $discord_user_id = $row['discord_id'];
        $guild_url = "https://discord.com/api/guilds/".discord_main_server_id."/members/" . $discord_user_id;
        $headers = array(
            "Content-Type: application/json", 
            "Authorization: Bot " . discord_bot_token
        );
        // Check if discord user is already joined server (error code 10007 = Unknown Member)
        $userGuildObject = checkUserInGuild($guild_url, $headers);
        if (isset($userGuildObject['code']) AND in_array($userGuildObject['code'], [10007, 10013])){
            $db_guilds = mysqli_query($conn, "SELECT * FROM discord_guilds");
            while($guild_row = mysqli_fetch_assoc($db_guilds)) {
                if ($guild_row['guild_id'] != discord_main_server_id){
                    $guild_url = "https://discord.com/api/guilds/".$guild_row['guild_id']."/members/" . $discord_user_id;
                    $userGuildObject = checkUserInGuild($guild_url, $headers);
                    if (!isset($userGuildObject['code'])){
                        removeRolesFromUser($guild_url, $headers, $userGuildObject['roles'], current_roles: null, conn: $conn);
                        setUserNickname($guild_url, $headers, null);
                        deleteUserFromGuild($guild_url, $headers);
                    }
                }
            }
            mysqli_query($conn,"DELETE FROM discord_users WHERE discord_id = ".$discord_user_id);
        } else {
            $vid = $row['vid'];
            $ivaoUser = getIvaoUser(openid_url, ivao_client_id, ivao_client_secret, $vid);
            if ($ivaoUser['isStaff'] == True){
                $user = new User($vid, $row['name'], $ivaoUser['divisionId'], $ivaoUser['userStaffPositions'], $conn);
                $guilds = $user -> getGuilds();
                $nicknames = $user -> generateNickname($guilds);
                $roles = $user -> getRoles($guilds);

                foreach ($guilds as $guild) {
                    $guild = $guild['id'];
                    if ($guild == discord_main_server_id){
                        // Change nickname if the generated nickname != discord nickname
                        if ($nicknames[$guild] != $userGuildObject['nick']){
                            setUserNickname($guild_url, $headers, $nicknames[$guild]);
                        }
                        // Delete unwanted current roles
                        removeRolesFromUser($guild_url, $headers, $roles[$guild], $userGuildObject['roles'], $conn);

                        // Add Roles
                        addRolesToUser($guild_url, $headers, $roles[$guild]);
                    } else {
                        $guild_url = "https://discord.com/api/guilds/".$guild."/members/" . $discord_user_id;
                        $userGuildObject = checkUserInGuild($guild_url, $headers);

                        if (isset($userGuildObject['code']) AND $userGuildObject['code'] == 10007){
                            $access_token = decryptToken(encryptedToken: $row['discord_access_token'], ENCRYPTION_KEY: encryption_key);
                            addUserInGuild(
                                access_token: $access_token,
                                nickname: $nicknames,
                                roles: $roles[$guild],
                                user_id: $discord_user_id,
                                discord_server_id: $guild,
                                discord_bot_token: discord_bot_token,
                                conn: $conn,
                                vid: $vid,
                                name: $ivaoUser['firstName'],
                                division: $ivaoUser['divisionId'],
                                positions: json_encode($ivaoUser['userStaffPositions'])
                            );
                        } else {
                            // Change nickname if the generated nickname != discord nickname
                            if ($nicknames[$guild] != $userGuildObject['nick']){
                                setUserNickname($guild_url, $headers, $nicknames[$guild]);
                            }
                            if($roles){
                                // Delete unwanted current roles
                                removeRolesFromUser($guild_url, $headers, $roles[$guild] ?? null, $userGuildObject['roles'], $conn);
                                // Add Roles
                                addRolesToUser($guild_url, $headers, $roles[$guild] ?? null);
                            }
                            
                        }
                    }
                }

                $notListedGuilds = getnotListedGuilds($guilds, $conn);

                if ($notListedGuilds){
                    foreach ($notListedGuilds as $notListedGuild){
                        $guild_url = "https://discord.com/api/guilds/".$notListedGuild."/members/" . $discord_user_id;
                        $userGuildObject = checkUserInGuild($guild_url, $headers);
                        if (!isset($userGuildObject['code'])){
                            removeRolesFromUser($guild_url, $headers, $userGuildObject['roles'], current_roles: null, conn: $conn);
                            setUserNickname($guild_url, $headers, null);
                            deleteUserFromGuild($guild_url, $headers);
                        }
                    }
                }
                // Modify the DB
                $query2 = "UPDATE discord_users SET 
                    vid = '".$vid."', 
                    name = '".$row['name']."', 
                    division = '".$ivaoUser['divisionId']."', 
                    nickname = '".$nicknames["fullNickname"]."',
                    last_checked_at = '".date("Y-m-d H:i:s")."',
                    positions = '".json_encode($ivaoUser['userStaffPositions'])."' 
                    WHERE discord_id = ".$discord_user_id." ORDER BY joined_at ASC LIMIT 1";
                mysqli_query($conn,$query2);
            } else {
                $db_guilds = mysqli_query($conn, "SELECT * FROM discord_guilds");
                while($guild_row = mysqli_fetch_assoc($db_guilds)) {
                    $guild_url = "https://discord.com/api/guilds/".$guild_row['guild_id']."/members/" . $discord_user_id;
                    $userGuildObject = checkUserInGuild($guild_url, $headers);
                    if (!isset($userGuildObject['code'])){
                        removeRolesFromUser($guild_url, $headers, $userGuildObject['roles'], current_roles: null, conn: $conn);
                        setUserNickname($guild_url, $headers, null);
                        deleteUserFromGuild($guild_url, $headers);
                    }
                }
                mysqli_query($conn,"DELETE FROM discord_users WHERE discord_id = ".$userGuildObject['user']['id']);
            }
        }
    }
}

?>