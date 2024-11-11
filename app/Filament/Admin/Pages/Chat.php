<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Redirect;

class Chat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.chat';

    public function mount()
    {
        // Redirect to /chatify
        return Redirect::to('/chatify');
    }
}
