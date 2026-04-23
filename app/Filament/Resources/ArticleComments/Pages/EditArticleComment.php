<?php

namespace App\Filament\Resources\ArticleComments\Pages;

use App\Filament\Resources\ArticleComments\ArticleCommentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticleComment extends EditRecord
{
    protected static string $resource = ArticleCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

