<?php

namespace WebpAvifConverter;

class ConversionQueue
{
    private $queueFile;

    public function __construct($queueFile)
    {
        $this->queueFile = $queueFile;
    }

    public function enqueue($data)
    {
        $queueContents = file_get_contents($this->queueFile);
        $queue = json_decode($queueContents, true);
        $queue[] = $data;
        file_put_contents($this->queueFile, json_encode($queue));
    }

    public function dequeue()
    {
        $queueContents = file_get_contents($this->queueFile);
        $queue = json_decode($queueContents, true);
        $data = array_shift($queue);
        file_put_contents($this->queueFile, json_encode($queue));
        return $data;
    }

    public function isEmpty()
    {
        $queueContents = file_get_contents($this->queueFile);
        $queue = json_decode($queueContents, true);
        return empty($queue);
    }
}
