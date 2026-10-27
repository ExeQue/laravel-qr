<?php

declare(strict_types=1);

namespace ExeQue\Qr;

use Closure;
use DateTimeInterface;
use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Builders\Pipeline;
use ExeQue\Qr\Builders\Rendered;
use ExeQue\Qr\Builders\Resolve;
use ExeQue\Qr\Builders\StyleBuilder;
use ExeQue\Qr\Colors\ColorNames;
use ExeQue\Qr\Decoding\Decoded;
use ExeQue\Qr\Exceptions\ResolveException;
use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Contracts\ProvidesQrFilter;
use ExeQue\Qr\Icons\Icon;
use ExeQue\Qr\Icons\Icons;
use ExeQue\Qr\Manifest\Manifest;
use ExeQue\Qr\Payloads\Contracts\Payload;
use ExeQue\Qr\Payloads\Contracts\ProvidesQrContent;
use ExeQue\Qr\Payloads\Email;
use ExeQue\Qr\Payloads\Enums\WifiSecurity;
use ExeQue\Qr\Payloads\Event;
use ExeQue\Qr\Payloads\Geo;
use ExeQue\Qr\Payloads\Sms;
use ExeQue\Qr\Payloads\Text;
use ExeQue\Qr\Payloads\Url;
use ExeQue\Qr\Payloads\Vcard;
use ExeQue\Qr\Payloads\Wifi;
use ExeQue\Qr\Registries\DecoderRegistry;
use ExeQue\Qr\Registries\EyeRegistry;
use ExeQue\Qr\Registries\FilterRegistry;
use ExeQue\Qr\Registries\GradientRegistry;
use ExeQue\Qr\Registries\InnerEyeRegistry;
use ExeQue\Qr\Registries\ModuleRegistry;
use ExeQue\Qr\Registries\OuterEyeRegistry;
use ExeQue\Qr\Registries\PayloadRegistry;
use ExeQue\Qr\Registries\StyleRegistry;
use ExeQue\Qr\Registries\WriterRegistry;
use ExeQue\Qr\Rendering\Contracts\ProvidesQrStyle;
use ExeQue\Qr\Rendering\Enums\Corner;
use ExeQue\Qr\Rendering\Style;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrModule;
use ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye;
use ExeQue\Qr\Validation\StyleRule;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Contracts\Writer;
use ExeQue\Qr\Writers\Metadata;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Traits\Macroable;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * What the Qr facade stands for.
 */
final class Qr
{
    use Macroable;

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function modules(): ModuleRegistry
    {
        return $this->container->make(ModuleRegistry::class);
    }

    public function outerEyes(): OuterEyeRegistry
    {
        return $this->container->make(OuterEyeRegistry::class);
    }

    public function innerEyes(): InnerEyeRegistry
    {
        return $this->container->make(InnerEyeRegistry::class);
    }

    public function eyes(): EyeRegistry
    {
        return $this->container->make(EyeRegistry::class);
    }

    public function filters(): FilterRegistry
    {
        return $this->container->make(FilterRegistry::class);
    }

    public function gradients(): GradientRegistry
    {
        return $this->container->make(GradientRegistry::class);
    }

    /**
     * A code holding `$content`, styled as the config's defaults say.
     */
    public function make(string|Payload|ProvidesQrContent $content): PendingQr
    {
        return $this->style()->for($content);
    }

    /**
     * A reusable style without content, starting from the config's defaults; `for()` makes it a code.
     */
    public function style(): StyleBuilder
    {
        $config = $this->container->make('config');
        $defaults = (array) $config->get('qr.defaults', []);

        $builder = new StyleBuilder($this->container->make(Resolve::class), Style::default(), new Metadata())
            // Fluent when() without a callback: the proxy it returns is beyond PHPStan's inference.
            // @phpstan-ignore argument.templateType
            ->when(isset($defaults['margin']))->margin((int) ($defaults['margin'] ?? 4))
            ->when(isset($defaults['paint']))->paint($defaults['paint'] ?? null)
            ->when(array_key_exists('background', $defaults))->background($defaults['background'] ?? null)
            ->when(isset($defaults['module']))->modules($defaults['module'] ?? null)
            ->when(isset($defaults['eye']))->eyes($defaults['eye'] ?? null);

        return isset($defaults['style']) ? $builder->style($defaults['style']) : $builder;
    }

    /**
     * The application's rules for style input; see StyleRule::defaults().
     */
    public function rule(): StyleRule
    {
        return StyleRule::defaults();
    }

    public function url(string $url): PendingQr
    {
        return $this->make(new Url($url));
    }

    /**
     * A Wi-Fi network to join; its details now or by chaining: `->ssid()->password()->wpa()`.
     */
    public function wifi(?string $ssid = null, ?string $password = null, WifiSecurity|string $security = WifiSecurity::Wpa, bool $hidden = false): PendingQr
    {
        $security = $security instanceof WifiSecurity ? $security : WifiSecurity::from($security);

        return $this->make(new Wifi($ssid, $security === WifiSecurity::None ? null : $password, $security, $hidden));
    }

    /**
     * A contact card; its details now or by chaining: `->name()->phone()->email()`.
     */
    public function vcard(?string $name = null, ?string $phone = null, ?string $email = null, ?string $organization = null): PendingQr
    {
        $card = new Vcard()
            // Fluent when() without a callback: the proxy it returns is beyond PHPStan's inference.
            // @phpstan-ignore argument.templateType
            ->when($name !== null)->name((string) $name)
            ->when($phone !== null)->phone((string) $phone)
            ->when($email !== null)->email((string) $email)
            ->when($organization !== null)->organization((string) $organization);

        return $this->make($card);
    }

