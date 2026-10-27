<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Builders\Concerns\BuildsStyle;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Writers\Metadata;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;

/**
 * A reusable style, without content. `for()` turns it into a code; the
 * builder itself never changes, so it can be kept and built on.
 *
 * @implements Arrayable<string, mixed>
 */
final class StyleBuilder implements Arrayable
{
    use BuildsStyle;
    use Conditionable;
    use Macroable;
    use Tappable;

    public function __construct(Resolve $resolve, Style $style, Metadata $metadata)
    {
        $this->resolve = $resolve;
        $this->style = $style;
        $this->metadata = $metadata;
    }

    /**
     * A code with this style.
     */
    public function for(string|Payload|ProvidesQrContent $content): PendingQr
    {
        return app(PendingQr::class, ['content' => $content])->fromBuilder($this);
    }

    /**
     * @return array{style: Style, errorCorrection: \ExeQue\Qr\Encoder\Enums\ErrorCorrection|null, mask: \ExeQue\Qr\Encoder\Enums\Mask|null, metadata: Metadata, scanSafety: \ExeQue\Qr\Rendering\ScanSafety|null}
     *
     * @internal
     */
    public function state(): array
    {
        return [
            'style' => $this->style,
            'errorCorrection' => $this->errorCorrection,
            'mask' => $this->mask,
            'metadata' => $this->metadata,
            'scanSafety' => $this->scanSafety,
        ];
    }
}
