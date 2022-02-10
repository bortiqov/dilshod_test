<?php

class BackupMyProject
{
    // project files working directory - automatically created
    const PWD = "./";
    const  TOKEN = '5127250290:AAFMQo54yRikFhJMm9EqFWNBQE9EDHjDXW8';
    private $chatId = -763230886;
    private $sqlFile = "sqlDum.pgsql";


    /**
     * Class construct.
     *
     * @param string $path
     * @param bool $download
     */
    function __construct($path = null, $download = false)
    {
        // check construct argument
        if (!$path) die(__CLASS__ . ' Error: Missing construct param: $path');
        if (!file_exists($path)) die(__CLASS__ . ' Error: Path not found: ' . htmlentities($path));
        if (!is_readable($path)) die(__CLASS__ . ' Error: Path not readable: ' . htmlentities($path));

        // set working vars
        $this->project_path = rtrim($path, '/');
        $this->backup_file = self::PWD . basename($this->project_path) . '.zip';

        // make project backup folder
        if (!file_exists(self::PWD)) {
            mkdir(self::PWD, 0775, true);
        }

        try {
           $this->zipcreate($this->project_path, $this->backup_file);
        } catch (Exception $e) {
            die($e->getMessage());
        }

        if ($download !== false) {
            // send zip to user
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($this->backup_file) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . sprintf("%u", filesize($this->backup_file)));
            readfile($this->backup_file);
            // cleanup
            unlink($this->backup_file);
        }
    }


    public function sqlDump()
    {
        $db_host = "localhost";
        $db_username = "bahodir";
        $db_password = "";
        $db_database = "funzone";

        $cmd = "pg_dump -U {$db_username} {$db_database} > {$this->sqlFile}";
        exec($cmd);
    }

    /**
     * @return bool|string
     */

    public function sendTelegram()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot".self::TOKEN."/sendDocument?chat_id=" . $this->chatId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->backup_file);
        $cFile = new CURLFile($this->backup_file, $finfo);


        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "document" => $cFile
        ]);


        $result = curl_exec($ch);

        var_dump($result);
        return $result;

    }

    /**
     * Create zip from extracted/fixed project.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     * @uses RecursiveIteratorIterator
     * @uses ZipArchive
     */
    function zipcreate($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            throw new Exception(__CLASS__ . ' Fatal error: ZipArchive required to use BackupMyProject class');
        }
        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            throw new Exception(__CLASS__ . ' Error: ZipArchive::open() failed to open path');
        }
        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
        return $zip->close();
    }


    public function deleteFile(){
        unlink($this->backup_file);
    }

    public function deleteSql(){
        unlink($this->sqlFile);
    }

}

$backup = new BackupMyProject('../test-bot');
$backup->sqlDump();
$backup->sendTelegram();
$backup->deleteFile();
$backup->deleteSql();

