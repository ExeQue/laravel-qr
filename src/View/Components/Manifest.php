<?php

declare(strict_types=1);

namespace ExeQue\Qr\View\Components;

use ExeQue\Qr\Qr;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * `<x-qr::manifest />`: the manifest in the page, as JSON `laravel-qr.js` reads, its labels and
 * messages in the request's language, or in `locales`.
 *
 * @internal Used through its tag.
 */
final class Manifest extends Component
{
    /**
     * @param list<string>|string|null $locales
     */
    public function __construct(
        public array|string|null $locales = null,
        public string $id = 'laravel-qr-manifest',
    ) {
    }

    public function render(): View
    {
        return view('qr::components.manifest');
    }

    /**
     * The manifest as JSON that cannot end the script element it sits in.
     */
    public function json(): string
    {
        return (string) json_encode(app(Qr::class)->manifest($this->locales), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
