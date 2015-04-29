<?php
try {
    require_once('../global_functions.php');
    require_once('../connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $db = new dbWrapper_v3($hostname_gds_site, $username_gds_site, $password_gds_site, $database_gds_site, true);
    if (empty($db)) throw new Exception('No DB!');

    $memcache = new Memcache;
    $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

    checkLogin_v2();
    if (empty($_SESSION['user_id64'])) throw new Exception('Not logged in!');

    $adminCheck = adminCheck($_SESSION['user_id64'], 'admin');
    if (empty($adminCheck)) throw new Exception('Not an admin!');

    if (
        empty($_POST['modID'])
    ) {
        throw new Exception('Missing or invalid required parameter(s)!');
    }

    $modID = htmlentities($_POST['modID']);
    $modActive = 0;
    $modRejected = 0;
    $modRejectedReason = NULL;

    $insertSQL = $db->q(
        'UPDATE `mod_list`
          SET
            `mod_active` = ?,
            `mod_rejected` = ?,
            `mod_rejected_reason` = ?
          WHERE `mod_identifier` = ?;',
        'iiss',
        $modActive, $modRejected, $modRejectedReason, $modID
    );

    if ($insertSQL) {
        $json_response['result'] = 'Custom Game re-queued!';
    } else {
        throw new Exception('Custom Game not re-queued!');
    }

} catch (Exception $e) {
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
} finally {
    if (isset($memcache)) $memcache->close();
    if(!isset($json_response)) $json_response = array('error' => 'Unknown exception');
}

try {
    echo utf8_encode(json_encode($json_response));
} catch (Exception $e) {
    unset($json_response);
    $json_response['error'] = 'Caught Exception: ' . $e->getMessage();
    echo utf8_encode(json_encode($json_response));
}