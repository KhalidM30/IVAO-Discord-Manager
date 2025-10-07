<?php


class User {
    public $firstName;
    public $divisionId;
    public $userStaffPositions;
    public $vid;
    public $conn;
    public $isSupervisor;

    public function __construct($vid = null, $firstName = null, $divisionId = null, $userStaffPositions = null, $conn = null, $isSupervisor = false) {
        $this->vid = $vid;
        $this->firstName = $firstName;
        $this->divisionId = $divisionId;
        $this->userStaffPositions = $userStaffPositions;
        $this->conn = $conn;
        $this->isSupervisor = $isSupervisor;
    }

    public function oldgenerateNickname() {
        $seniorStaffPositions = [];
        $divisionalStaffPositions = ["firPositions" => [], "otherPositions" => []];
        $secondmentProgrammePositions = [];

        // Separate positions by type
        foreach ($this->userStaffPositions as $pos) {
            $position_id = $pos['id'];
            $position_division = $pos['divisionId'];
            $position_centerId = $pos['centerId'];
            $position_type = $pos['staffPosition']['type'];

            if ($position_type === 'HQ') {
                $seniorStaffPositions[] = $position_id;
            } elseif ($position_type === 'DIV') {
                if (!empty($position_centerId)) {
                    $divisionalStaffPositions['firPositions'][] = $position_id;
                } elseif ($position_division === $this->divisionId) {
                    $divisionalStaffPositions['otherPositions'][] = $position_id;
                } else {
                    $secondmentProgrammePositions[] = $position_id;
                }
            }
        }

        $nickname_parts = [];

        // Add Senior Staff
        if (!empty($seniorStaffPositions)) {
            $nickname_parts = array_merge($nickname_parts, $seniorStaffPositions);
        }

        // Sort and add Divisional Other Positions
        if (!empty($divisionalStaffPositions['otherPositions']) || !empty($divisionalStaffPositions['firPositions'])) {
            $priority_order = [
                'President' => 0,
                'Chief Executive Officer' => 0,
                'Vice President' => 1,
                'Executive Council' => 1,
                'Executive Assistant' => 2,
                'Director' => 3,
                'Assistant Director' => 4,
                'Administrator' => 5,
                'Coordinator' => 6,
                'Manager' => 6,
                'Webmaster' => 6,
                'Assistant Coordinator' => 7,
                'Assistant Webmaster' => 7,
                'Advisor' => 8,
                'Trainer' => 9
            ];

            $otherPositions = [];
            foreach ($divisionalStaffPositions['otherPositions'] as $id) {
                foreach ($this->userStaffPositions as $p) {
                    if ($p['id'] === $id) {
                        $otherPositions[] = $p;
                        break;
                    }
                }
            }

            usort($otherPositions, function($a, $b) use ($priority_order) {
                $a_name = $a['staffPosition']['name'];
                $b_name = $b['staffPosition']['name'];

                $a_priority = 99;
                $b_priority = 99;
                foreach ($priority_order as $keyword => $priority) {
                    if (stripos($a_name, $keyword) !== false) $a_priority = $priority;
                    if (stripos($b_name, $keyword) !== false) $b_priority = $priority;
                }

                return $a_priority <=> $b_priority ?: strcmp($a['id'], $b['id']);
            });

            if (!empty($otherPositions)) {
                $nickname_parts[] = $otherPositions[0]['id'];
                for ($i = 1; $i < count($otherPositions); $i++) {
                    $parts = explode('-', $otherPositions[$i]['id']);
                    $nickname_parts[] = end($parts);
                }
            }

            // Append FIR positions last and uncut
            if (!empty($divisionalStaffPositions['firPositions'])) {
                $nickname_parts = array_merge($nickname_parts, $divisionalStaffPositions['firPositions']);
            }
        }

        // Add secondment positions
        if (!empty($secondmentProgrammePositions)) {
            $nickname_parts = array_merge($nickname_parts, $secondmentProgrammePositions);
        }

        return implode('/', $nickname_parts) . ' ' . $this->firstName;
    }