    public function email(string $address, ?string $subject = null, ?string $body = null): PendingQr
    {
        return $this->make(new Email($address, $subject, $body));
    }

    public function sms(string $number, ?string $message = null): PendingQr
    {
        return $this->make(new Sms($number, $message));
    }

    public function geo(float $latitude, float $longitude): PendingQr
    {
        return $this->make(new Geo($latitude, $longitude));
    }

    /**
     * A calendar event; its details now or by chaining: `->title()->starts()->ends()`.
     */
    public function event(?string $title = null, ?DateTimeInterface $starts = null, ?DateTimeInterface $ends = null, ?string $location = null): PendingQr
    {
        return $this->make(new Event($title, $starts, $ends, $location));
    }

    public function text(string $text): PendingQr
    {
        return $this->make(new Text($text));
    }

    /**
     * A code holding a registered kind of content, made with the options.
     */
    public function payload(string $name, mixed ...$options): PendingQr
    {
        return $this->make($this->payloads()->get($name, $options));
    }

    /**
     * Adds an output format: `$code->format($name)` writes with it.
     *
     * @param Closure(Container, array<array-key, mixed>): Writer $writer Given the container and the code's writer options.
     */
    public function extend(string $name, Closure $writer): void
    {
        $this->writers()->register($name, $writer);
    }

    public function writers(): WriterRegistry
    {
        return $this->container->make(WriterRegistry::class);
    }

    /**
     * Makes every cached copy of a code stale: a code, or content and the styles it was made with.
     */
    public function forget(PendingQr|string|Payload|ProvidesQrContent $code, Style|StyleBuilder|ProvidesQrStyle|Closure|string ...$styles): void
    {
        ($code instanceof PendingQr ? $code : $this->make($code)->style(...$styles))->forget();
    }

    /**
     * Makes every cached code stale.
     */
    public function flush(): void
    {
        $this->container->make(Pipeline::class)->flush();
    }

    /**
     * The registered parts, their labels in the application's language or `$locale`, unit paths and
     * the scan guards' limits, as plain data for a browser. See `Manifest`.
     *
     * @return array<string, mixed>
     */
    public function manifest(?string $locale = null): array
    {
        return $this->container->make(Manifest::class)->toArray($locale);
    }

    /**
     * A shape on its own as a small SVG: a module (or a whole eye preset) first, an eye preset by
     * name, or an outer and/or inner eye by name; or what a filter does. `Qr::icon(Module::Square)`,
     * `Qr::icon(eye: 'heart')`, `Qr::icon(outer: 'square')`, `Qr::icon(filter: 'wave')`.
     */
    public function icon(
        ModuleShape|ProvidesQrModule|ProvidesQrEye|string|null $shape = null,
        OuterEyeShape|ProvidesQrOuterEye|string|null $outer = null,
        InnerEyeShape|ProvidesQrInnerEye|string|null $inner = null,
        bool $withOuter = true,
        Corner|string|null $corner = null,
        ProvidesQrEye|string|null $eye = null,
        NamedFilter|ProvidesQrFilter|string|null $filter = null,
    ): Icon {
        $icons = $this->container->make(Icons::class);
        $corner = is_string($corner) ? Corner::from($corner) : $corner;

        return match (true) {
            $filter !== null => $icons->filter($filter),
            $eye !== null => $icons->eye($eye, corner: $corner),
            $shape instanceof ProvidesQrEye && ! $shape instanceof ProvidesQrModule => $icons->eye($shape, corner: $corner),
            $shape !== null => $icons->module($shape),
            $outer !== null && $inner !== null => $icons->eye(outer: $outer, inner: $inner, corner: $corner),
            $outer !== null => $icons->outerEye($outer, $corner),
            $inner !== null => $icons->innerEye($inner, $withOuter, $corner),
            default => throw ResolveException::cannot(null, 'a shape to draw an icon of'),
        };
    }

    /**
     * Reads the code in an image: PNG, JPEG, WebP or GIF bytes, a file (such as an upload from a
     * request), or a rendered code - with the decoder `qr.reading.decoder` names, or `$using`.
     *
     * @throws UnreadableCodeException When no code can be read, or the upload failed.
     */
    public function read(Rendered|Output|SplFileInfo|string $image, ?string $using = null): Decoded
    {
        $name = $using ?? (string) $this->container->make('config')->get('qr.reading.decoder', 'stack');
        $bytes = match (true) {
            is_string($image) => $image,
            $image instanceof SplFileInfo => self::fileContents($image),
            default => $image->contents(),
        };
        $decoded = $this->decoders()->get($name)->decode($bytes);

        return $decoded->decoder === null ? $decoded->withDecoder($name) : $decoded;
    }

    /**
     * A file's bytes; an upload that did not arrive, or a file that cannot be read, is no image.
     */
    private static function fileContents(SplFileInfo $file): string
    {
        $readable = ! ($file instanceof UploadedFile && ! $file->isValid()) && $file->isFile() && $file->isReadable();
        $bytes = $readable ? file_get_contents($file->getPathname()) : false;

        return $bytes === false ? throw UnreadableCodeException::unreadableFile() : $bytes;
    }

    public function decoders(): DecoderRegistry
    {
        return $this->container->make(DecoderRegistry::class);
    }

    public function payloads(): PayloadRegistry
    {
        return $this->container->make(PayloadRegistry::class);
    }

    public function styles(): StyleRegistry
    {
        return $this->container->make(StyleRegistry::class);
    }

    /**
     * The color names of the application, next to the CSS names.
     */
    public function colors(): ColorNames
    {
        return $this->container->make(ColorNames::class);
    }
}
