<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    echo '<h2>List of Moderators</h2>';

    $moderatorList = cached_query(
        'admin_moderator_list',
        'SELECT
                gpu.`user_id64`,
                GROUP_CONCAT(gpu.`user_group` SEPARATOR ", ") AS `user_group`,
                gpu.`date_recorded`,

                gu.`user_name`,
                gu.`user_avatar`
            FROM `gds_power_users` gpu
            LEFT JOIN `gds_users` gu ON gpu.`user_id64` = gu.`user_id64`
            GROUP BY gpu.`user_id64`
            ORDER BY gpu.`date_recorded`;'
    );

    if (!empty($moderatorList)) {
        echo '<span class="h4">&nbsp;</span>';
        echo '<div class="row">
                    <div class="col-md-1">&nbsp;</div>
                    <div class="col-md-4"><span class="h4">Username</span></div>
                    <div class="col-md-3"><span class="h4">Groups</span></div>
                    <div class="col-md-2"><span class="h4">Date Added</span></div>
                </div>';
        echo '<span class="h5">&nbsp;</span>';
        foreach ($moderatorList as $key => $value) {
            $userAvatar = !empty($value['user_avatar'])
                ? $value['user_avatar']
                : $CDN_image . '/images/misc/steam/blank_avatar.jpg';

            $userName = !empty($value['user_name'])
                ? '<span class="h3">
                            <a class="nav-clickable" href="#s2__user?id=' . $value['user_id64'] . '">
                                ' . $value['user_name'] . '
                            </a>
                        </span>'
                : '<span class="h3">
                            <a class="nav-clickable" href="#s2__user?id=' . $value['user_id64'] . '">
                                ??
                            </a>
                            <small>Sign in to update profile!</small>
                        </span>';

            echo '<div class="row">
                    <div class="col-md-1">
                        <img alt="User Avatar" class="hof_avatar img-responsive center-block" src="' . $userAvatar . '" />
                    </div>
                    <div class="col-md-4">
                        ' . $userName . '
                    </div>
                    <div class="col-md-3">
                        ' . $value['user_group'] . '
                    </div>
                    <div class="text-right col-md-2">
                        ' . relative_time_v3($value['date_recorded']) . '
                    </div>
                </div>';
            echo '<span class="h5">&nbsp;</span>';
        }
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}