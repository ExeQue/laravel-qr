<?php

declare(strict_types=1);

namespace ExeQue\Qr\Builders;

use ExeQue\Qr\Writers\Contracts\Output;
use GuzzleHttp\Psr7\Stream;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A written code, and everything to do with it: save it, stream it, send it, embed it.
 */
final readonly class Rendered implements Htmlable, Responsable, Stringable
{
    public function __construct(
        private Output $output,
    ) {
    }

    /**
     * Writes the code to a disk - by name or instance, the default disk when null - from a stream.
     */
    public function save(string $path, Filesystem|string|null $disk = null): bool
    {
        $stream = $this->output->resource();

        try {
            return (bool) self::disk($disk)->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * The same as `save()`.
     */
    public function store(string $path, Filesystem|string|null $disk = null): bool
    {
        return $this->save($path, $disk);
    }

    /**
     * A response that downloads the code, streamed.
     */
    public function download(?string $filename = null): StreamedResponse
    {
        // A filename names the file, not where it goes.
        $filename = str_replace(['/', '\\'], '_', $filename ?? 'qr.'.$this->output->extension());

        return new StreamedResponse(function (): void {
            foreach ($this->output->chunks() as $chunk) {
                echo $chunk;
            }
        }, 200, [
            'Content-Type' => $this->output->mimeType(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, self::asciiName($filename)),
        ]);
    }

    /**
     * The code itself as the response.
     */
    public function toResponse($request): Response
    {
        return new Response($this->contents(), 200, [
            'Content-Type' => $this->output->mimeType(),
            'X-Content-Type-Options' => 'nosniff',
            // An SVG opened on its own runs nothing and loads nothing from elsewhere.
            ...($this->output->mimeType() === 'image/svg+xml' ? ['Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:"] : []),
        ]);
    }

    public function contents(): string
    {
        return $this->output->contents();
    }

    /**
     * @return iterable<int, string>
     */
    public function chunks(int $length = 8192): iterable
    {
        return $this->output->chunks($length);
    }

    /**
     * A new stream with the code, at its start; closing it is the caller's job.
     *
     * @return resource
     */
    public function toResource(): mixed
    {
        return $this->output->resource();
    }

    /**
     * A new PSR-7 stream with the code, at its start, for PSR-7 clients and responses.
     */
    public function toStream(): StreamInterface
    {
        return new Stream($this->output->resource());
    }

    /**
     * The code's own stream, handed over: closing it is the caller's job, and this can be read no more.
     *
     * @return resource
     */
    public function detach(): mixed
    {
        return $this->output->detach();
    }

    public function toBase64(): string
    {
        return base64_encode($this->contents());
    }

    /**
     * Base64 with `-` and `_` and no padding: safe in URLs and file names.
     */
    public function toBase64Url(): string
    {
        return rtrim(strtr($this->toBase64(), '+/', '-_'), '=');
    }

    public function toDataUri(): string
    {
        return "data:{$this->output->mimeType()};base64,{$this->toBase64()}";
    }

    public function mimeType(): string
    {
        return $this->output->mimeType();
    }

    public function extension(): string
    {
        return $this->output->extension();
    }

    public function output(): Output
    {
        return $this->output;
    }

    /**
     * SVG inline; an image as an `<img>` with a data URI.
     */
    public function toHtml(): string
    {
        return $this->output->mimeType() === 'image/svg+xml'
            ? $this->contents()
            : '<img src="'.$this->toDataUri().'" alt="'.e((string) __('qr::qr.label')).'">';
    }

    public function __toString(): string
    {
        return $this->contents();
    }

    private static function disk(Filesystem|string|null $disk): Filesystem
    {
        return $disk instanceof Filesystem ? $disk : Storage::disk($disk);
    }

    /**
     * The filename in plain ASCII, for clients that do not read the UTF-8 one.
     */
    private static function asciiName(string $filename): string
    {
        $ascii = (string) preg_replace('/[^\x20-\x7E]|[%\/\\\\]/u', '_', $filename);

        return $ascii === '' ? 'qr' : $ascii;
    }
}
