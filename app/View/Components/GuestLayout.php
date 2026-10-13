<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    public ?string $title;
    public ?string $description;
    public ?string $keywords;
    public ?string $ogImage;
    public ?string $canonicalUrl;
    public ?string $robots;

    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $keywords = null,
        ?string $ogImage = null,
        ?string $canonicalUrl = null,
        ?string $robots = null
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->keywords = $keywords;
        $this->ogImage = $ogImage;
        $this->canonicalUrl = $canonicalUrl;
        $this->robots = $robots;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
