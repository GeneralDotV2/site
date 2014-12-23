<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    checkLogin_v2();

    if (!empty($_SESSION['user_id64']) && !empty($_SESSION['isAdmin'])) {
        echo '<h2>CSP Reports</h2>';
        echo '<p>
                <div class="text-center">
                    <a class="nav-clickable btn btn-default btn-lg" href="#admin/">Back to Admin Panel</a>
                </div>
            </p>';

        $db = new dbWrapper($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
        if ($db) {
            $reports = $db->q(
                "SELECT
                        `violated-directive`,
                        `blocked-uri`,
                        `source-file`,
                        COUNT(DISTINCT `remote-ip`) as sumReports
                    FROM `reports_csp_filter`
                    GROUP BY 1,2,3
                    ORDER BY sumReports DESC;"
            );

            if (!empty($reports)) {
                echo '<div class="table-responsive">
		            <table class="table table-striped table-hover bigTable">';
                echo '<tr>
                        <th class="col-sm-2 text-center">Directive</th>
                        <th>Blocked URI</th>
                        <th>Source URI</th>
                        <th class="col-sm-1 text-center">Unique Reports</th>
                    </tr>';
                foreach ($reports as $key => $value) {
                    $blockedURI = str_replace('http://', '', str_replace('https://', '', $value['blocked-uri']));
                    $sourceFile = str_replace('http://', '', str_replace('https://', '', $value['source-file']));

                    echo '<tr>
                            <td class="text-center">' . $value['violated-directive'] . '</td>
                            <td>' . $blockedURI . '</td>
                            <td>' . $sourceFile . '</td>
                            <td class="text-center">' . $value['sumReports'] . '</td>
                        </tr>';
                }
                echo '</table></div>';
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
    } else {
        echo bootstrapMessage('Oh Snap', 'Not logged in or not admin!', 'danger');
    }
} catch (Exception $e) {
    $message = 'Caught Exception -- ' . $e->getFile() . ':' . $e->getLine() . '<br /><br />' . $e->getMessage();
    echo bootstrapMessage('Oh Snap', $message, 'danger');
}
