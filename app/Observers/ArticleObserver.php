<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ArticleObserver
{
    private const BLOG_IMAGE_WIDTH = 498;
    private const BLOG_IMAGE_HEIGHT = 336;
    private const BLOG_THUMB_SIZE = 120;
    private const BLOG_IMAGE_DIR_PREFIX = 'blog/';
    private const BLOG_THUMBS_DIR = 'blog/thumbs/';

    public function saved(Article $article): void
    {
        if (blank($article->image)) {
            return;
        }

        $path = ltrim($article->image, '/');
        if (! str_starts_with($path, self::BLOG_IMAGE_DIR_PREFIX)) {
            return;
        }

        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);

        if (! is_file($fullPath)) {
            return;
        }

        try {
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($fullPath);

            $image->cover(self::BLOG_IMAGE_WIDTH, self::BLOG_IMAGE_HEIGHT, 'center');
            $image->save($fullPath);

            $thumbDir = $disk->path(self::BLOG_THUMBS_DIR);
            if (! is_dir($thumbDir)) {
                $disk->makeDirectory(self::BLOG_THUMBS_DIR);
            }
            $thumbPath = self::BLOG_THUMBS_DIR . basename($path);
            $thumbFullPath = $disk->path($thumbPath);
            $thumb = $manager->read($fullPath);
            $thumb->cover(self::BLOG_THUMB_SIZE, self::BLOG_THUMB_SIZE, 'center');
            $thumb->save($thumbFullPath);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
