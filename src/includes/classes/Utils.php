<?php

namespace WebpAvifConverter;

class Utils
{
    /**
     * Print the contents of the upload folder, including nested folders.
     *
     * @return void
     */
    public function print_upload_folder_contents(): void
    {
        $upload_dir = wp_upload_dir()['basedir'];
        $this->print_directory_content($upload_dir);
    }

    /**
     * Recursively print the contents of a directory.
     *
     * @param string $dir
     * @return void
     */
    private function print_directory_content(string $dir): void
    {
        $items = scandir($dir);

        echo '<ul>';
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                echo '<li><strong>' . $item . '</strong></li>';
                $this->print_directory_content($path);
            } else {
                echo '<li>' . $item . '</li>';
            }
        }
        echo '</ul>';
    }
}
