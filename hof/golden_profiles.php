<?php
require_once('./functions.php');
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

echo '
<head>
    <link href="./hof/auction.css" rel="stylesheet" type="text/css" >
</head>
';

try {
    $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    $db->q('SET NAMES utf8;');

    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $hofDetails = simple_cached_query(
            'hof_golden_profiles_list',
            'SELECT
                  hof_gp.`auction_rank`,
                  hof_gp.`user_id64`,
                  hof_gp.`user_id32`,
                  gdsu.`user_name`,
                  gdsu.`user_avatar`,
                  gdsu.`user_avatar_medium`,
                  gdsu.`user_avatar_large`
                FROM `hof_golden_profiles` hof_gp
                LEFT JOIN `gds_users` gdsu ON hof_gp.`user_id64` = gdsu.`user_id64`
                ORDER BY hof_gp.`auction_rank` ASC;',
            10
        );

        if (!empty($hofDetails)) {

            $table = '<div id="hof_block">';
            $table .= '<h2>2014 Holiday Profile - Hall of Fame</h2>';
            $table .= '<p>The below users are recognised for their valiant efforts in obtaining a limited edition 2014 Winter profile.</p>';

            foreach ($hofDetails as $key => $value) {
                $holidayBackground = 'http://cdn.akamai.steamstatic.com/steam/clusters/holiday2014_auction/dc9e02780a41ffde098796ac/golden_184x69_english.jpg?t=1418577448';
                $avatar = '//static.getdotastats.com/images/misc/hof/golden_profiles/new_user.jpg';

                if (!empty($value['user_id64']) && $value['user_id64'] != -1) {
                    $avatar = !empty($value['user_avatar'])
                        ? $value['user_avatar']
                        : NULL;

                    $username = !empty($value['user_name'])
                        ? htmlentities($value['user_name'])
                        : '??';

                    $table .= '<span class="auction_round auction_round_ended"><img class="round_bg" src="' . $holidayBackground . '"><span class="round_winner"><img src="' . $avatar . ' " alt="">' . $username . '</span></span>
                    ';

                } else {
                    $table .= '<span class="auction_round"><img class="round_bg" src="' . $holidayBackground . '"></span>
                    ';
                }
            }

            $table .= '</div>';
            echo $table;
        } else {
            echo bootstrapMessage('Oh Snap', 'No Hall of Fame entries!', 'danger');
        }

        $memcache->close();
    } else {
        echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
    }

    echo '<div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__directory">Return to Home</a>
            </div>';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}