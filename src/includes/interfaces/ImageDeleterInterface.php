<?php

namespace WebpAvifConverter;

interface ImageDeleterInterface
{
    public function delete(string $path): void;
}