    public function getRoles($guilds) {
        $userDepartmentTeams = [];
        $userPositions = [];
        $userDiscordRoles = [];
        $userPositionType = [];


        foreach ($this->userStaffPositions as $pos) {
            $userDepartmentTeams[] = (string)$pos['staffPosition']['departmentTeam']['id'];
            $userPositions[] = preg_replace('/\d+/', '*', $pos['id']);
            if($this->isSupervisor){
                $userPositions[] = "SUP";
            }
            if (!in_array($pos['staffPosition']['type'], $userPositionType)) {
                $userPositionType[] = $pos['staffPosition']['type'];
            }
        }

        if (empty($userDepartmentTeams) || empty($userPositions)) return [];

        foreach ($guilds as $guild){
            $guild = $guild['id'];
            // Get HQ & DIV Staff roles from DB
            foreach ($userPositionType as $type) {
                $query = "SELECT discord_roles.discord_id FROM `position_roles` JOIN discord_roles ON discord_roles.role_id = position_roles.discord_id JOIN discord_guilds ON discord_guilds.id = discord_roles.guild_id WHERE discord_guilds.guild_id = $guild AND position_roles.position = \"$type STAFF\";";
                $query = mysqli_query($this->conn,$query);
                //$row = mysqli_fetch_array($query);
                while($row = mysqli_fetch_assoc($query)) {
                    $userDiscordRoles[$guild][] = $row['discord_id'];
                }
            }

            // First Query: department team roles
            $placeholders1 = implode(',', array_fill(0, count($userDepartmentTeams), '?'));
            $types1 = str_repeat('s', count($userDepartmentTeams));
            $stmt1 = $this->conn->prepare("
                SELECT discord_roles.discord_id
                FROM ivao_department_teams
                JOIN team_roles ON ivao_department_teams.team_id = team_roles.team_id
                JOIN discord_roles ON discord_roles.role_id = team_roles.discord_id
                JOIN discord_guilds ON discord_guilds.id = discord_roles.guild_id
                WHERE ivao_department_teams.team_code IN ($placeholders1) AND discord_guilds.guild_id = $guild
            ");
            $stmt1->bind_param($types1, ...$userDepartmentTeams);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            while ($row = $result1->fetch_assoc()) {
                $userDiscordRoles[$guild][] = $row['discord_id'];
            }
            $stmt1->close();

            // Second Query: position roles
            $placeholders2 = implode(',', array_fill(0, count($userPositions), '?'));
            $types2 = str_repeat('s', count($userPositions));
            $stmt2 = $this->conn->prepare("
                SELECT discord_roles.discord_id
                FROM position_roles
                JOIN discord_roles ON discord_roles.role_id = position_roles.discord_id
                JOIN discord_guilds ON discord_guilds.id = discord_roles.guild_id
                WHERE position_roles.position IN ($placeholders2) AND discord_guilds.guild_id = $guild
            ");
            $stmt2->bind_param($types2, ...$userPositions);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            while ($row = $result2->fetch_assoc()) {
                $userDiscordRoles[$guild][] = $row['discord_id'];
            }
            $stmt2->close();
        }
        return $userDiscordRoles;
    }

    public function getGuilds() {
        $guilds = [];

        $departmentsTeams = array_map(function($item) {
            return $item['staffPosition']['departmentTeam']['id'];
        }, $this->userStaffPositions);
        $departmentIds = array_map(function($item) {
            return $item['staffPosition']['departmentTeam']['department']['id'];
        }, $this->userStaffPositions);
        
        $userPositionDepType = [];

        $isEXEC = false;
        $isDivisionDirector = false;

        foreach($departmentsTeams as $departmentTeam){
            if(str_contains($departmentTeam, "EXEC")){
                $isEXEC = true;
                break;
            } elseif ($departmentTeam == "DIV-DIR"){
                $isDivisionDirector = true;
            }
        }
        if($isEXEC){
            $query = mysqli_query($this->conn,"SELECT * FROM `discord_guilds` LEFT JOIN ivao_departments on ivao_departments.dep_id = discord_guilds.dep");
            while($row = mysqli_fetch_assoc($query)) {
                $guilds[] = ['id' => $row['guild_id'], 'department' => $row['dep_code']];
            }
        } else{
            foreach ($this->userStaffPositions as $pos){
                $userPositionDepType[$pos['staffPosition']['departmentTeam']['department']['id']] = $pos['staffPosition']['type'];
            }
            $query = mysqli_query($this->conn,"SELECT * FROM discord_guilds WHERE discord_guilds.dep IS NULL");
            if (mysqli_num_rows($query) > 0) {
                while($row = mysqli_fetch_array($query)){
                    $guilds[] = ['id' => $row['guild_id'], 'department' => null];
                }
            }
            
            if($isDivisionDirector){
                $stmt1 = $this->conn->prepare("
                    SELECT * FROM discord_guilds
                    JOIN ivao_departments on ivao_departments.dep_id = discord_guilds.dep
                ");
                $stmt1->execute();
                $result1 = $stmt1->get_result();
            } else{
                $placeholders1 = implode(',', array_fill(0, count($departmentIds), '?'));
                $types1 = str_repeat('s', count($departmentIds));
                $stmt1 = $this->conn->prepare("
                    SELECT * FROM discord_guilds
                    JOIN ivao_departments on ivao_departments.dep_id = discord_guilds.dep
                    WHERE ivao_departments.dep_code IN ($placeholders1)
                ");
                $stmt1->bind_param($types1, ...$departmentIds);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
            }
            while ($row = $result1->fetch_assoc()) {
                if($row['staff_type'] == 'HQ'){
                    if($userPositionDepType[$row['dep_code']] == $row['staff_type']){
                        $guilds[] = ['id' => $row['guild_id'], 'department' => $row['dep_code']];
                    }
                } elseif($row['staff_type'] == 'DIV'){
                    if($userPositionDepType[$row['dep_code']] == $row['staff_type'] or $userPositionDepType[$row['dep_code']] == 'HQ' or $isDivisionDirector){
                        $guilds[] = ['id' => $row['guild_id'], 'department' => $row['dep_code']];
                    }
                } else {
                    $guilds[] = ['id' => $row['guild_id'], 'department' => $row['dep_code']];
                }
            }
            $stmt1->close();
        }
        return $guilds;
    }
    
    public function generateNickname($guilds) {
        $nicknames = [];
        $fullNickname = "";
        $isEXEC = false;

        $departmentsTeams = array_map(function($item) {
            return $item['staffPosition']['departmentTeam']['id'];
        }, $this->userStaffPositions);
        foreach($departmentsTeams as $departmentTeam){
            if(str_contains($departmentTeam, "EXEC")){
                $isEXEC = true;
                break;
            }
        }
        foreach($guilds as $guild){
            $userStaffPositions = $this->userStaffPositions;
            if ($guild['department']){
                foreach($userStaffPositions as $key => $value){
                    if ($value['staffPosition']['departmentTeam']['department']['id'] != $guild['department']){
                        if(!str_contains($value['staffPosition']['departmentTeam']['id'], "EXEC")){
                            unset($userStaffPositions[$key]);
                        }                        
                    }
                }
            }

            $seniorStaffPositions = [];
            $divisionalStaffPositions = ["firPositions" => [], "otherPositions" => []];
            $secondmentProgrammePositions = [];

            // Separate positions by type
            foreach ($userStaffPositions as $pos) {
                $position_id = $pos['id'];
                $position_division = $pos['divisionId'];
                $position_centerId = $pos['centerId'];
                $position_type = $pos['staffPosition']['type'];

                if ($position_type === 'HQ') {
                    $seniorStaffPositions[] = $position_id;
                } elseif ($position_type === 'DIV') {
                    if (!empty($position_centerId)) {
                        $divisionalStaffPositions['firPositions'][] = $position_id;
                    } elseif ($position_division === $this->divisionId) {
                        $divisionalStaffPositions['otherPositions'][] = $position_id;
                    } else {
                        $secondmentProgrammePositions[] = $position_id;
                    }
                }
            }

            $nickname_parts = [];

            // Add Senior Staff
            if (!empty($seniorStaffPositions)) {
                $nickname_parts = array_merge($nickname_parts, $seniorStaffPositions);
            }

            // Sort and add Divisional Other Positions
            if (!empty($divisionalStaffPositions['otherPositions']) || !empty($divisionalStaffPositions['firPositions'])) {
                $priority_order = [
                    'President' => 0,
                    'Chief Executive Officer' => 0,
                    'Vice President' => 1,
                    'Executive Council' => 1,
                    'Executive Assistant' => 2,
                    'Director' => 3,
                    'Assistant Director' => 4,
                    'Administrator' => 5,
                    'Coordinator' => 6,
                    'Manager' => 6,
                    'Webmaster' => 6,
                    'Assistant Coordinator' => 7,
                    'Assistant Webmaster' => 7,
                    'Advisor' => 8,
                    'Trainer' => 9
                ];

                $otherPositions = [];
                foreach ($divisionalStaffPositions['otherPositions'] as $id) {
                    foreach ($userStaffPositions as $p) {
                        if ($p['id'] === $id) {
                            $otherPositions[] = $p;
                            break;
                        }
                    }
                }

                usort($otherPositions, function($a, $b) use ($priority_order) {
                    $a_name = $a['staffPosition']['name'];
                    $b_name = $b['staffPosition']['name'];

                    $a_priority = 99;
                    $b_priority = 99;
                    foreach ($priority_order as $keyword => $priority) {
                        if (stripos($a_name, $keyword) !== false) $a_priority = $priority;
                        if (stripos($b_name, $keyword) !== false) $b_priority = $priority;
                    }

                    return $a_priority <=> $b_priority ?: strcmp($a['id'], $b['id']);
                });

                if (!empty($otherPositions)) {
                    $nickname_parts[] = $otherPositions[0]['id'];
                    for ($i = 1; $i < count($otherPositions); $i++) {
                        $parts = explode('-', $otherPositions[$i]['id']);
                        $nickname_parts[] = end($parts);
                    }
                }

                // Append FIR positions last and uncut
                if (!empty($divisionalStaffPositions['firPositions'])) {
                    $nickname_parts = array_merge($nickname_parts, $divisionalStaffPositions['firPositions']);
                }
            }

            // Add secondment positions
            if (!empty($secondmentProgrammePositions)) {
                $nickname_parts = array_merge($nickname_parts, $secondmentProgrammePositions);
            }

            if($this->isSupervisor and !$guild['department']){
                if(!$isEXEC){
                    $nicknames[$guild['id']] = '*' . implode('/', $nickname_parts) . ' ' . $this->firstName;
                    $nicknames["fullNickname"] = '*' . implode('/', $nickname_parts) . ' ' . $this->firstName;
                } else{
                    $nicknames[$guild['id']] = implode('/', $nickname_parts) . ' ' . $this->firstName;
                    $nicknames["fullNickname"] = implode('/', $nickname_parts) . ' ' . $this->firstName;
                }
            } elseif(!$guild['department']){
                $nicknames["fullNickname"] = implode('/', $nickname_parts) . ' ' . $this->firstName;
                $nicknames[$guild['id']] = implode('/', $nickname_parts) . ' ' . $this->firstName;
            } elseif($guild['department']) {
                $nicknames[$guild['id']] = implode('/', $nickname_parts) . ' ' . $this->firstName;
            }
            
        }

        return $nicknames;
    }

}

function exchangeTempToken($tempToken, $conn) {
    $query = "SELECT vid, name, division, positions, supervisor FROM temp_users WHERE token = '".$tempToken."' ORDER BY request_date DESC LIMIT 1";
    $query = mysqli_query($conn,$query);
    $row = mysqli_fetch_array($query);

    return $row;
}

function exchangeDiscordToken($discord_token_url,$data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $discord_token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if (isset($result['error']) or is_null($result)){      
        die('Wrong Discord Token. Please try again later!');
    };
    return $result;
}

function getDiscordUserID($discord_user_url, $access_token) {
    $headers = array(
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/x-www-form-urlencoded"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $discord_user_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    return $result['id'];
}

function checkUserInDB($vid, $user_id, $conn) {
    $query = "SELECT * FROM `discord_users` WHERE vid = $vid";
    $query = mysqli_query($conn,$query);
    $row = mysqli_fetch_array($query);
    if (!is_null($row)){
        if ($row["discord_id"] != $user_id and $user_id != null){
            // do some actions
            die("The user is already registered! Please Leave the server from your first account or contact admin for assistance.");
        } elseif ($row["vid"] != $vid){
            die("This Discord account is already registered! Please contact admin for assistance.");
        }
    }
}

function checkUserInGuild($guild_url, $headers){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $guild_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function addUserInGuild($access_token, $nicknames, $roles, $user_id, $discord_server_id, $discord_bot_token, $conn, $vid, $name, $division, $positions, $supervisor = false, $refresh_token = null, $expire_token = null, $encryption_key = null) {
    if($supervisor){
        $supervisor = 1;
    } else{
        $supervisor = 0;
    }

    $data = json_encode(array(
        "access_token" => $access_token,
        "nick" => $nicknames[$discord_server_id],
        "roles" => $roles)
    );
    $guild_url = "https://discord.com/api/guilds/".$discord_server_id."/members/" . $user_id;
    //$guild_url = "https://discord.com/api/guilds/".discord_server_id."/members/471001485975486476";

    $headers = array(
        "Content-Type: application/json", 
        "Authorization: Bot " . $discord_bot_token
    );
    if ($encryption_key){
        $encrypted_access_token = encryptToken($access_token, $encryption_key);
        if ($refresh_token){
            $encrypted_refresh_token = encryptToken($refresh_token, $encryption_key);
        }
    }
    // Check if discord user is already joined server (error code 10007 = Unknown Member)
    $userGuildObject = checkUserInGuild($guild_url, $headers);
    
    if (isset($userGuildObject['code']) AND $userGuildObject['code'] == 10007) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $guild_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        // If the user is not in the DB then add him 
        // If the user is in the DB then Update User's info
        $query = "SELECT * FROM `discord_users` WHERE discord_id = $user_id";
        $query = mysqli_query($conn,$query);
        $row = mysqli_fetch_array($query);
        if (is_null($row)){
            $query = "INSERT INTO discord_users VALUES
		        (NULL,
		        ".$user_id.",
		        '".$vid."',
		        '".$name."',
                '".$division."',
		        '".$nicknames["fullNickname"]."',
                '".$positions."',
                '".$supervisor."',
                '".$encrypted_access_token."',
                '".$encrypted_refresh_token."',
                '".date("Y-m-d H:i:s")."',
                '".date("Y-m-d H:i:s")."',
		        '".$expire_token."')";
	        mysqli_query($conn,$query);
        } else {
            $query = "UPDATE discord_users SET 
                vid = '".$vid."', 
                name = '".$name."', 
                division = '".$division."', 
                nickname = '".$nicknames["fullNickname"]."',
                supervisor = '".$supervisor."',
                positions = '".$positions."'
                WHERE discord_id = ".$user_id." ORDER BY joined_at ASC LIMIT 1";
            mysqli_query($conn,$query);
        }
    } else {
        // Delete unwanted current roles
        removeRolesFromUser($guild_url, $headers, $roles, $userGuildObject['roles'], $conn);

        // Add Roles
        addRolesToUser($guild_url, $headers, $roles);

        // Change nickname
        setUserNickname($guild_url, $headers, $nicknames[$discord_server_id]);

        // If the user is not in the DB then add him 
        // If the user is in the DB then Update User's info
        $query = "SELECT * FROM `discord_users` WHERE discord_id = $user_id";
        $query = mysqli_query($conn,$query);
        $row = mysqli_fetch_array($query);
        if (is_null($row)){
            $query = "INSERT INTO discord_users VALUES
		        (NULL,
		        ".$user_id.",
		        '".$vid."',
		        '".$name."',
                '".$division."',
		        '".$nicknames["fullNickname"]."',
                '".$positions."',
                '".$supervisor."',
                '".$encrypted_access_token."',
                '".$encrypted_refresh_token."',
                '".date("Y-m-d H:i:s")."',
                '".date("Y-m-d H:i:s")."',
		        '".$expire_token."')";
	        mysqli_query($conn,$query);
        } else {
            $query = "UPDATE discord_users SET 
                vid = '".$vid."', 
                name = '".$name."', 
                division = '".$division."', 
                nickname = '".$nicknames["fullNickname"]."',
                discord_access_token = '".$encrypted_access_token."', 
                discord_refresh_token = '".$encrypted_refresh_token."', 
                discord_token_expires = '".$expire_token."',
                supervisor = '".$supervisor."',
                positions = '".$positions."'
                WHERE discord_id = ".$user_id." ORDER BY joined_at ASC LIMIT 1";
            mysqli_query($conn,$query);
        }
    };
}

function deleteUserFromGuild($guild_url, $headers){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $guild_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
}

function removeRolesFromUser($guild_url, $headers, $generated_roles, $current_roles = null, $conn){
    if ($generated_roles){
        if ($current_roles != null){
            $checked_roles = [];
            foreach($current_roles as $current_role){
                if(!in_array($current_role, $generated_roles)){
                    $checked_roles[] = $current_role;
                }
            }
            if ($checked_roles){
                $placeholders = implode(',', array_fill(0, count($checked_roles), '?'));
                $types = str_repeat('s', count($checked_roles));
                $stmt = $conn->prepare("
                    SELECT * 
                    FROM discord_roles
                    WHERE discord_roles.discord_id IN ($placeholders)
                ");
                $stmt->bind_param($types, ...$checked_roles);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $discord_id = $row['discord_id'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "$guild_url/roles/$discord_id");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    $response = curl_exec($ch);
                    curl_close($ch);
                }
                $stmt->close();
                
            }
            
        } else{
            foreach ($generated_roles as $role) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "$guild_url/roles/$role");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $response = curl_exec($ch);
                curl_close($ch);
            }
            
        }
    }
}

function addRolesToUser($guild_url, $headers, $roles){
    if($roles){
        foreach ($roles as $role){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$guild_url/roles/$role");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
        };
    }
}

function setUserNickname($guild_url, $headers, $nick = null){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $guild_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("nick" => $nick)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);


}

function encryptToken($token, $ENCRYPTION_KEY) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($token, 'AES-256-CBC', $ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptToken($encryptedToken, $ENCRYPTION_KEY) {
    $data = base64_decode($encryptedToken);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $ENCRYPTION_KEY, 0, $iv);
}

function getIvaoUser($openid_url, $ivao_client_id, $ivao_client_secret, $vid){
    $openid_result = file_get_contents($openid_url);
    $openid_data = json_decode($openid_result, true);
    $ivao_token_req_data = [
        'grant_type' => 'client_credentials',
        'client_id' => $ivao_client_id,
        'client_secret' => $ivao_client_secret,
        'scope' => 'tracker'
    ];
    $ivao_token_options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($ivao_token_req_data)
        ]
    ];
    $ivao_token_context  = stream_context_create($ivao_token_options);
    $ivao_token_result = file_get_contents($openid_data['token_endpoint'], false, $ivao_token_context);
    //if ($user_result === FALSE) {
    //    /* Handle error */
    //    die('Error while getting user data from IVAO');
    //}
    $ivao_token_res_data = json_decode($ivao_token_result, true);
    $ivao_access_token = $ivao_token_res_data['access_token'];
    $ivao_user_url = "https://api.ivao.aero/v2/users/$vid";
    $ivao_user_options = [
        'http' => [
            'header'  => "Authorization: Bearer $ivao_access_token\r\n",
            'method'  => 'GET',
        ]
    ];
    $ivao_context  = stream_context_create($ivao_user_options);
    $ivao_result = file_get_contents($ivao_user_url, false, $ivao_context);
    $ivao_res_data = json_decode($ivao_result, true);

    return $ivao_res_data;
}

function getNotListedGuilds($guilds, $conn){
    $notListedGuilds = [];

    $guilds = array_map(function($item) {
        return $item['id'];
    }, $guilds);

    $placeholders = implode(',', array_fill(0, count($guilds), '?'));
    $types = str_repeat('s', count($guilds));
    $stmt = $conn->prepare("
        SELECT * 
        FROM discord_guilds
        WHERE discord_guilds.guild_id NOT IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$guilds);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()){
        $notListedGuilds[] = $row['guild_id'];
    }

    return $notListedGuilds;
}
?>