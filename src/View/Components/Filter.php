<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\View\Components\Concerns\RendersIcon;
use Illuminate\View\Component;

/**
 * `<x-qr::filter filter="mixed-rotation" class="size-8" />`: what a filter does, as an icon.
 *
 * @internal Used through its tag.
 */
final class Filter extends Component
{
    use RendersIcon;

    public function __construct(
        public NamedFilter|ProvidesQrFilter|string $filter,
        public ?string $label = null,
    ) {
    }

    protected function icon(Icons $icons): Icon
    {
        return $icons->filter($this->filter);
    }
}
