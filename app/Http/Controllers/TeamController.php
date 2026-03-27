<?php

namespace App\Http\Controllers;

use App\Models\SectionSetting;
use App\Models\Specialist;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $section = SectionSetting::forType('specialists');
        $items = Specialist::query()
            ->latest('id')
            ->get();

        $title = $section?->title ?: 'Специалисты';
        $subtitle = $section?->subtitle ?: null;

        return view('team.index', [
            'section' => $section,
            'items' => $items,
            'title' => $title,
            'subtitle' => $subtitle,
            'seo_title' => $section?->seo_title ?: $title,
            'seo_description' => $section?->seo_description ?: strip_tags((string) ($section?->description ?? '')),
        ]);
    }
}
