<?php

namespace App\Traits;

use App\Models\DailyStat;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait BuildViewBreadcrumbs {
    /**
     * @var array<array{label: string, link: string|null} $breadcrumbs>
     */
    private $breadcrumbs = [];

    protected function addBreadcrumb(string $label, ?string $link = null): self {
        $this->breadcrumbs[] = [
            'label' => $label,
            'link'  => $link,
        ];
        return $this;
    }

    protected function getBreadcrumbs(): array {
        return $this->breadcrumbs;
    }
}
