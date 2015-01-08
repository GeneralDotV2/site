<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        echo '<h2>Approve Mods</h2>';
        echo '<p>I lied. Not ready yet. :)</p>';
        echo '<p>
                <div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                </div>
            </p>';

        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        $db->q('SET NAMES utf8;');
        if ($db) {
            $modList = simple_cached_query('admin_d2mods_directory_inactive',
                'SELECT
                        ml.*,
                        gu.`user_name`,
                        gu.`user_avatar`
                    FROM `mod_list` ml
                    LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                    WHERE ml.`mod_active` <> 1
                    ORDER BY ml.date_recorded ASC;'
                , 1
            );

            if (!empty($modList)) {
                echo '
                    <form>
                    <div class="table-responsive">
		            <table class="table table-striped table-hover">';
                echo '<tr>
                        <th width="40">&nbsp;</th>
                        <th>&nbsp;</th>
                        <th width="170" class="text-left">Owner</th>
                        <th width="80" class="text-center">Links <span class="glyphicon glyphicon-question-sign" title="Steam workshop / Steam group"></span></th>
                    </tr>';

                foreach ($modList as $key => $value) {
                    $sg = !empty($value['mod_steam_group'])
                        ? '<a href="http://steamcommunity.com/groups/' . $value['mod_steam_group'] . '" target="_new">SG</a>'
                        : 'SG';

                    $wg = !empty($value['mod_workshop_link'])
                        ? '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $value['mod_workshop_link'] . '" target="_new">WS</a>'
                        : 'WG';

                    echo '<tr>
                        <td>' . ($key + 1) . '</td>
                        <th>' . $value['mod_name'] . '</th>
                        <td>' . '<img width="20" height="20" src="' . $value['user_avatar'] . '"/> ' . $value['user_name'] . '</td>
                        <th class="text-center">' . $wg . ' || ' . $sg . '</th>
                    </tr>
                    <tr class="warning">
                        <td colspan="6">
                            <div class="text-right"><strong>' . relative_time($value['date_recorded']) . '</strong> <span class="glyphicon glyphicon-question-sign" title="This mod was added ' . relative_time($value['date_recorded']) . '"></span></div>
                            ' . $value['mod_description'] . '<br />
                        </td>
                    </tr>';
                }

                echo '</table></div></form>';
            } else {
                echo bootstrapMessage('Oh Snap', 'No reports!', 'danger');
            }
        } else {
            echo bootstrapMessage('Oh Snap', 'No DB!', 'danger');
        }

        echo '<p>
                <div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                </div>
            </p>';

        $memcache->close();
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}