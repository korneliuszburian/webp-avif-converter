<?php

namespace WebpAvifConverter;

interface ImageConverterInterface
{
    public function convert(string $path, int $quality): ?string;
}