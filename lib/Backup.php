<?php

include_once('DbDump.php');

class Backup
{
    private $site_dir;
    private $file_name;
    private $dir_backup;
    private $small_name;

    public function __construct($dir = null)
    {

        $this->site_dir = (empty($dir)) ? dirname(__DIR__) : $dir;
        $this->small_name = $this->normalizeString('Backup-' . $_SERVER['SERVER_NAME'] . "-" . date('d.m.Y H:i'));
        $this->file_name = $this->site_dir . '/dex-backup/backup/' . $this->small_name . '.zip';
        $this->file_url = $this->url() . '/dex-backup/backup/' . $this->small_name . '.zip';
        $this->dir_backup = $this->site_dir . '/dex-backup/backup';
        $filename = $this->site_dir . '/dex-backup/backup';
        if (!file_exists($filename)) {
            mkdir($filename, 0700);
        }
    }

    public function clear()
    {
        $files = glob($this->dir_backup . '/*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
    }

    public function url()
    {
        return sprintf(
            "%s://%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME']
        );
    }

    public function get_config_db()
    {
        //https://www.nic.ru/help/konfiguracionnye-fajly-populyarnyh-cms_6776.html
        $top_cms = [
            'joomla' => $this->site_dir . '/configuration.php',
            'wp' => $this->site_dir . '/wp-config.php',
            '1c' => $this->site_dir . '/bitrix/php_interface/dbconn.php',
            'brupal' => $this->site_dir . '/sites/default/settings.php',
            'phpshop' => $this->site_dir . '/phpshop/inc/config.ini',
            'umi' => $this->site_dir . '/config.ini',
            'modxrevo' => $this->site_dir . '/core/config/config.inc.php',
            'opencart' => $this->site_dir . '/config.php',
            'webasyst' => $this->site_dir . '/wa-config/db.php',
            'dle' => $this->site_dir . '/engine/data/dbconfig.php',
        ];
        $config = [];
        $type = null;
        foreach ($top_cms as $kwy => $top_cm) {
            if (file_exists($top_cm)) {
                $type = $kwy;
            }
        }
        if (!empty($type)) {

            switch ($type) {
                case 'wp';
                    include_once $top_cms[$type];
                    $config['host'] = DB_HOST;
                    $config['user'] = DB_USER;
                    $config['pass'] = DB_PASSWORD;
                    $config['db'] = DB_NAME;
                    break;
                case 'webasyst':
                    $include = include($top_cms[$type]);
                    $config['host'] = $include['default']['host'];
                    $config['user'] = $include['default']['user'];
                    $config['pass'] = $include['default']['password'];
                    $config['db'] = $include['default']['database'];

                    break;
                default:
                    return false;
            }
            return $config;
        }
        return false;
    }

    public function add_db($config)
    {
        $da_name = $this->site_dir . '/dex-backup/backup/' . $this->small_name . '.sql';
        $db = new DbDump($config['host'], $config['user'], $config['pass'], $config['db'], $da_name);
        if ($db->export()) {
            $zip = new ZipArchive;
            if ($zip->open($this->file_name) === TRUE) {
                $zip->addFile($da_name, $this->small_name . '.sql');


                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry_info = $zip->statIndex($i);

                    if (substr($entry_info["name"], 0, strlen('dex-backup')) == 'dex-backup') {
                        $zip->deleteIndex($i);
                    }
                }

                $zip->close();
                unlink($da_name);
                return true;
            }

        }
        return false;
    }

    public
    function create_zip()
    {


        if ($this->zip($this->site_dir, $this->file_name)) {
            return [
                'dir' => $this->file_name,
                'url' => $this->file_url
            ];
        }
        return false;
    }

    private
    static function zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', DIRECTORY_SEPARATOR, realpath($source));
        $source = str_replace('/', DIRECTORY_SEPARATOR, $source);

        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);

                if ($file == '.' || $file == '..' || empty($file) || $file == DIRECTORY_SEPARATOR) {
                    continue;
                }
                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1), array('.', '..'))) {
                    continue;
                }

                $file = realpath($file);
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
                $file = str_replace('/', DIRECTORY_SEPARATOR, $file);

                if (is_dir($file) === true) {
                    $d = str_replace($source . DIRECTORY_SEPARATOR, '', $file);
                    if (empty($d)) {
                        continue;
                    }
                    $zip->addEmptyDir($d);
                } elseif (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . DIRECTORY_SEPARATOR, '', $file),
                        file_get_contents($file));
                } else {
                    // do nothing
                }
            }
        } elseif (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }

    public
    function normalizeString($str = '')
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }
}