<?php

declare(strict_types=1);

namespace App;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use function getimagesize;

final class ImageOptimizer
{
    private const MAX_WIDTH = 200;

    private const MAX_HEIGHT = 150;

    /** @var Imagine */
    private $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $filename): void
    {
        [$fileWidth, $fileHeight] = getimagesize($filename);
        $ratio = $fileWidth / $fileHeight;

        $width = self::MAX_WIDTH;
        $height = self::MAX_HEIGHT;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $this->imagine
            ->open($filename)
            ->resize(new Box($width, $height))
            ->save($filename);
    }
}
