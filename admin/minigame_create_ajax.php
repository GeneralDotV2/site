<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    $json_response = array();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);

        if ($db) {
            if (!empty($_POST['minigame_name']) && !empty($_POST['minigame_developer'])) {
                $steamAPI = new steam_webapi($api_key1);
                $steamIDconverter = new SteamID();

                if (is_numeric($_POST['minigame_developer'])) {
                    $steamIDconverter->setSteamID($_POST['minigame_developer']);
                    $developerSteamID = $steamIDconverter->getSteamID64();
                } else if (stristr($_POST['minigame_developer'], 'steamcommunity.com/id/')) {
                    $customUrl = rtrim(cut_str($_POST['minigame_developer'], 'steamcommunity.com/id/'), '/');
                    $vanityURLResult = $steamAPI->ResolveVanityURL($customUrl);

                    if (!empty($vanityURLResult) && $vanityURLResult['response']['success'] == 1) {
                        $steamIDconverter->setSteamID($vanityURLResult['response']['steamid']);
                        $developerSteamID = $steamIDconverter->getSteamID64();
                    } else {
                        throw new Exception('Failed to resolve vanity URL!');
                    }
                } else {
                    throw new Exception('Bad steam ID!');
                }

                $developer_user_details = cached_query(
                    'minigame_user_details' . $developerSteamID,
                    'SELECT
                            `user_id64`,
                            `user_id32`,
                            `user_name`,
                            `user_avatar`,
                            `user_avatar_medium`,
                            `user_avatar_large`
                    FROM `gds_users`
                    WHERE `user_id64` = ?
                    LIMIT 0,1;',
                    's',
                    $developerSteamID,
                    5
                );

                if (empty($developer_user_details)) {
                    $developer_user_details_temp = $steamAPI->GetPlayerSummariesV2($steamIDconverter->getSteamID64());

                    if (!empty($developer_user_details_temp)) {
                        $developer_user_details[0]['user_id64'] = $steamIDconverter->getSteamID64();
                        $developer_user_details[0]['user_id32'] = $steamIDconverter->getSteamID32();
                        $developer_user_details[0]['user_name'] = $developer_user_details_temp['response']['players'][0]['personaname'];
                        $developer_user_details[0]['user_avatar'] = $developer_user_details_temp['response']['players'][0]['avatar'];
                        $developer_user_details[0]['user_avatar_medium'] = $developer_user_details_temp['response']['players'][0]['avatarmedium'];
                        $developer_user_details[0]['user_avatar_large'] = $developer_user_details_temp['response']['players'][0]['avatarfull'];


                        $db->q(
                            'INSERT INTO `gds_users`
                                (`user_id64`, `user_id32`, `user_name`, `user_avatar`, `user_avatar_medium`, `user_avatar_large`)
                                VALUES (?, ?, ?, ?, ?, ?)',
                            'ssssss',
                            array(
                                $developer_user_details[0]['user_id64'],
                                $developer_user_details[0]['user_id32'],
                                $developer_user_details[0]['user_name'],
                                $developer_user_details[0]['user_avatar'],
                                $developer_user_details[0]['user_avatar_medium'],
                                $developer_user_details[0]['user_avatar_large']
                            )
                        );

                        $memcache->set('minigame_user_details' . $developerSteamID, $developer_user_details, 0, 15);
                    }
                }


                if (!empty($_POST['minigame_steam_group']) && stristr($_POST['minigame_steam_group'], 'steamcommunity.com/groups/')) {
                    $minigameGroup = htmlentities(rtrim(cut_str($_POST['minigame_steam_group'], 'groups/'), '/'));
                } else {
                    $minigameGroup = NULL;
                }

                $minigameName = htmlentities($_POST['minigame_name']);


                $insertSQL = $db->q(
                    'INSERT INTO `stat_highscore_minigames` (`minigameID`, `minigameName`, `minigameDeveloper`, `minigameSteamGroup`)
                        VALUES (?, ?, ?, ?);',
                    'ssss', //STUPID x64 windows PHP is actually x86
                    md5($minigameName . time()), $minigameName, $developerSteamID, $minigameGroup
                );

                if ($insertSQL) {
                    $json_response['result'] = 'Success! Mini Game added to DB and under the developer\'s account.';
                } else {
                    throw new Exception('Mini Game not added to DB!');
                }
            } else {
                throw new Exception('Missing name or developer!');
            }
        } else {
            throw new Exception('No DB!');
        }
    } else {
        throw new Exception('Not logged in!');
    }
} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage() . ' || Contact getdotastats.com';
    echo utf8_encode(json_encode($json_response));
}