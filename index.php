<?php
require_once __DIR__ . '/lib/vendor/autoload.php';
include_once('function.php');
switch ($_GET['method']) {
    case "backup":
        $config = full_backup();
        echo json_encode([
            'data' => $config
        ]);
        break;
    default:
        break;
}

?>