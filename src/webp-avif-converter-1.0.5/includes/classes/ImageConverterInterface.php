<?php

namespace WebpAvifConveter;

interface ImageConverterInterface
{
    public function convert(string $path, int $quality): ?string;
}