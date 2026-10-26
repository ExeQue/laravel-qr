<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\View\Components\Concerns\RendersIcon;
use Illuminate\View\Component;

/**
 * `<x-qr::eye :eye="Eye::Square" />` or `<x-qr::eye outer="square" inner="square" />`: a whole
 * eye as an icon, optionally turned as it sits on a corner.
 *
 * @internal Used through its tag.
 */
final class Eye extends Component
{
    use RendersIcon;

    public function __construct(
        public ProvidesQrEye|string|null $eye = null,
        public OuterEyeShape|ProvidesQrOuterEye|string|null $outer = null,
        public InnerEyeShape|ProvidesQrInnerEye|string|null $inner = null,
        public Corner|string|null $corner = null,
        public ?string $label = null,
    ) {
    }

    protected function icon(Icons $icons): Icon
    {
        return $icons->eye($this->eye, $this->outer, $this->inner, self::corner($this->corner));
    }
}
