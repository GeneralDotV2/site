<?php
require_once("./global_functions.php");
try {
    if (!isset($_SESSION)) {
        session_start();
    }

    require_once("./connections/parameters.php");
    checkLogin_v2();
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Security-Policy"
          content="
          default-src 'none';
          connect-src 'self' static.getdotastats.com getdotastats.com;
          style-src 'self' static.getdotastats.com getdotastats.com 'unsafe-inline' ajax.googleapis.com *.google.com;
          script-src 'self' static.getdotastats.com getdotastats.com oss.maxcdn.com ajax.googleapis.com *.google.com 'unsafe-eval' 'unsafe-inline';
          img-src 'self' dota2.photography static.getdotastats.com getdotastats.com media.steampowered.com data: ajax.googleapis.com cdn.akamai.steamstatic.com cdn.dota2.com *.gstatic.com;
          font-src 'self' static.getdotastats.com getdotastats.com;
          frame-src chatwing.com *.youtube.com;
          object-src 'none';
          media-src 'none';
          report-uri ./csp_reports.php;">
    <meta charset="utf-8">
    <meta content="text/html">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="//getdotastats.com/bootstrap/css/bootstrap.min.css?1" rel="stylesheet">
    <link href="//static.getdotastats.com/getdotastats.css?17" rel="stylesheet">
    <!--<link href="./getdotastats.css?11" rel="stylesheet">-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <title>GetDotaStats - Dota 2 Statistics</title>
    <script type="text/javascript" src="//static.getdotastats.com/bootstrap/js/jquery.min.js?1"></script>
    <script type="text/javascript" src="//static.getdotastats.com/getdotastats.js?22"></script>
    <!--<script type="text/javascript" src="./getdotastats.js?13"></script>-->
</head>
<body>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div id="navBarCustom" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Custom Games <span
                            class="label label-warning">UPDATED</span> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Lobby Explorer</li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_list">Lobby List</a></li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_guide">Setup Guide</a></li>
                        <li><a class="nav-clickable" href="#d2mods__lobby_graph">Trends</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Mod Section</li>
                        <li><a class="nav-clickable" href="#d2mods__directory">Directory</a></li>
                        <li><a class="nav-clickable" href="#d2mods__recent_games">Recently Played Games</a></li>
                        <li><a class="nav-clickable" href="#d2mods__hof">Hall of Fame</a></li>
                        <li><a class="nav-clickable" href="#d2mods__guide">Developer Guide</a></li>
                        <li><a class="nav-clickable" href="#d2mods__mod_request">Request a Mod</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Mini Games Section</li>
                        <li><a class="nav-clickable" href="#d2mods__minigame_highscores">Highscores <span
                                    class="label label-warning">UPDATED</span></a></li>
                        <li><a class="nav-clickable" href="#d2mods__minigame_guide">Developer Guide</a></li>
                        <li><a class="nav-clickable" href="#d2mods__minigame_request">Request a Mini Game</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">My Custom Games</li>
                        <li><a class="nav-clickable" href="#d2mods__my_games">My Recent Games</a></li>
                        <li><a class="nav-clickable" href="#d2mods__my_mods">My Added Mods</a></li>
                        <li><a class="nav-clickable" href="#d2mods__my_minigames">My Mini Games</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Projekts <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li class="dropdown-header">Signatures</li>
                        <li><a class="nav-clickable" href="#steamtracks/">Generator <span
                                    class="label label-danger">HOT</span></a></li>
                        <li><a class="nav-clickable" href="#stats__sig_stats">Usage Stats</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Halls of Fame</li>
                        <li><a class="nav-clickable" href="#hof__golden_profiles">Golden Profiles</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Browser Extensions</li>
                        <li><a class="nav-clickable" href="#dbe/">Dotabuff Extended</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Simulations</li>
                        <li><a class="nav-clickable" href="#simulations__axespins/">Axe Counter Helix</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Misc.</li>
                        <li><a class="nav-clickable" href="#credits">Credits</a></li>
                        <li><a class="nav-clickable" href="#game_servers">Game Servers</a></li>
                        <li><a class="nav-clickable" href="#d2moddin/">D2Modd.in <span
                                    class="label label-info">DEAD</span></a></a></li>
                        <li><a class="nav-clickable" href="#contact">Contact</a></li>
                    </ul>
                </li>
                <?php if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) { ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <li><a class="nav-clickable" href="#admin/">Home</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Mods</li>
                            <li><a class="nav-clickable" href="#admin__mod_approve">Approve</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">Mini Games</li>
                            <li><a class="nav-clickable" href="#admin__minigame_create">Create</a></li>
                            <li class="divider"></li>
                            <li class="dropdown-header">CSP Reports</li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered_lw">Last Week</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports_filtered">Total</a></li>
                            <li><a class="nav-clickable" href="#admin__csp_reports">Last 100</a></li>
                            <?php if (!empty($_SESSION['access_feeds'])) { ?>
                                <li class="divider"></li>
                                <li class="dropdown-header">Feeds</li>
                                <li><a class="nav-clickable" href="#feeds/">Animu</a></li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } ?>
            </ul>
            <form id="searchForm" class="navbar-form navbar-left" role="search">
                <div class="form-group">
                    <input name="user" type="text" class="form-control" placeholder="UserID or MatchID">
                </div>
                <button type="submit" class="btn btn-default">Search</button>
            </form>
            <?php if (empty($_SESSION['user_id64'])) { ?>
                <p class="nav navbar-text"><a href="./auth/?login"><img src="./auth/assets/images/steam_small.png"
                                                                        alt="Sign in with Steam"/></a></p>
            <?php
            } else {
                $image = empty($_SESSION['user_avatar'])
                    ? $_SESSION['user_id32']
                    : '<a href="http://steamcommunity.com/profiles/' . $_SESSION['user_id64'] . '" target="_new"><img width="20px" src="' . $_SESSION['user_avatar'] . '" /></a> ';

                echo '<p class="nav navbar-text">' . $image . ' <a href="./auth/?logout">Logout</a></p>';
            } ?>
            <p class="nav navbar-text">
                <a id="nav-refresh-holder" class="nav-refresh" href="#home" title="Refresh page"><span
                        class="glyphicon glyphicon-refresh"></span></a>
            </p>
        </div>
    </div>
</div>
<div class="clear"></div>

<script type="application/javascript">
    $("#searchForm").submit(function (event) {
        event.preventDefault();
        var searchTerm = $("input:first").val();

        if (searchTerm.length == 32) {
            loadPage("#d2mods__match?id=" + searchTerm, 1);
            window.location.replace("#d2mods__match?id=" + searchTerm);
        }
        else {
            loadPage("#d2mods__search?user=" + searchTerm, 1);
            window.location.replace("#d2mods__search?user=" + searchTerm);
        }
    });
</script>

<span class="h4 clearfix">&nbsp;</span>

<div class="container">
    <div class="text-center">
        <a class="nav-clickable" href="#d2mods__lobby_list"><img width="400px"
                                                                 src="//static.getdotastats.com/images/getdotastats_logo_v3.png"
                                                                 alt="site logo"/></a>

        <div id="loading">
            <img id="loading_spinner1" src="//static.getdotastats.com/images/spinner_v2.gif" alt="loading"/>
        </div>
    </div>
</div>
<div class="clear"></div>

<div class="container">
    <div class="row">
        <div class="col-sm-9">
            <div id="main_content" class="blog-post"></div>
        </div>

        <div class="col-sm-3">
            <div class="sidebar-module sidebar-module-inset">
                <div class="text-center">
                    <a href="//flattr.com/thing/3621831/GetDotaStats" target="_blank" class="flattr-button"><span
                            class="flattr-icon"></span></a>
                    <a href="//steamcommunity.com/groups/getdotastats" target="_blank" class="steam-group-button"><span
                            class="steam-group-icon"></span><span class="steam-group-label">Join us on Steam</span></a>
                </div>
                <br/>
                <!-- Begin chatwing.com chatbox -->
                <iframe src="//chatwing.com/chatbox/e7f2bbd0-e292-4596-ab15-1667b4319e95" width="100%" height="650"
                        frameborder="0" scrolling="0">Embedded chat
                </iframe>
                <!-- End chatwing.com chatbox -->
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<div id="footer">
    <div class="container">
        <p class="text-muted">Built by jimmydorry. Dota 2 is a registered trademark of Valve Corporation. Powered by
            Steam.
            <small><a target="_blank" href="//github.com/GetDotaStats/GetDotaLobby/issues">Lobby Explorer Issues</a>
            </small>
            ||
            <small><a target="_blank" href="//github.com/GetDotaStats/site/issues">Site Issues</a></small>
        </p>
    </div>
</div>

<script src="//static.getdotastats.com/bootstrap/js/jquery-1-11-0.min.js?1"></script>
<script src="//static.getdotastats.com/bootstrap/js/bootstrap.min.js?1"></script>
</body>
</html>