<?php


class FiLogs
{

    // Configure your module
    public $LogPath = "../../../logs";

    public function setPath($path)
    {
        $this->LogPath = $path;
    }

    private function getPath()
    {
        if (is_dir($this->LogPath)) {
            return $this->LogPath;
        } else {
            die("Log directory: " . $this->LogPath . " is not a valid dir");
        }
    }

    public function getFiles()
    {
        $path = $this->getPath();
        $files = scandir($path);
        $files = array_reverse($files);
        return array_values($files);
    }

    public function getLastLogFile()
    {
        $files = $this->getFiles();
        $path = $this->getPath();
        $last_file = $path . "/" . $files[0];

        if (is_file($last_file)) {
            return $path . "/" . $files[0];
        } else {
            return false;
        }
    }
    public function getLastLogs()
    {
        // Get files and open the lastest
        $logFile = $this->getLastLogFile();
        if ($logFile) {
            $lines = file($logFile);
            return $lines;
        } else {
            return false;
        }
    }
}
