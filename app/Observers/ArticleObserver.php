<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ArticleObserver
{
    // Главная картинка статьи (внутри материала) — ограничиваем ширину до 1000px.
    // ВАЖНО: не кадрируем, а уменьшаем по ширине с сохранением пропорций.
    private const BLOG_IMAGE_WIDTH = 1000;
    private const BLOG_THUMB_SIZE = 120;
    private const BLOG_IMAGE_DIR_PREFIX = 'blog/';
    private const BLOG_THUMBS_DIR = 'blog/thumbs/';
    private const BLOG_PREVIEWS_DIR = 'blog/previews/';
    private const BLOG_PREVIEW_WIDTH = 550;
    private const BLOG_PREVIEW_HEIGHT = 370;

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

            // Уменьшаем только если изображение шире 1000px (без изменения пропорций).
            $image->scaleDown(width: self::BLOG_IMAGE_WIDTH);
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

            $previewDir = $disk->path(self::BLOG_PREVIEWS_DIR);
            if (! is_dir($previewDir)) {
                $disk->makeDirectory(self::BLOG_PREVIEWS_DIR);
            }
            $previewPath = self::BLOG_PREVIEWS_DIR . basename($path);
            $previewFullPath = $disk->path($previewPath);
            $preview = $manager->read($fullPath);
            $preview->cover(self::BLOG_PREVIEW_WIDTH, self::BLOG_PREVIEW_HEIGHT, 'center');
            $preview->save($previewFullPath);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
