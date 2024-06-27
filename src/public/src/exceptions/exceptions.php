<?php
class FilesizeUnavailableException extends Exception
{
    private $unsupportedExtension;

    public function __construct($unsupportedExtension, $message = 'Filesize is unavailable for this file.', $code = 0, Throwable $previous = null)
    {
        $this->unsupportedExtension = $unsupportedExtension;
        $message .= ' Unsupported extension: ' . $unsupportedExtension;
        parent::__construct($message, $code, $previous);
    }

    public function getUnsupportedExtension()
    {
        return $this->unsupportedExtension;
    }
}
?>
