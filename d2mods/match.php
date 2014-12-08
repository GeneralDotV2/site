<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

$start = time();

if (!isset($_SESSION)) {
    session_start();
}

try {
    if (!function_exists('dota2TeamName')) {
        function dota2TeamName($teamID)
        {
            switch ($teamID) {
                case -1:
                    $teamName = 'No Winner';
                    break;
                case 2:
                    $teamName = 'Radiant';
                    break;
                case 3:
                    $teamName = 'Dire';
                    break;
                default:
                    $teamName = '#' . $teamID;
                    break;
            }
            return $teamName;
        }
    }

    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    $db->q('SET NAMES utf8;');

    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $matchID = empty($_GET['id']) || strlen($_GET['id']) != 32
            ? NULL
            : $_GET['id'];


        if (!empty($matchID)) {
            $matchDetails = $db->q(
                'SELECT
                      mmo.`match_id`,
                      mmo.`mod_id`,
                      mmo.`message_id`,
                      mmo.`match_duration`,
                      mmo.`match_num_players`,
                      mmo.`match_winning_team`,
                      mmo.`match_recorded`,

                      ml.`mod_id`,
                      ml.`mod_identifier`,
                      ml.`mod_name`,
                      ml.`mod_description`,
                      ml.`mod_workshop_link`,
                      ml.`mod_steam_group`,
                      ml.`mod_active`
                    FROM `mod_match_overview` mmo
                    LEFT JOIN `mod_list` ml ON mmo.`mod_id` = ml.`mod_identifier`
                    WHERE mmo.`match_id` = ?
                    LIMIT 0,1;',
                's',
                $matchID
            );

            if (!empty($matchDetails)) {
                $matchPlayerDetails = $db->q(
                    'SELECT
                          mmp.`match_id`,
                          mmp.`mod_id`,
                          mmp.`player_sid32`,
                          mmp.`player_sid64`,
                          mmp.`isBot`,
                          mmp.`connection_status`,
                          mmp.`player_won`,
                          mmp.`player_name`,
                          mmp.`player_round_id`,
                          mmp.`player_team_id`,
                          mmp.`player_slot_id`,

                          gcs.`cs_id`,
                          gcs.`cs_string`,
                          gcs.`cs_name`
                        FROM `mod_match_players` mmp
                        LEFT JOIN `game_connection_status` gcs
                          ON mmp.`connection_status` = gcs.`cs_id`
                        WHERE mmp.`match_id` = ?
                        ORDER BY mmp.`player_round_id`, mmp.`player_team_id`, mmp.`player_slot_id`;',
                    's',
                    $matchID
                );

                $matchHeroDetails = $db->q(
                    'SELECT
                          mmh.`match_id`,
                          mmh.`mod_id`,
                          mmh.`player_round_id`,
                          mmh.`player_team_id`,
                          mmh.`player_slot_id`,
                          mmh.`player_sid32`,
                          mmh.`hero_id`,
                          mmh.`hero_won`,
                          mmh.`hero_level`,
                          mmh.`hero_kills`,
                          mmh.`hero_deaths`,
                          mmh.`hero_assists`,
                          mmh.`hero_gold`,
                          mmh.`hero_lasthits`,
                          mmh.`hero_denies`,
                          mmh.`hero_gold_spent_buyback`,
                          mmh.`hero_gold_spent_consumables`,
                          mmh.`hero_gold_spent_items`,
                          mmh.`hero_gold_spent_support`,
                          mmh.`hero_num_purchased_consumables`,
                          mmh.`hero_num_purchased_items`,
                          mmh.`hero_stun_amount`,
                          mmh.`hero_total_earned_gold`,
                          mmh.`hero_total_earned_xp`
                        FROM `mod_match_heroes` mmh
                        WHERE mmh.`match_id` = ?
                        ORDER BY mmh.`player_round_id`, mmh.`player_sid32`;',
                    's',
                    $matchID
                );

                $matchDetailsSorted = array();

                if (!empty($matchPlayerDetails)) {
                    foreach ($matchPlayerDetails as $mh_key => $mh_value) {
                        foreach ($mh_value as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']][$mh_key2] = $mh_value2;
                        }
                    }
                }

                if (!empty($matchHeroDetails)) {
                    foreach ($matchHeroDetails as $mh_key => $mh_value) {
                        foreach ($mh_value as $mh_key2 => $mh_value2) {
                            $matchDetailsSorted[$mh_value['player_round_id']][$mh_value['player_team_id']][$mh_value['player_slot_id']][$mh_key2] = $mh_value2;
                        }
                    }
                }

                /*echo '<pre>';
                print_r($matchDetailsSorted);
                echo '</pre>';
                exit();*/

                echo '<h2><a class="nav-clickable" href="#d2mods__stats?id=' . $matchDetails[0]['mod_id'] . '">' . $matchDetails[0]['mod_name'] . '</a> <small>' . $matchID . '</small></h2>';

                $sg = !empty($matchDetails[0]['mod_steam_group'])
                    ? '<a href="http://steamcommunity.com/groups/' . $matchDetails[0]['mod_steam_group'] . '" target="_new">Steam Group</a>'
                    : 'Steam Group';

                $wg = !empty($matchDetails[0]['mod_workshop_link'])
                    ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $matchDetails[0]['mod_workshop_link'] . '" target="_new">Workshop</a>'
                    : 'Workshop';

                $schemaLink = !empty($matchDetails[0]['message_id'])
                    ? '<a href=" ./d2mods/?custom_match=' . $matchDetails[0]['message_id'] . '" target="_new">' . $matchDetails[0]['message_id'] . '</a>'
                    : 'N/A';

                echo '<div class="container">
                        <div class="col-sm-7">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <tr>
                                        <th>Links</th>
                                        <td>' . $wg . ' || ' . $sg . '</td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>' . $matchDetails[0]['mod_description'] . '</td>
                                    </tr>
                                    <tr>
                                        <th>Schema</th>
                                        <td>' . $schemaLink . '</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                      </div>';

                echo '<div class="table-responsive">
		                    <table class="table table-condensed">
		                        <tr class="warning">
		                            <th class="col-sm-8 bashful">&nbsp;</th>
		                            <th class="col-sm-1 text-center">Players</th>
		                            <th class="col-sm-1 text-center">Duration</th>
		                            <th class="col-sm-2 text-center">Ended</th>
		                        </tr>
		                        <tr>
		                            <td class="bashful">&nbsp;</td>
		                            <td class="text-center">' . $matchDetails[0]['match_num_players'] . '</td>
		                            <td class="text-center">' . number_format($matchDetails[0]['match_duration'] / 60) . ' mins</td>
		                            <td class="text-right">' . relative_time($matchDetails[0]['match_recorded']) . '</td>
		                        </tr>
		                    </table>
		                </div>';

                echo '<div><h2><small>Winning Team:</small> ' . dota2TeamName($matchDetails[0]['match_winning_team']) . '</h2></div>';

                $roundCount = count($matchDetailsSorted);
                if (!empty($matchDetailsSorted)) {
                    foreach ($matchDetailsSorted as $round_key => $round_value) {
                        if ($roundCount > 1) {
                            echo '<div><h3><small>Round:</small> ' . ($round_key + 1) . ' of ' . $roundCount . '</h3></div>';
                        }

                        foreach ($round_value as $team_key => $team_value) {

                            $firstTeamID = 0;
                            foreach($team_value as $player_key => $player_value){
                                if(isset($player_value['player_team_id'])){
                                    $firstTeamID = $player_value['player_team_id'];
                                    break;
                                }
                            }

                            $teamName = dota2TeamName($firstTeamID);
                            echo '<h4><small>Team:</small> ' . $teamName . '</h4>';

                            echo '<div class="table-responsive">
		                            <table class="table table-striped table-hover">';

                            echo '<tr>
                                        <th class="col-sm-1">&nbsp;</th>
                                        <th>Player</th>
                                        <th class="col-sm-1 text-center">Connection</th>
                                        <th class="col-sm-1 text-center">Bot?</th>
                                        <th class="col-sm-1 text-center">lvl</th>
                                        <th class="col-sm-2 text-center">K / A / D <span class="glyphicon glyphicon-question-sign" title="Kills / Assists / Deaths"></span></th>
                                        <th class="col-sm-2 text-center">LH / D <span class="glyphicon glyphicon-question-sign" title="Last Hits / Denies"></span></th>
                                    </tr>';

                            foreach ($team_value as $player_key => $player_value) {


                                $heroID = !empty($player_value['hero_id'])
                                    ? $player_value['hero_id']
                                    : -1;

                                $heroData = $memcache->get('game_herodata' . $heroID);
                                if (!$heroData) {
                                    $heroData = $db->q(
                                        'SELECT * FROM `game_heroes` WHERE `hero_id` = ? LIMIT 0,1;',
                                        'i',
                                        $heroID
                                    );

                                    if (empty($heroData)) {
                                        $heroData = array();
                                        $heroData['localized_name'] = 'aaa_blank';
                                    } else {
                                        $heroData = $heroData[0];
                                    }

                                    $memcache->set('game_herodata' . $heroID, $heroData, 0, 1 * 60 * 60);
                                }

                                $player_value['player_name'] = $player_value['isBot'] != 1 && !empty($player_value['player_name'])
                                    ? htmlentities($player_value['player_name'])
                                    : '??';

                                $playerName = !empty($player_value['player_sid32']) && is_numeric($player_value['player_sid32'])
                                    ? '<a class="nav-clickable" href="./#d2mods__search?user=' . $player_value['player_sid32'] . '">' . $player_value['player_name'] . '</a>'
                                    : $player_value['player_name'];

                                $dbLink = !empty($player_value['player_sid32']) && is_numeric($player_value['player_sid32'])
                                    ? ' <a class="db_link" href="http://dotabuff.com/players/' . $player_value['player_sid32'] . '" target="_new">[DB]</a>'
                                    : '';

                                $isBot = !empty($player_value['isBot']) && $player_value['isBot'] == 1
                                    ? '<span class="glyphicon glyphicon-ok"></span>'
                                    : '<span class="glyphicon glyphicon-remove"></span>';

                                $arrayGoodConnectionStatus = array(1, 2, 3, 5);
                                if (!empty($player_value['connection_status']) && in_array($player_value['connection_status'], $arrayGoodConnectionStatus)) {
                                    $connectionStatus = '<span class="glyphicon glyphicon-ok-sign" title="' . $player_value['cs_string'] . '"></span>';
                                } else if (!empty($player_value['connection_status']) && $player_value['connection_status'] == 0) {
                                    $connectionStatus = '<span class="glyphicon glyphicon-question-sign" title="' . $player_value['cs_string'] . '"></span>';
                                } else {
                                    $connectionStatus = '<span class="glyphicon glyphicon-remove-sign" title="' . $player_value['cs_string'] . '"></span>';
                                }

                                ///////////////

                                $img_link = '//static.getdotastats.com/images/heroes/' . strtolower(str_replace('\'', '', str_replace(' ', '-', $heroData['localized_name']))) . '.png';

                                $heroLevel = !empty($player_value['hero_level'])
                                    ? $player_value['hero_level']
                                    : '-';

                                $heroKills = !empty($player_value['hero_kills'])
                                    ? $player_value['hero_kills']
                                    : '-';

                                $heroDeaths = !empty($player_value['hero_deaths'])
                                    ? $player_value['hero_deaths']
                                    : '-';

                                $heroAssists = !empty($player_value['hero_assists'])
                                    ? $player_value['hero_assists']
                                    : '-';

                                $heroLastHits = !empty($player_value['hero_lasthits'])
                                    ? $player_value['hero_lasthits']
                                    : '-';

                                $heroDenies = !empty($player_value['hero_denies'])
                                    ? $player_value['hero_denies']
                                    : '-';

                                ///////////////

                                echo '<tr>
                                        <td><img class="match_overview_hero_image" src="' . $img_link . '" alt="' . $heroData['localized_name'] . ' {ID: ' . $heroID . '}" /></td>
                                        <td>' . $playerName . $dbLink . '</td>
                                        <td class="text-center">' . $connectionStatus . '</td>
                                        <td class="text-center">' . $isBot . '</td>
                                        <td class="text-center">' . $heroLevel . '</td>
                                        <td class="text-center">' . $heroKills . ' / ' . $heroAssists . ' / ' . $heroDeaths . '</td>
                                        <td class="text-center">' . $heroLastHits . ' / ' . $heroDenies . '</td>
                                    </tr>';

                            }
                            echo '</table></div>';
                        }

                        echo '<hr />';
                    }
                } else {
                    echo bootstrapMessage('Oh Snap', 'Game ended without recording any player data!', 'danger');
                }
            } else {
                echo bootstrapMessage('Oh Snap', 'No match with that matchID!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'Invalid matchID!', 'danger');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }

    echo '<p>
            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Mod Directory</a>
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__recent_games">Recent Games</a>
            </div>
        </p>';

    echo '<div id="pagerendertime" class="pagerendertime">';
    echo '<hr />Page generated in ' . (time() - $start) . 'secs';
    echo '</div>';

    $memcache->close();
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}