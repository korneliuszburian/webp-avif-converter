<?php

namespace WebpAvifConverter\Utils;

/**
 * Helper function for deleting files.
 */
function safe_unlink($path)
{
    if (file_exists($path)) {
        unlink($path);
    }
}

function update_progress_bar($percent) {
    $percent = intval($percent);
    echo "data: $percent\n\n";
    ob_flush();
    flush();
}

function get_php_version_info()
{
    if(version_compare(phpversion(), PHP_REQUIRED_VERSION, '>=')){
        return 'PHP Version is: <span class="php-version-good">' . phpversion() . '</span><b> version >= ' . PHP_REQUIRED_VERSION . '</b> is required.';
    } else {
        return 'PHP Version: <span class="php-version-bad">' . phpversion() . '</span> is too low <.>version >= ' . PHP_REQUIRED_VERSION . '</b> is required.';
    }
}
