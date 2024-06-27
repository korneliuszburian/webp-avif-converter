<?php

namespace WebpAvifConverter;

class Utils
{
    /**
     * Print the contents of the upload folder, including nested folders.
     *
     * @return void
     */
    public function printUploadFolderContents(): void
    {
        $upload_dir = wp_upload_dir()['basedir'];
        $this->printDirectoryContents($upload_dir);
    }

    /**
     * Recursively print the contents of a directory.
     *
     * @param string $dir
     * @return void
     */
    private function printDirectoryContents(string $dir): void
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
                $this->printDirectoryContents($path); // Recursive call
            } else {
                echo '<li>' . $item . '</li>';
            }
        }
        echo '</ul>';
    }
}
