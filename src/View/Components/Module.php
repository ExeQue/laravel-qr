<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\View\Components\Concerns\RendersIcon;
use Illuminate\View\Component;

/**
 * `<x-qr::module :shape="Module::Square" class="size-5" />`: a module shape as an icon.
 *
 * @internal Used through its tag.
 */
final class Module extends Component
{
    use RendersIcon;

    public function __construct(
        public ModuleShape|ProvidesQrModule|string $shape,
        public ?string $label = null,
    ) {
    }

    protected function icon(Icons $icons): Icon
    {
        return $icons->module($this->shape);
    }
}
