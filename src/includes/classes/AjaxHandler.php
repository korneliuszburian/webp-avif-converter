<?php

namespace WebpAvifConverter;

class AjaxHandler
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function init()
    {
        add_action('wp_ajax_update_conversion_progress', [$this, 'updateConversionProgress']);
    }

    public function updateConversionProgress()
    {
        check_ajax_referer('update_conversion_progress', 'security');
        
        $total = intval($_POST['total']);
        $current = intval($_POST['current']);
        
        $progress = ($current / $total) * 100;
        
        $this->logger->log("Progress update: $current/$total ($progress%)");
        
        wp_send_json_success(['progress' => $progress]);
    }
}