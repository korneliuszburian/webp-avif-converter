<?php

namespace WebpAvifConverter;

class Logger
{
    private $logFilePath;

    public function __construct()
    {
        $this->logFilePath = plugin_dir_path(__FILE__) . '../../webp_avif_converter.log';
    }

    public function log($message)
    {
        $date = date('Y-m-d H:i:s');
        $formattedMessage = "[$date] $message" . PHP_EOL;
        file_put_contents($this->logFilePath, $formattedMessage, FILE_APPEND);
    }
}
