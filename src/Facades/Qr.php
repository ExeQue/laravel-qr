<?php

declare(strict_types=1);

namespace ExeQue\Qr\Facades;

use ExeQue\Qr\Qr as QrManager;
use ExeQue\Qr\Testing\QrFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \ExeQue\Qr\Builders\PendingQr make(string|\ExeQue\Qr\Payloads\Contracts\Payload|\ExeQue\Qr\Payloads\Contracts\ProvidesQrContent $content)
 * @method static \ExeQue\Qr\Builders\StyleBuilder style()
 * @method static \ExeQue\Qr\Validation\StyleRule rule()
 * @method static \ExeQue\Qr\Builders\PendingQr url(string $url)
 * @method static \ExeQue\Qr\Builders\PendingQr wifi(?string $ssid = null, ?string $password = null, \ExeQue\Qr\Payloads\Enums\WifiSecurity|string $security = \ExeQue\Qr\Payloads\Enums\WifiSecurity::Wpa, bool $hidden = false)
 * @method static \ExeQue\Qr\Builders\PendingQr vcard(?string $name = null, ?string $phone = null, ?string $email = null, ?string $organization = null)
 * @method static \ExeQue\Qr\Builders\PendingQr email(string $address, ?string $subject = null, ?string $body = null)
 * @method static \ExeQue\Qr\Builders\PendingQr sms(string $number, ?string $message = null)
 * @method static \ExeQue\Qr\Builders\PendingQr geo(float $latitude, float $longitude)
 * @method static \ExeQue\Qr\Builders\PendingQr event(?string $title = null, ?\DateTimeInterface $starts = null, ?\DateTimeInterface $ends = null, ?string $location = null)
 * @method static \ExeQue\Qr\Builders\PendingQr text(string $text)
 * @method static \ExeQue\Qr\Builders\PendingQr payload(string $name, mixed ...$options)
 * @method static \ExeQue\Qr\Registries\PayloadRegistry payloads()
 * @method static \ExeQue\Qr\Registries\WriterRegistry writers()
 * @method static void extend(string $name, \Closure $writer)
 * @method static void forget(\ExeQue\Qr\Builders\PendingQr|string|\ExeQue\Qr\Payloads\Contracts\Payload|\ExeQue\Qr\Payloads\Contracts\ProvidesQrContent $code, mixed ...$styles)
 * @method static void flush()
 * @method static \ExeQue\Qr\Registries\ModuleRegistry modules()
 * @method static \ExeQue\Qr\Registries\OuterEyeRegistry outerEyes()
 * @method static \ExeQue\Qr\Registries\InnerEyeRegistry innerEyes()
 * @method static \ExeQue\Qr\Registries\EyeRegistry eyes()
 * @method static \ExeQue\Qr\Registries\FilterRegistry filters()
 * @method static \ExeQue\Qr\Registries\GradientRegistry gradients()
 * @method static \ExeQue\Qr\Registries\StyleRegistry styles()
 * @method static \ExeQue\Qr\Colors\ColorNames colors()
 * @method static \ExeQue\Qr\Decoding\Decoded read(\ExeQue\Qr\Builders\Rendered|\ExeQue\Qr\Writers\Contracts\Output|\SplFileInfo|string $image, ?string $using = null)
 * @method static \ExeQue\Qr\Registries\DecoderRegistry decoders()
 * @method static \ExeQue\Qr\Icons\Icon icon(\ExeQue\Qr\Shapes\Contracts\ModuleShape|\ExeQue\Qr\Shapes\Contracts\ProvidesQrModule|\ExeQue\Qr\Shapes\Contracts\ProvidesQrEye|string|null $shape = null, \ExeQue\Qr\Shapes\Contracts\OuterEyeShape|\ExeQue\Qr\Shapes\Contracts\ProvidesQrOuterEye|string|null $outer = null, \ExeQue\Qr\Shapes\Contracts\InnerEyeShape|\ExeQue\Qr\Shapes\Contracts\ProvidesQrInnerEye|string|null $inner = null, bool $withOuter = true, \ExeQue\Qr\Rendering\Enums\Corner|string|null $corner = null, \ExeQue\Qr\Shapes\Contracts\ProvidesQrEye|string|null $eye = null, \ExeQue\Qr\Filters\Contracts\NamedFilter|\ExeQue\Qr\Filters\Contracts\ProvidesQrFilter|string|null $filter = null)
 * @method static array<string, mixed> manifest(list<string>|string|null $locales = null)
 *
 * @method static \ExeQue\Qr\Testing\QrFake assertRendered(?\Closure $callback = null)
 * @method static \ExeQue\Qr\Testing\QrFake assertNotRendered(?\Closure $callback = null)
 * @method static \ExeQue\Qr\Testing\QrFake assertRenderedCount(int $expected, ?\Closure $callback = null)
 * @method static \ExeQue\Qr\Testing\QrFake assertNothingRendered()
 *
 * @see QrManager
 */
class Qr extends Facade
{
    /**
     * Records the codes rendered instead of writing them, for tests.
     */
    public static function fake(): QrFake
    {
        // Faking twice keeps the one fake, with what it recorded.
        if (static::$app->bound(QrFake::class)) {
            return static::$app->make(QrFake::class);
        }

        $fake = static::$app->make(QrFake::class);
        static::$app->instance(QrFake::class, $fake);

        return $fake;
    }

    /**
     * The assertions are the fake's, once there is one.
     *
     * @param array<array-key, mixed> $arguments
     */
    public static function __callStatic($method, $arguments): mixed
    {
        if (str_starts_with($method, 'assert') && static::$app->bound(QrFake::class)) {
            return static::$app->make(QrFake::class)->{$method}(...$arguments);
        }

        return parent::__callStatic($method, $arguments);
    }

    protected static function getFacadeAccessor(): string
    {
        return QrManager::class;
    }
}
