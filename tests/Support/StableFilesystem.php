<?php

namespace Tests\Support;

use Illuminate\Filesystem\Filesystem;

class StableFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $content, LOCK_EX);

        if (! is_null($mode)) {
            @chmod($path, $mode);
        } else {
            @chmod($path, 0777 - umask());
        }
    }
}
