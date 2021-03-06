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

    echo '<h2>CSP Reports (Last 100)</h2>';

    $reports = simple_cached_query(
        'csp_reports_l100',
        "SELECT * FROM `reports_csp` ORDER BY `reportDate` DESC LIMIT 0,100;",
        60
    );

    $reportsStats = simple_cached_query(
        'csp_report_stats',
        "SELECT
            (
                SELECT
                    COUNT(*) as total_reports_lw
                FROM `reports_csp_filter`
                WHERE `dateRecorded` >= now() - INTERVAL 7 DAY
                LIMIT 0,1
            ) AS total_reports_lw,
            (
                SELECT
                    COUNT(*) as total_reports_lw
                FROM `reports_csp_filter`
                LIMIT 0,1
            ) AS total_reports;",
        60
    );

    if (!empty($reportsStats)) {
        echo
            '<div class="alert alert-danger">
                <span class="h3">Reports</span><br />
                <strong>Total:</strong> ' . number_format($reportsStats[0]['total_reports']) . '<br />
                        <strong>Last Week:</strong> ' . number_format($reportsStats[0]['total_reports_lw']) . '
                    </div>';
    }

    if (!empty($reports)) {
        foreach ($reports as $key => $value) {
            $reportIP = empty($value['reportIP'])
                ? ''
                : " - " . $value['reportIP'];

            echo relative_time_v3($value['reportDate']) . $reportIP . "<br />";

            echo '<pre>';

            $reportContect = json_decode($value['reportContent'], 1);
            unset($reportContect['csp-report']['original-policy']);

            echo print_r($reportContect['csp-report']);

            echo '<br /><br />';

            $reportHeaders = json_decode($value['reportHeaders'], 1);
            echo print_r($reportHeaders);

            echo '</pre>';

            echo "<hr />";
        }
    } else {
        echo bootstrapMessage('Oh Snap', 'No reports!', 'danger');
    }

} catch (Exception $e) {
    echo formatExceptionHandling($e);
} finally {
    if (isset($memcached)) $memcached->close();
}