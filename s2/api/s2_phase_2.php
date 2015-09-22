<?php
require_once('./functions.php');
require_once('../../global_functions.php');
require_once('../../connections/parameters.php');

try {
    $s2_response = array();

    if (!isset($_POST['payload']) || empty($_POST['payload'])) {
        throw new Exception('Missing payload!');
    }

    $preGameAuthPayload = $_POST['payload'];
    $preGameAuthPayloadJSON = json_decode($preGameAuthPayload, 1);

    if (!isset($preGameAuthPayloadJSON) || empty($preGameAuthPayloadJSON)) {
        throw new Exception('Payload not JSON!');
    }

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] < $currentSchemaVersionPhase2) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (
        !isset($preGameAuthPayloadJSON['authKey']) || empty($preGameAuthPayloadJSON['authKey']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID']) ||
        !isset($preGameAuthPayloadJSON['modIdentifier']) || empty($preGameAuthPayloadJSON['modIdentifier']) ||
        !isset($preGameAuthPayloadJSON['players']) || empty($preGameAuthPayloadJSON['players'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $numPlayers = count($preGameAuthPayloadJSON['players']);
    $matchID = $preGameAuthPayloadJSON['matchID'];
    $modIdentifier = $preGameAuthPayloadJSON['modIdentifier'];
    $authKey = $preGameAuthPayloadJSON['authKey'];

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    //Check if the modIdentifier is valid
    $modIdentifierCheck = cached_query(
        's2_mod_identifier_check' . $modIdentifier,
        'SELECT
                `mod_id`,
                `steam_id64`,
                `mod_name`,
                `mod_description`,
                `mod_workshop_link`,
                `mod_steam_group`,
                `mod_active`,
                `mod_rejected`,
                `mod_rejected_reason`,
                `mod_maps`,
                `mod_max_players`,
                `mod_options_enabled`,
                `mod_options`,
                `date_recorded`
            FROM `mod_list`
            WHERE `mod_identifier` = ?
            LIMIT 0,1;',
        's',
        $modIdentifier,
        15
    );

    if (empty($modIdentifierCheck)) {
        throw new Exception('Invalid modID!');
    }

    $modID = $modIdentifierCheck[0]['mod_id'];

    //MATCH CHECK
    {
        $matchDetails = cached_query(
            's2_match_query_' . $matchID,
            'SELECT
                `matchID`,
                `matchAuthKey`,
                `modID`,
                `matchHostSteamID32`,
                `matchPhaseID`,
                `isDedicated`,
                `matchMapName`,
                `numPlayers`,
                `numRounds`,
                `matchDuration`,
                `schemaVersion`,
                `dateUpdated`,
                `dateRecorded`
            FROM `s2_match`
            WHERE `matchID` = ? AND `modID` = ? AND `matchAuthKey` = ?
            LIMIT 0,1;',
            'sss',
            array(
                $matchID,
                $modID,
                $authKey
            ),
            5
        );
    }

    if (empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //MATCH DETAILS
    {
        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchID`, `matchPhaseID`)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                  `matchPhaseID` = VALUES(`matchPhaseID`);',
            'si',
            array(
                $matchID,
                2
            )
        );
    }

    //PLAYERS DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['players'])) {
            $steamID_manipulator = new SteamID();

            foreach ($preGameAuthPayloadJSON['players'] as $key => $value) {
                $steamID_manipulator->setSteamID($value['steamID32']);

                $steamID32 = $steamID_manipulator->getSteamID32();
                $steamID64 = $steamID_manipulator->getSteamID64();

                $db->q(
                    'INSERT INTO `s2_match_players`(`matchID`, `roundID`, `modID`, `steamID32`, `steamID64`, `connectionState`)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `connectionState` = VALUES(`connectionState`);',
                    'sisssi',
                    array(
                        $matchID,
                        1,
                        $modID,
                        $steamID32,
                        $steamID64,
                        $value['connectionState']
                    )
                );

                $db->q('INSERT INTO `s2_match_players_name`
                                (`steamID32`, `steamID64`, `playerName`)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                  `playerName` = VALUES(`playerName`);',
                    'sss',
                    array(
                        $steamID32,
                        $steamID64,
                        $value['playerName']
                    )
                );
            }
        }
    }

    //FLAGS
    {
        if (!empty($preGameAuthPayloadJSON['flags'])) {
            foreach ($preGameAuthPayloadJSON['flags'] as $key => $value) {
                $db->q(
                    'INSERT INTO `s2_match_flags`(`matchID`, `modID`, `flagName`, `flagValue`)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                          `flagName` = VALUES(`flagName`),
                          `flagValue` = VALUES(`flagValue`);',
                    'ssss',
                    array(
                        $matchID,
                        $modID,
                        $key,
                        $value
                    )
                );
            }
        }
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
        $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;

        $irc_message = new irc_message($webhook_gds_site_announce);

        $message = array(
            array(
                '[',
                $irc_message->colour_generator('bold'),
                $irc_message->colour_generator('blue'),
                'PHASE2',
                $irc_message->colour_generator(NULL),
                $irc_message->colour_generator('bold'),
                ']',
            ),
            array(
                $irc_message->colour_generator('red'),
                '[' . $modIdentifierCheck[0]['mod_name'] . ']',
                $irc_message->colour_generator(NULL),
            ),
            array(' || http://getdotastats.com/#s2__match?id=' . $matchID),
        );

        $message = $irc_message->combine_message($message);
        $irc_message->post_message($message, array('localDev' => $localDev));
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
        $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    $s2_response['schemaVersion'] = $currentSchemaVersionPhase2;
} finally {
    if (isset($memcache)) $memcache->close();
    if (!isset($s2_response)) $s2_response = array('error' => 'Unknown exception');
}

try {
    header('Content-Type: application/json');
    echo utf8_encode(json_encode($s2_response));
} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($s2_response));
}