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

    if (!isset($preGameAuthPayloadJSON['schemaVersion']) || empty($preGameAuthPayloadJSON['schemaVersion']) || $preGameAuthPayloadJSON['schemaVersion'] != $currentSchemaVersion) { //CHECK THAT SCHEMA VERSION IS CURRENT
        throw new Exception('Schema version out of date!');
    }

    if (!isset($preGameAuthPayloadJSON['gamePhase']) || empty($preGameAuthPayloadJSON['gamePhase']) || $preGameAuthPayloadJSON['gamePhase'] != 3) { //CHECK THAT gamePhase IS CORRECT
        throw new Exception('Wrong endpoint for this phase!');
    }

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    if (
        !isset($preGameAuthPayloadJSON['authKey']) || empty($preGameAuthPayloadJSON['authKey']) ||
        !isset($preGameAuthPayloadJSON['matchID']) || empty($preGameAuthPayloadJSON['matchID']) || !is_numeric($preGameAuthPayloadJSON['matchID']) ||
        !isset($preGameAuthPayloadJSON['modID']) || empty($preGameAuthPayloadJSON['modID']) ||
        !isset($preGameAuthPayloadJSON['rounds']) || empty($preGameAuthPayloadJSON['rounds'])
    ) {
        throw new Exception('Payload missing fields!');
    }

    $matchID = $preGameAuthPayloadJSON['matchID'];
    $modID = $preGameAuthPayloadJSON['modID'];
    $authKey = $preGameAuthPayloadJSON['authKey'];
    $numRounds = count($preGameAuthPayloadJSON['rounds']);
    $numPlayers = count($preGameAuthPayloadJSON['rounds'][($numRounds - 1)]['players']);

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

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
                `numPlayers`,
                `matchWinningTeamID`,
                `matchDuration`,
                `schemaVersion`,
                `dateUpdated`,
                `dateRecorded`
            FROM `s2_match`
            WHERE `matchID` = ? AND `modID` = ? AND `matchAuthKey` = ?;',
            'sss',
            array(
                $matchID,
                $modID,
                $authKey
            ),
            5
        );
    }

    if (!isset($matchDetails) || empty($matchDetails)) {
        throw new Exception('No match found matching parameters!');
    }

    //MATCH DETAILS
    {
        $sqlResult = $db->q(
            'INSERT INTO `s2_match`(`matchID`, `matchPhaseID`, `numPlayers`, `numRounds`, `matchWinningTeamID`, `matchDuration`)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  `matchPhaseID` = VALUES(`matchPhaseID`),
                  `numPlayers` = VALUES(`numPlayers`),
                  `numRounds` = VALUES(`numRounds`),
                  `matchWinningTeamID` = VALUES(`matchWinningTeamID`),
                  `matchDuration` = VALUES(`matchDuration`);',
            'siiiii',
            array(
                $matchID,
                $preGameAuthPayloadJSON['gamePhase'],
                $numPlayers,
                $numRounds,
                $preGameAuthPayloadJSON['winningTeam'],
                $preGameAuthPayloadJSON['gameDuration']
            )
        );
    }

    //PLAYERS DETAILS
    {
        if (!empty($preGameAuthPayloadJSON['rounds'])) {
            $steamID_manipulator = new SteamID();

            foreach ($preGameAuthPayloadJSON['rounds'] as $key => $value) {
                if (!empty($value['players'])) {
                    foreach ($value['players'] as $key2 => $value2) {
                        $steamID_manipulator->setSteamID($value2['steamID32']);

                        $steamID32 = $steamID_manipulator->getSteamID32();
                        $steamID64 = $steamID_manipulator->getSteamID64();

                        $db->q(
                            'INSERT INTO `s2_match_players`(`matchID`, `roundID`, `modID`, `steamID32`, `steamID64`, `playerName`, `teamID`, `slotID`, `heroID`, `connectionState`)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                  `playerName` = VALUES(`playerName`),
                                  `teamID` = VALUES(`teamID`),
                                  `slotID` = VALUES(`slotID`),
                                  `heroID` = VALUES(`heroID`),
                                  `connectionState` = VALUES(`connectionState`);',
                            'sissssiiii',
                            array(
                                $matchID,
                                ($key + 1),
                                $preGameAuthPayloadJSON['modID'],
                                $steamID32,
                                $steamID64,
                                $value2['playerName'],
                                $value2['teamID'],
                                $value2['slotID'],
                                $value2['heroID'],
                                $value2['connectionState']
                            )
                        );
                    }
                }
            }
        }
    }

    if (!empty($sqlResult)) {
        $s2_response['result'] = 1;
    } else {
        //SOMETHING FUNKY HAPPENED
        $s2_response['result'] = 0;
        $s2_response['error'] = 'Unknown error!';
    }

} catch (Exception $e) {
    unset($s2_response);
    $s2_response['result'] = 0;
    $s2_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
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