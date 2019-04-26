<?php

//composer require rah/danpu
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Rah\Danpu\Import;

class DbDump
{
    private $dump;

    function __construct($host, $user, $pass, $db, $file)
    {
        $filename = __DIR__ . '/tmp';

        if (!file_exists($filename)) {
            mkdir($filename, 0700);
        }
        $this->dump = new Dump;
        $this->dump
            ->file($file)
            ->dsn('mysql:dbname=' . $db . ';host=' . $host)
            ->user($user)
            ->pass($pass)
            ->tmp($filename);
    }

    public function import()
    {
        try {
            new Import($this->dump);
            $this->clear();
            return true;
        } catch (\Exception $e) {
            echo 'Import failed with message: ' . $e->getMessage();
            return false;
        }
    }

    public function export()
    {
        try {
            new Export($this->dump);
            $this->clear();
            return true;
        } catch (\Exception $e) {
            echo 'Import failed with message: ' . $e->getMessage();
            return false;
        }
    }

    private function clear()
    {
        $files = glob(__DIR__ . '/tmp/*');
        foreach ($files as $file) {
            if (is_file($file))
                unlink($file);
        }
    }
}