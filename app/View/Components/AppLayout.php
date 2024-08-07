<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AppLayout Component
 *
 * This component represents the main layout for the application.
 * It serves as the primary wrapper for all pages, providing a consistent
 * structure and appearance across the entire application.
 */
class AppLayout extends Component
{
    /**
     * Render the application layout component.
     *
     * @return View The view instance for the main application layout
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
