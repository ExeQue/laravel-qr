<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers;

/**
 * Who made a code and on what terms, written into the file: XMP (Dublin Core)
 * in PNG, JPEG and WebP, text chunks in PNG too, and `<title>` and `<metadata>` in SVG.
 */
final readonly class Metadata
{
    /**
     * @param string|null $url Where the terms of use are, such as a license page.
     * @param list<string> $keywords
     */
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $description = null,
        public ?string $copyright = null,
        public ?string $url = null,
        public array $keywords = [],
    ) {
    }

    /**
     * @param list<string> $keywords
     */
    public static function make(
        ?string $title = null,
        ?string $author = null,
        ?string $description = null,
        ?string $copyright = null,
        ?string $url = null,
        array $keywords = [],
    ): self {
        return new self($title, $author, $description, $copyright, $url, $keywords);
    }

    /**
     * These values, with `$other`'s set ones taking their place: a code's own over the defaults.
     */
    public function merge(self $other): self
    {
        return new self(
            $other->title ?? $this->title,
            $other->author ?? $this->author,
            $other->description ?? $this->description,
            $other->copyright ?? $this->copyright,
            $other->url ?? $this->url,
            $other->keywords === [] ? $this->keywords : $other->keywords,
        );
    }

    public function isEmpty(): bool
    {
        return $this->toTextChunks() === [] && $this->url === null;
    }

    /**
     * The values as the keywords PNG text chunks use (PNG specification, section 11.3.4.2).
     *
     * @return array<string, string>
     */
    public function toTextChunks(): array
    {
        return array_filter([
            'Title' => $this->title,
            'Author' => $this->author,
            'Description' => $this->description,
            'Copyright' => $this->copyright,
            'Source' => $this->url,
            'Keywords' => $this->keywords === [] ? null : implode(', ', $this->keywords),
        ], fn (?string $value): bool => $value !== null);
    }

    /**
     * An RDF description in Dublin Core terms, for XMP and SVG `<metadata>`.
     */
    public function toRdf(): string
    {
        $alternative = fn (string $value): string => '<rdf:Alt><rdf:li xml:lang="x-default">'.self::escape($value).'</rdf:li></rdf:Alt>';

        $properties = array_filter([
            $this->title === null ? null : '<dc:title>'.$alternative($this->title).'</dc:title>',
            $this->author === null ? null : '<dc:creator><rdf:Seq><rdf:li>'.self::escape($this->author).'</rdf:li></rdf:Seq></dc:creator>',
            $this->description === null ? null : '<dc:description>'.$alternative($this->description).'</dc:description>',
            $this->copyright === null ? null : '<dc:rights>'.$alternative($this->copyright).'</dc:rights>',
            $this->keywords === [] ? null : '<dc:subject><rdf:Bag>'.implode('', array_map(fn (string $keyword): string => '<rdf:li>'.self::escape($keyword).'</rdf:li>', $this->keywords)).'</rdf:Bag></dc:subject>',
            $this->url === null ? null : '<xmpRights:WebStatement>'.self::escape($this->url).'</xmpRights:WebStatement>',
        ]);

        return '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xmpRights="http://ns.adobe.com/xap/1.0/rights/">'
            .implode('', $properties)
            .'</rdf:Description></rdf:RDF>';
    }

    /**
     * An XMP packet, as image files embed it.
     */
    public function toXmp(): string
    {
        return "<?xpacket begin=\"\u{FEFF}\" id=\"W5M0MpCehiHzreSzNTczkc9d\"?>"
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'.$this->toRdf().'</x:xmpmeta>'
            .'<?xpacket end="r"?>';
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
