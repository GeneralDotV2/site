#!/usr/bin/php -q
<?php
require_once('../../../global_functions.php');
require_once('../../../connections/parameters.php');

try {
    $db = new dbWrapper_v3($hostname_gds_cron, $username_gds_cron, $password_gds_cron, $database_gds_cron, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"


    $time_start1 = time();
    echo '<h2>Mod Matches</h2>';

    $db->q('CREATE TABLE IF NOT EXISTS `cache_mod_matches_temp0`
        SELECT `matchID`, `modID`, `matchPhaseID`, `dateRecorded` FROM `s2_match` LIMIT 0, 100;');

    $db->q("CREATE TABLE IF NOT EXISTS `cache_mod_matches_temp1` (
        `day` int(2) NOT NULL DEFAULT '0',
        `month` int(2) NOT NULL DEFAULT '0',
        `year` int(4) NOT NULL DEFAULT '0',
        `modID` int(255) NOT NULL,
        `gamePhase` tinyint(1) NOT NULL,
        `gamesPlayed` bigint(255) NOT NULL,
        `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`modID`, `gamePhase`, `year`,`month`,`day`),
        KEY (`dateRecorded`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q("CREATE TABLE IF NOT EXISTS `cache_mod_matches` (
        `day` int(2) NOT NULL DEFAULT '0',
        `month` int(2) NOT NULL DEFAULT '0',
        `year` int(4) NOT NULL DEFAULT '0',
        `modID` int(255) NOT NULL,
        `gamePhase` tinyint(1) NOT NULL,
        `gamesPlayed` bigint(255) NOT NULL,
        `dateRecorded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`modID`, `gamePhase`, `year`,`month`,`day`),
        KEY (`dateRecorded`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

    $db->q('TRUNCATE `cache_mod_matches_temp0`;');
    $db->q('TRUNCATE `cache_mod_matches_temp1`;');

    $numMatchesProcessed = $db->q('INSERT INTO `cache_mod_matches_temp0`
        SELECT
          `matchID`, `modID`, `matchPhaseID`, `dateRecorded`
        FROM `s2_match`
        WHERE `dateRecorded` >= (SELECT DATE_FORMAT( IF( MAX(`dateRecorded`) >0, MAX(`dateRecorded`), (SELECT MIN(`dateRecorded`) FROM `s2_match` ) ), "%Y-%m-%d 00:00:00") - INTERVAL 1 DAY FROM `cache_mod_matches`);'
    );

    $db->q('INSERT INTO `cache_mod_matches_temp1`
            SELECT
                DAY(`dateRecorded`) as `day`,
                MONTH(`dateRecorded`) as `month`,
                YEAR(`dateRecorded`) as `year`,
                `modID`,
                `matchPhaseID` AS gamePhase,
                COUNT(*) as `gamesPlayed`,
                DATE_FORMAT(MAX(`dateRecorded`), "%Y-%m-%d 00:00:00") as `dateRecorded`
            FROM `cache_mod_matches_temp0`
            GROUP BY 4,5,3,2,1
            ORDER BY 4 DESC, 5 DESC, 3 DESC, 2 DESC, 1 DESC
        ON DUPLICATE KEY UPDATE
            `gamesPlayed` = VALUES(`gamesPlayed`);');

    $db->q(
        'INSERT INTO `cache_mod_matches`
            SELECT
                *
            FROM `cache_mod_matches_temp1`
            ON DUPLICATE KEY UPDATE
              `gamesPlayed` = VALUES(`gamesPlayed`);');

    $last_rows = $db->q('SELECT * FROM `cache_mod_matches_temp1` ORDER BY `dateRecorded` DESC, `modID`, `gamePhase`;');

    $db->q('DROP TABLE `cache_mod_matches_temp0`;');
    $db->q('DROP TABLE `cache_mod_matches_temp1`;');

    echo '<table border="1" cellspacing="1">';
    echo '<tr>
        <th>modID</th>
        <th>Phase</th>
        <th>Games</th>
        <th>Date</th>
    </tr>';
    foreach ($last_rows as $key => $value) {
        echo '<tr>
            <td>' . $value['modID'] . '</td>
            <td>' . $value['gamePhase'] . '</td>
            <td>' . $value['gamesPlayed'] . '</td>
            <td>' . $value['dateRecorded'] . '</td>
        </tr>';
    }
    echo '</table>';

    $time_end1 = time();
    $totalRunTime = $time_end1 - $time_start1;
    echo 'Total Running: ' . ($time_end1 - $time_start1) . " seconds<br /><br />";

    echo '<hr />';

    try {
        $serviceName = 's2_cron_matches';

        $oldServiceReport = cached_query(
            $serviceName . '_old_service_report',
            'SELECT
                    `instance_id`,
                    `service_name`,
                    `execution_time`,
                    `performance_index1`,
                    `performance_index2`,
                    `performance_index3`,
                    `date_recorded`
                FROM `cron_services`
                WHERE `service_name` = ?
                ORDER BY `date_recorded` DESC
                LIMIT 0,1;',
            's',
            array($serviceName),
            1
        );

        service_report($serviceName, $totalRunTime, $numMatchesProcessed);

        if (empty($oldServiceReport)) throw new Exception('No old service report data!');

        $oldServiceReport = $oldServiceReport[0];

        //Check if the run-time increased majorly
        if ($totalRunTime > 10 && ($totalRunTime > ($oldServiceReport['execution_time'] * 2))) {
            throw new Exception("Major increase (>100%) in execution time! {$oldServiceReport['execution_time']}secs to {$totalRunTime}secs");
        }

        //Check if the performance_index1 increased majorly
        if ($numMatchesProcessed > ($oldServiceReport['performance_index1'] * 1.10)) {
            throw new Exception("Major increase (>10%) in performance index #1! {$oldServiceReport['performance_index1']} matches to {$numMatchesProcessed} matches");
        }

    } catch (Exception $e) {
        echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';

        //WEBHOOK
        {
            $irc_message = new irc_message($webhook_gds_site_admin);

            $message = array(
                array(
                    $irc_message->colour_generator('red'),
                    '[CRON]',
                    $irc_message->colour_generator(NULL),
                ),
                array(
                    $irc_message->colour_generator('green'),
                    '[MATCHES]',
                    $irc_message->colour_generator(NULL),
                ),
                array(
                    $irc_message->colour_generator('bold'),
                    $irc_message->colour_generator('blue'),
                    'Error:',
                    $irc_message->colour_generator(NULL),
                    $irc_message->colour_generator('bold'),
                ),
                array($e->getMessage() . ' ||'),
                array('http://getdotastats.com/s2/routine/log_10minute.html?' . time())
            );

            $message = $irc_message->combine_message($message);
            $irc_message->post_message($message, array('localDev' => $localDev));
        }
    }

} catch (Exception $e) {
    echo 'Caught Exception (MAIN) -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '<br /><br />';
} finally {
    if (isset($memcache)) $memcache->close();
}