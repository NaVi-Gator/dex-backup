<?php
session_start();
set_time_limit(0);
ini_set('memory_limit', '1024M');
ignore_user_abort(true);

include_once('lib/Backup.php');

function full_backup()
{
    $b = new Backup(dirname(__DIR__));
    $b->clear();
    $result = $b->create_zip();
    $config = $b->get_config_db();
    $b->add_db($config);
    return $result;
}
