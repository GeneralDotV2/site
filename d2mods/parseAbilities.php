<?php
require_once('../global_functions.php');
require_once('./functions.php');
require_once('../connections/parameters.php');

try {
    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    echo '
        <head>
            <link href="//getdotastats.com/bootstrap/css/bootstrap.min.css" rel="stylesheet">
            <link href="//getdotastats.com/getdotastats.css?10" rel="stylesheet">
        </head>
    ';

    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        if ($db) {
            $dir = '../images/abilities/default/';
            $files1 = scandir($dir);

            foreach ($files1 as $fileValue) {
                $filename = str_replace('.png', '', rtrim($fileValue, '.'));

                if(!empty($filename)){
                    echo $filename . '<br />';
                    $db->q(
                        'INSERT INTO `game_regular_abilities` (`ability_name`)
                            VALUES (?)
                         ON DUPLICATE KEY UPDATE
                            `ability_name` = VALUES(`ability_name`);',
                        's',
                        $filename
                    );
                }
            }

            /*echo '<pre>';
            print_r($files1);
            echo '</pre>';*/
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!');
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in!');
        echo '<a href="../">Go back to main site</a>';
    }
} catch (Exception $e) {
    $eMsg = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $eMsg);
}