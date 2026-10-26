<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components\Concerns;

use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Rendering\Enums\Corner;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;

/**
 * An icon component: hidden from screen readers unless given a `label`, with the
 * tag's other attributes on its `<svg>`.
 *
 * @internal
 */
trait RendersIcon
{
    abstract protected function icon(Icons $icons): Icon;

    public function render(): View
    {
        return view('qr::components.icon');
    }

    public function html(ComponentAttributeBag $attributes): string
    {
        $accessibility = $this->label === null ? ['aria-hidden' => 'true'] : ['role' => 'img', 'aria-label' => $this->label];

        return $this->icon(app(Icons::class))->withAttributes((string) $attributes->merge($accessibility))->toHtml();
    }

    private static function corner(Corner|string|null $corner): ?Corner
    {
        return is_string($corner) ? Corner::from($corner) : $corner;
    }
}
