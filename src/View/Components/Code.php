<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Builders\StyleBuilder;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Qr;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Style;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

/**
 * `<x-qr::code :content="$url" style="wedding" />`: a code as inline SVG, or as an `<img>`
 * with a PNG data URI given `as="img"`.
 *
 * @internal Used through its tag.
 */
final class Code extends Component
{
    /**
     * @param 'svg'|'img' $as
     * @param int|null $size Pixels: the PNG's width for an image; none for inline SVG, so CSS sizes it.
     * @param bool $currentColor Draw the code in the text color around it (inline SVG only).
     * @param string|null $label What the code is, for screen readers; "QR code" by default.
     */
    public function __construct(
        private readonly Qr $qr,
        public string|Payload|ProvidesQrContent $content,
        public Style|StyleBuilder|ProvidesQrStyle|string|null $style = null,
        public string $as = 'svg',
        public ?int $size = null,
        public bool $currentColor = false,
        public ?string $label = null,
    ) {
    }

    public function render(): View
    {
        return view('qr::components.code');
    }

    /**
     * The markup, with the tag's other attributes on the outer element.
     */
    public function html(ComponentAttributeBag $attributes): string
    {
        // PHPStan cannot follow when() without a callback; it returns the code either way.
        // @phpstan-ignore argument.templateType
        $code = $this->qr->make($this->content)->when($this->style !== null)->style($this->style);
        $label = $this->label ?? (string) __('qr::qr.label');

        if ($this->as === 'img') {
            $image = $code->png($this->size ?? 600);

            return '<img '.$attributes->merge(['src' => $image->toDataUri(), 'alt' => $label]).'>';
        }

        $svg = $code->svg(size: $this->size, currentColor: $this->currentColor, xmlDeclaration: false)->contents();

        return substr_replace($svg, '<svg '.$attributes->merge(['role' => 'img', 'aria-label' => $label]), 0, 4);
    }
}
