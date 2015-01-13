<?php
require_once('../global_functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

checkLogin_v2();

try {
    if (!empty($_SESSION['user_id64'])) {
        $db = new dbWrapper_v2($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site);
        $db->q('SET NAMES utf8;');

        if ($db) {
            ?>
            <div class="page-header">
                <h2>Add a new Mod for Stats
                    <small>BETA</small>
                </h2>
            </div>

            <p>This is a form that developers can use to add a mod to the list, and get access to the necessary code to
                implement stats for their mod. <strong>THIS IS NOT A PLACE TO ASK FOR A LOBBY!</strong></p>

            <div class="container">
                <div class="col-sm-6">
                    <form id="modSignup">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <tr>
                                    <th width="120">Name</th>
                                    <td><input name="mod_name" type="text" maxlength="35" size="45" required></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><textarea name="mod_description" rows="4" cols="47" required></textarea></td>
                                </tr>
                                <tr>
                                    <th>Workshop Link</th>
                                    <td><input name="mod_workshop_link" type="text" maxlength="70" size="45" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Steam Group</th>
                                    <td><input name="mod_steam_group" type="text" maxlength="70" size="45"></td>
                                </tr>
                                <tr>
                                    <th>Maps</th>
                                    <td><textarea name="mod_maps" rows="3" maxlength="255" cols="45" required>One map
                                            per line. Have a look at the map field in the in-game client
                                            lobby.</textarea></td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center">
                                        <button id="sub">Signup</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                </div>
            </div>

            <br/>

            <span id="modSignupResult" class="label label-danger"></span>

            <br/><br/>

            <div class="text-center">
                <a class="nav-clickable btn btn-default btn-lg" href="#d2mods__my_mods">Browse my mods</a>
            </div>

            <script type="application/javascript">
                $("#modSignup").submit(function (event) {
                    event.preventDefault();

                    $.post("./d2mods/signup_insert.php", $("#modSignup").serialize(), function (data) {
                        $("#modSignup :input").each(function () {
                            $(this).val('');
                        });
                        $('#modSignupResult').html(data);
                    }, 'text');
                });
            </script>

        <?php
        } else {
            echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> No DB!</div></div>';
        }
    } else {
        echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Not logged in!</div></div>';
    }
} catch (Exception $e) {
    echo '<div class="page-header"><div class="alert alert-danger" role="alert"><strong>Oh Snap:</strong> Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage() . '</div></div>';
}