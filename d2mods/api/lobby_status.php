<?php
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

//Get the lobby details of a specific lobby

try {
    $lobbyID = !empty($_GET['lid']) && is_numeric($_GET['lid'])
        ? $_GET['lid']
        : NULL;

    if (!empty($lobbyID)) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"
        $lobbyStatus = $memcache->get('api_d2mods_lobby_status' . $lobbyID);
        if (!$lobbyStatus) {
            $lobbyStatus = array();
            $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, false);
            if ($db) {
                $lobbyDetails = $db->q(
                    'SELECT
                            ll.`lobby_id`,
                            ll.`mod_id`,
                            ll.`workshop_id`,
                            ll.`lobby_ttl`,
                            ll.`lobby_min_players`,
                            ll.`lobby_max_players`,
                            ll.`lobby_public`,
                            ll.`lobby_leader`,
                            ll.`lobby_active`,
                            ll.`lobby_hosted`,
                            ll.`lobby_pass`,
                            ll.`lobby_map`
                        FROM `lobby_list` ll
                        WHERE ll.`lobby_active` = 1 AND ll.`lobby_id` = ?
                        ORDER BY `lobby_id` DESC
                        LIMIT 0,1;',
                    'i',
                    $lobbyID
                );

                if (!empty($lobbyDetails)) {
                    $lobbyDetails = $lobbyDetails[0];

                    $lobbyStatus['lobby_id'] = $lobbyDetails['lobby_id'];
                    $lobbyStatus['mod_id'] = $lobbyDetails['mod_id'];
                    $lobbyStatus['workshop_id'] = $lobbyDetails['workshop_id'];
                    $lobbyStatus['lobby_max_players'] = $lobbyDetails['lobby_max_players'];
                    $lobbyStatus['lobby_leader'] = $lobbyDetails['lobby_leader'];
                    $lobbyStatus['lobby_hosted'] = $lobbyDetails['lobby_hosted'];
                    $lobbyStatus['lobby_pass'] = $lobbyDetails['lobby_pass'];
                    $lobbyStatus['lobby_map'] = $lobbyDetails['lobby_map'];
                } else {
                    $lobbyStatus['error'] = 'Not in active lobby!';
                }
            } else {
                $lobbyStatus['error'] = 'No DB connection!';
            }

            $memcache->set('api_d2mods_lobby_status' . $lobbyID, $lobbyStatus, 0, 1);
        }
        $memcache->close();
    } else {
        $lobbyStatus['error'] = 'Invalid user id!';
    }

} catch (Exception $e) {
    unset($lobbyStatus);
    $lobbyStatus['error'] = 'Contact getdotastats.com - Caught Exception: ' . $e->getMessage();
}

echo utf8_encode(json_encode($lobbyStatus));