<?php
try {
    require_once("./connections/parameters.php");
    require_once("./global_functions.php");
    require_once("./global_variables.php");

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcached = new Cache(NULL, NULL, $localDev);

    checkLogin_v2();

    $adminCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'admin')
        : false;

    $feedCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'animufeed')
        : false;

    $emailCheck = !empty($_SESSION['user_id64'])
        ? adminCheck($_SESSION['user_id64'], 'email')
        : false;

    $csp = generate_csp($CSParray);
    $csp = !empty($csp) ? '<meta http-equiv="Content-Security-Policy" content="' . $csp . '">' : '';
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . basename($e->getFile()) . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
} finally {
    if (!empty($memcached)) {
        $memcached->close();
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <?= $csp ?>
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicons/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/favicons/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/favicons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicons/favicon-16x16.png">
    <link rel="manifest" href="/images/favicons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/images/favicons/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="<?= $path_css_bootstrap_full ?>">
    <link rel="stylesheet" href="<?= $path_css_site_full ?>">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script type="text/javascript" src="<?= $path_lib_html5shivJS_full ?>"></script>
    <script type="text/javascript" src="<?= $path_lib_respondJS_full ?>"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="<?= $path_lib_jQuery_full ?>"></script>
    <script type="text/javascript" src="<?= $path_lib_siteJS_full ?>"></script>
</head>
<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div id="navBarCustom" class="navbar-collapse collapse">
            <span class="nav navbar-nav">
                <a class="nav-clickable" href="#s2__directory">
                    <img height="51px" width="194px" src="<?= $imageCDN ?>/images/getdotastats_logo_v5_1_small.png"
                         alt="site logo"/>
                </a>
            </span>
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <!--<span class="label label-success">UPDATED</span>-->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Custom Games <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Mod Section</li>
                        <li><a class="nav-clickable" href="#s2__directory">Directory</a></li>
                        <li><a class="nav-clickable" href="#s2__search">Search</a></li>
                        <li><a class="nav-clickable" href="#s2__recent_games">Recent Games</a></li>
                        <li><a class="nav-clickable" href="#s2__mod_aggregate">Aggregate Analysis</a></li>
                        <?php if (!empty($_SESSION['user_id64'])) { ?>
                            <li class="divider"></li>
                            <li class="dropdown-header">My Section</li>
                            <li><a class="nav-clickable" href="#s2__user?id=<?= $_SESSION['user_id64'] ?>">Public
                                    Profile</a></li>
                            <li><a class="nav-clickable" href="#s2__my__profile">Private Profile</a></li>
                            <li><a class="nav-clickable" href="#s2__my__give_feedback">Give Feedback</a></li>
                        <?php } ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Developers <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php if (!empty($_SESSION['user_id64'])) { ?>
                            <li class="dropdown-header">My Mods</li>
                            <li><a class="nav-clickable" href="#s2__my__mods">Mod List</a></li>
                            <li><a class="nav-clickable" href="#s2__my__mods_feedback">Mod Feedback</a></li>
                            <li><a class="nav-clickable" href="#s2__my__mod_request">Add New Mod</a></li>
                            <li class="divider"></li>
                        <?php } ?>
                        <li class="dropdown-header">Site Metrics</li>
                        <li><a class="nav-clickable" href="#site__crons">Scheduled Tasks <span
                                    class="label label-danger">NEW</span></a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Resources</li>
                        <li><a class="nav-clickable" href="#docs__implement_stat-collection">Implementing Stats</a></li>
                        <li><a class="nav-clickable" href="#docs__stat-collection">Schema stat-collection</a></li>
                        <li><a class="nav-clickable" href="#docs__stat-highscore">Schema stat-highscore</a></li>
                        <li><a class="nav-clickable" href="#docs__stat-save">Schema stat-save</a></li>
                        <li><a class="nav-clickable" href="#source2__beta_changes">Dota 2 Reborn Changes</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Projekts <span
                            class="label label-danger">NEW</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Signatures</li>
                        <li><a class="nav-clickable" href="#sig__generator">Generator</a></li>
                        <li><a class="nav-clickable" href="#sig__usage">Trends</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">TI6</li>
                        <li><a class="nav-clickable" href="#misc__arcana_votes">Arcana Votes</a></li>
                        <li><a class="nav-clickable" href="#misc__arcana_votes_per_round">Arcana Votes Per Round<span
                                    class="label label-danger">NEW</span></a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Halls of Fame</li>
                        <li><a class="nav-clickable" href="#hof__golden_profiles">Golden Profiles</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Browser Extensions</li>
                        <li><a class="nav-clickable" href="#dbe/">Dotabuff Extended</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#site__service_stats">Service Stats</a></li>
                        <li><a class="nav-clickable" href="#site__uptime">Up-Time Graph</a></li>
                        <li><a class="nav-clickable" href="#site__who">Who are we?</a></li>
                        <li><a class="nav-clickable" href="#site__game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#site__contact">Contact</a></li>
                    </ul>
                </li>
                <?php if (!empty($adminCheck)) { ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <span
                                class="label label-warning">NEW</span> <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Management</li>
                            <li><a class="nav-clickable" href="#admin__mod_approve">Mod Approve</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_edit">Mod Edit</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_rejected">Mods Rejected</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_contact_devs">Contact Devs</a></li>
                            <li><a class="nav-clickable" href="#admin__hs_mod">Highscores Schema</a></li>
                            <li><a class="nav-clickable" href="#admin__mod_schema">Mod Schema</a></li>
                            <li><a class="nav-clickable" href="#admin__cron_list">Cron List <span
                                        class="label label-danger">NEW</span></a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Tools</li>
                            <li><a class="nav-clickable" href="#admin__tools__add_site_user">Add User to Site Cache</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Misc.</li>
                            <li><a class="nav-clickable" href="#admin__moderator_list">Moderator List</a></li>
                            <li><a class="nav-clickable" href="#admin__ti6__matches">TI6 Matches <span
                                        class="label label-success">NEW</span></a></li>
                            <li><a class="nav-clickable" href="#admin__ti6__calendar">Calendar Management <span
                                        class="label label-danger">WIP</span></a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">CSP Reports</li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered_lw">Last Week</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered">Total</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports">Last 100</a></li>
                            <?php if (!empty($feedCheck)) { ?>
                                <li class="divider"></li>
                                <li class="dropdown-header">Feeds</li>
                                <li><a class="nav-clickable" href="#feeds/">Animu</a></li>
                            <?php } ?>
                            <?php if (!empty($emailCheck)) { ?>
                                <li class="divider"></li>
                                <li class="dropdown-header">Emails</li>
                                <li><a class="nav-clickable" href="#admin__email/">Email Lookup</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
            <?php if (empty($_SESSION['user_id64'])) { ?>
                <p class="nav navbar-text"><a href="./auth/?login"><img
                            src="<?= $CDN_generic ?>/auth/assets/images/steam_small.png"
                            alt="Sign in with Steam"/></a></p>
                <?php
            } else {
                $image = empty($_SESSION['user_avatar'])
                    ? $_SESSION['user_id32']
                    : '<a class="nav-clickable" href="#s2__user?id=' . $_SESSION['user_id64'] . '"><img width="20px" src="' . $_SESSION['user_avatar'] . '" /></a> ';

                echo '<p class="nav navbar-text">' . $image . ' <a href="./auth/?logout">Logout</a></p>';
            } ?>
            <p class="nav navbar-text">
                <a id="nav-refresh-holder" class="nav-refresh" href="#s2__directory" title="Refresh page"><span
                        class="glyphicon glyphicon-refresh"></span></a>
            </p>
        </div>
    </div>
</div>
<div class="clear"></div>

<span class="h4 clearfix hidden">&nbsp;</span>

<div class="container">
    <div class="text-center">
        <div id="loading">
            <img id="loading_spinner1" src="<?= $CDN_generic ?>/images/spinner_v2.gif" alt="loading"/>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="row">
        <!--<div class="alert alert-danger">
            We just recovered the site from a backup from the 1st of February. Any data that came into our possession
            since then has been lost, including mods that signed up. We apologise for the inconvenience.
        </div>-->

        <div id="main_content" class="col-sm-12"></div>

        <!--<div class="col-sm-3">
            <div class="sidebar-module sidebar-module-inset">
                <div class="text-center">
                    <a href="//steamcommunity.com/groups/getdotastats" target="_blank" class="steam-group-button"><span
                            class="steam-group-icon"></span> <span class="steam-group-label">Steam Group</span></a>

                    <a href="https://www.changetip.com/tipme/getdotastats" target="_blank"
                       class="changetip-button"><span
                            class="changetip-icon"></span> <span class="changetip-label">Tip.me</span></a>
                </div>

                <!-- Begin chatwing.com chatbox -->
        <!--<iframe src="//chatwing.com/chatbox/e7f2bbd0-e292-4596-ab15-1667b4319e95" width="100%" height="650"
                frameborder="0" scrolling="0">Embedded chat
        </iframe>-->
        <!-- End chatwing.com chatbox -->
        <!--
                        <br />

                        <p><strong>Chatbox: Removed for pressing ceremonial reasons</strong></p>
                    </div>
                </div>-->
    </div>
</div>
<div class="clear"></div>

<div id="footer">
    <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by
            Steam.

            <a href="//steamcommunity.com/groups/getdotastats" target="_blank" class="steam-group-button"><span
                    class="steam-group-icon"></span> <span class="steam-group-label">Steam Group</span></a>

            <a href="https://www.changetip.com/tipme/getdotastats" target="_blank"
               class="changetip-button"><span
                    class="changetip-icon"></span> <span class="changetip-label">Tip.me</span></a>


            <small><a target="_blank" href="https://github.com/GetDotaStats/stat-collection/issues">stat-collection
                    Issues</a>
            </small>
            ||
            <small><a target="_blank" href="https://github.com/GetDotaStats/site/issues">Site Issues</a></small>
        </p>
    </div>
</div>

<script type="text/javascript" src="<?= $path_lib_jQuery2_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_jQuery3_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_bootstrap_full ?>"></script>
<script type="text/javascript" src="<?= $path_lib_highcharts_full ?>"></script>
<script type="text/javascript">var _gaq = [
        ['_setAccount', 'UA-45573043-1'],
        ['_trackPageview']
    ];
    (function (d, t) {
        var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
        g.async = 1;
        g.src = '//www.google-analytics.com/ga.js';
        s.parentNode.insertBefore(g, s)
    })(document, 'script')</script>
</body>
</html>
