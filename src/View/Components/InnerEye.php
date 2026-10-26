<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\View\Components\Concerns\RendersIcon;
use Illuminate\View\Component;

/**
 * `<x-qr::inner-eye shape="square" :with-outer="false" class="size-6" />`: an inner eye as an
 * icon, with the square outer eye faintly behind it unless told not to.
 *
 * @internal Used through its tag.
 */
final class InnerEye extends Component
{
    use RendersIcon;

    public function __construct(
        public InnerEyeShape|ProvidesQrInnerEye|string $shape,
        public bool $withOuter = true,
        public Corner|string|null $corner = null,
        public ?string $label = null,
    ) {
    }

    protected function icon(Icons $icons): Icon
    {
        return $icons->innerEye($this->shape, $this->withOuter, self::corner($this->corner));
    }
}
