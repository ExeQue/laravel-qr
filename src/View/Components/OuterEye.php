<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\View\Components\Concerns\RendersIcon;
use Illuminate\View\Component;

/**
 * `<x-qr::outer-eye :shape="OuterEye::Square" class="size-6" />`: an outer eye as an icon.
 *
 * @internal Used through its tag.
 */
final class OuterEye extends Component
{
    use RendersIcon;

    public function __construct(
        public OuterEyeShape|ProvidesQrOuterEye|string $shape,
        public Corner|string|null $corner = null,
        public ?string $label = null,
    ) {
    }

    protected function icon(Icons $icons): Icon
    {
        return $icons->outerEye($this->shape, self::corner($this->corner));
    }
}
