<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation;

use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Rendering\Style;
use SplFileInfo;

/**
 * For a form request or a Livewire form whose rules include a StyleRule: the validated
 * style input as a style.
 *
 * @phpstan-ignore trait.unused
 */
trait InteractsWithQrStyle
{
    /**
     * The style validated under `$key`, over the base given, the default style, or a named style.
     *
     * @param Logo|SplFileInfo|null $logo The logo's image, for input with logo settings.
     */
    public function qrStyle(string $key = 'style', Style|string|null $base = null, Logo|SplFileInfo|null $logo = null): Style
    {
        $validated = method_exists($this, 'validated') ? $this->validated($key) : data_get($this->validate(), $key);

        return StyleRule::toStyle((array) ($validated ?? []), $base, $logo);
    }
}
