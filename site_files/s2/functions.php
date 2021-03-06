<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!function_exists('modPageHeader')) {
    function modPageHeader($modID, $imageCDN)
    {
        global $db, $memcached;

        $result = '';

        $modDetails = cached_query(
            's2_mod_header_mod_details' . $modID,
            'SELECT
                  ml.`mod_id`,
                  ml.`steam_id64`,
                  ml.`mod_identifier`,
                  ml.`mod_name`,
                  ml.`mod_description`,
                  ml.`mod_workshop_link`,
                  ml.`mod_steam_group`,
                  ml.`mod_active`,
                  ml.`mod_rejected`,
                  ml.`mod_rejected_reason`,
                  ml.`mod_size`,
                  ml.`workshop_updated`,
                  ml.`date_recorded`,

                  gu.`user_name`,
                  gu.`user_avatar`,

                  guo.`user_email`,

                  (SELECT
                        SUM(`gamesPlayed`)
                      FROM `cache_mod_matches` cmm
                      WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3 AND cmm.`dateRecorded` >= now() - INTERVAL 7 DAY
                  ) AS games_last_week,
                  (SELECT
                        SUM(`gamesPlayed`)
                      FROM `cache_mod_matches` cmm
                      WHERE cmm.`modID` = ml.`mod_id` AND cmm.`gamePhase` = 3
                  ) AS games_all_time

                FROM `mod_list` ml
                LEFT JOIN `gds_users` gu ON ml.`steam_id64` = gu.`user_id64`
                LEFT JOIN `gds_users_options` guo ON ml.`steam_id64` = guo.`user_id64`
                WHERE ml.`mod_id` = ?
                LIMIT 0,1;',
            'i',
            $modID,
            15
        );

        if (empty($modDetails)) {
            throw new Exception('Invalid modID! Not recorded in database.');
        }

        //Tidy variables
        {
            //Mod name and thumb
            {
                $modThumb = is_file('../images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png')
                    ? $imageCDN . '/images/mods/thumbs/' . $modDetails[0]['mod_id'] . '.png'
                    : $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                $modThumb = '<img width="24" height="24" src="' . $modThumb . '" alt="Mod thumbnail" />';
                $modThumb = '<a target="_blank" href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '">' . $modThumb . '</a>';

                $modNameLink = $modIDname = '';
                if (!empty($_SESSION['user_id64'])) {
                    //if admin, show modIdentifier too
                    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                    if (!empty($adminCheck)) {
                        $modEditLink = '<a class="nav-clickable" href="#s2__my__mods_details?id=' . $modDetails[0]['mod_id'] . '"><span class="glyphicon glyphicon-pencil" title="Admin only!"></span></a>';
                        $modIDname = '<small>' . adminWrapText($modDetails[0]['mod_identifier'] . ' ' . $modEditLink) . '</small>';
                    }
                }
                $modNameLink = $modThumb . ' <a class="nav-clickable" href="#s2__mod?id=' . $modDetails[0]['mod_id'] . '">' . $modDetails[0]['mod_name'] . $modNameLink . '</a> ' . $modIDname;
            }

            //Mod external links
            {
                !empty($modDetails[0]['mod_workshop_link'])
                    ? $links['steam_workshop'] = '<a href="http://steamcommunity.com/sharedfiles/filedetails/?id=' . $modDetails[0]['mod_workshop_link'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Workshop</a>'
                    : NULL;
                !empty($modDetails[0]['mod_steam_group'])
                    ? $links['steam_group'] = '<a href="http://steamcommunity.com/groups/' . $modDetails[0]['mod_steam_group'] . '" target="_new"><span class="glyphicon glyphicon-new-window"></span> Steam Group</a>'
                    : NULL;
                $links = !empty($links)
                    ? implode(' || ', $links)
                    : 'None';
            }

            //Developer name and avatar
            {
                $developerAvatar = !empty($modDetails[0]['user_avatar'])
                    ? $modDetails[0]['user_avatar']
                    : $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                $developerAvatar = '<img width="20" height="20" src="' . $developerAvatar . '" alt="Developer avatar" />';
                $developerLink = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $modDetails[0]['steam_id64'] . '">' . $developerAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $modDetails[0]['steam_id64'] . '">' . $modDetails[0]['user_name'] . '</a>';
            }

            //Team Members
            {
                $teamMembers = '';
                $teamMembersSQL = cached_query(
                    's2_mod_tm_' . $modID,
                    'SELECT
                          mlo.`steam_id64`,
                          mlo.`date_recorded`,

                          gdsu.`user_name`,
                          gdsu.`user_avatar`

                      FROM `mod_list_owners`  mlo
                      LEFT JOIN `gds_users` gdsu
                        ON mlo.`steam_id64` = gdsu.`user_id64`
                      WHERE `mod_id` = ?
                      ORDER BY `date_recorded` DESC;',
                    'i',
                    array($modID),
                    5
                );

                if (!empty($teamMembersSQL)) {
                    $modEditLink = '<a class="nav-clickable" href="#s2__my__mods_details?id=' . $modDetails[0]['mod_id'] . '"><span class="glyphicon glyphicon-pencil" title="Admin only!"></span></a>';
                    $teamMembersTitle = 'Team Members';

                    if (!empty($_SESSION['user_id64'])) {
                        //Check if logged in user is on team
                        $modDetailsAuthorisation = $db->q(
                            'SELECT
                            `mod_id`
                          FROM mod_list_owners
                          WHERE
                            `mod_id` = ? AND
                            `steam_id64` = ?
                          LIMIT 0,1;',
                            'is',
                            array($modID, $_SESSION['user_id64'])
                        );

                        //Check if logged in user is an admin
                        $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');

                        if (!empty($modDetailsAuthorisation) || $adminCheck) {
                            $teamMembersTitle .= ' ' . $modEditLink;
                        }
                    }

                    $teamMembers = '<div class="row mod_info_panel">
                                <div class="col-sm-3"><strong>' . $teamMembersTitle . '</strong></div>
                                <div class="col-sm-9">';

                    $teamMembersArray = array();
                    foreach ($teamMembersSQL as $key => $value) {
                        if ($value['steam_id64'] != $modDetails[0]['steam_id64']) {
                            $teamMemberAvatar = !empty($value['user_avatar'])
                                ? $value['user_avatar']
                                : $imageCDN . '/images/misc/steam/blank_avatar.jpg';
                            $teamMemberAvatar = '<img width="20" height="20" src="' . $teamMemberAvatar . '" />';
                            $teamMemberUsername = !empty($value['user_name'])
                                ? $value['user_name']
                                : '???';
                            $teamMembersArray[] = '<a target="_blank" href="http://steamcommunity.com/profiles/' . $value['steam_id64'] . '">' . $teamMemberAvatar . '</a> <a class="nav-clickable" href="#s2__user?id=' . $value['steam_id64'] . '">' . $teamMemberUsername . '</a>';
                        }
                    }

                    if (!empty($teamMembersArray)) {
                        $teamMembers .= implode('<br />', $teamMembersArray);
                        $teamMembers .= '</div></div>';
                    } else {
                        $teamMembers = '';
                    }
                }
            }

            //Developer email
            {
                $developerEmail = '';
                if (!empty($_SESSION['user_id64'])) {
                    //if admin, show developer email too
                    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
                    if (!empty($adminCheck)) {
                        $developerEmail = '<div class="row mod_info_panel">
                                <div class="col-sm-3"><strong>' . adminWrapText('Developer Email') . '</strong></div>
                                <div class="col-sm-9">';

                        if (!empty($modDetails[0]['user_email'])) {
                            $developerEmail .= $modDetails[0]['user_email'];
                        } else {
                            $developerEmail .= 'Developer has not given us it!';
                        }

                        $developerEmail .= '</div>
                            </div>';
                    }
                }
            }

            //Status
            if (!empty($modDetails[0]['mod_rejected']) && !empty($modDetails[0]['mod_rejected_reason'])) {
                $modStatus = '<span class="boldRedText">Rejected:</span> ' . $modDetails[0]['mod_rejected_reason'];
            } else if ($modDetails[0]['mod_active'] == 1) {
                $modStatus = '<span class="boldGreenText">Accepted</span>';
            } else {
                $modStatus = '<span class="boldOrangeText">Pending Approval</span>';
            }

            //Mod Size
            {
                $modSize = !empty($modDetails[0]['mod_size'])
                    ? filesize_human_readable($modDetails[0]['mod_size'], 0, 'MB', true)
                    : NULL;

                $modSize = !empty($modSize)
                    ? $modSize['number'] . '<span class="db_link"> ' . $modSize['string'] . '</span>'
                    : '??<span class="db_link"> MB</span>';
            }

            //Last workshop update
            $workshopUpdateDate = !empty($modDetails[0]['workshop_updated'])
                ? relative_time_v3($modDetails[0]['workshop_updated'])
                : 'No workshop data available yet!';
        }

        $result .= '<h2>' . $modNameLink . '</h2>';

        //MOD INFO
        $result .= '<div class="container">';
        $result .= '<div class="col-sm-7">
                        <div class="row mod_info_panel">
                            <div class="col-sm-12 text-center">
                                <button class="btn btn-sm" data-toggle="collapse" data-target="#mod_info">Mod Info</button>
                            </div>
                        </div>
                    </div>';

        $result .= '<div id="mod_info" class="collapse col-sm-7">
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Status</strong></div>
                            <div class="col-sm-9">' . $modStatus . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Links</strong></div>
                            <div class="col-sm-9">' . $links . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Description</strong></div>
                            <div class="col-sm-9">' . $modDetails[0]['mod_description'] . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Developer</strong></div>
                            <div class="col-sm-9">' . $developerLink . '</div>
                        </div>' . $teamMembers . $developerEmail . '
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Size</strong></div>
                            <div class="col-sm-9">' . $modSize . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Total Games</strong></div>
                            <div class="col-sm-9">' . number_format($modDetails[0]['games_all_time']) . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Games (Last Week)</strong></div>
                            <div class="col-sm-9">' . number_format($modDetails[0]['games_last_week']) . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Updated</strong></div>
                            <div class="col-sm-9">' . $workshopUpdateDate . '</div>
                        </div>
                        <div class="row mod_info_panel">
                            <div class="col-sm-3"><strong>Added</strong></div>
                            <div class="col-sm-9">' . relative_time_v3($modDetails[0]['date_recorded']) . '</div>
                        </div>
                   </div>';
        $result .= '</div>';

        $result .= '<br />';

        $result .= "<div class='row'>
                        <div class='col-sm-12 text-right'>
                            <a class='nav-clickable btn btn-info' href='#s2__mod?id={$modID}'>Num Games</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_np?id={$modID}'>Num Players</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_ws?id={$modID}'>Workshop</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_schema?mid={$modID}'>Schema</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_f?id={$modID}'>Flags</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_cgv?id={$modID}'>Game Values</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_cpv?id={$modID}'>Player Values</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_wr?id={$modID}'>Winrates</a>
                            <a class='nav-clickable btn btn-info' href='#s2__mod_lb?id={$modID}'>Top Players</a>
                            <a class='nav-clickable btn btn-info' href='#s2__recent_games?m={$modID}'>Recent Games</a>
                        </div>
                    </div>";

        $result .= '<hr />';

        return $result;
    }
}