<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Exceptions\InvalidColorException;

/**
 * A CSS named color (`rebeccapurple`), `transparent`, `currentColor`, or one
 * of the application's own names (see `ColorNames`).
 * `currentColor` takes the color of the surrounding text, so only SVG output can use it.
 */
final readonly class Named implements Color
{
    use IsColor;

    public const string CURRENT_COLOR = 'currentColor';

    public const string TRANSPARENT = 'transparent';

    // The named colors of CSS Color Module Level 4, section 6.1.
    private const array COLORS = [
        'aliceblue' => '#f0f8ff',
        'antiquewhite' => '#faebd7',
        'aqua' => '#00ffff',
        'aquamarine' => '#7fffd4',
        'azure' => '#f0ffff',
        'beige' => '#f5f5dc',
        'bisque' => '#ffe4c4',
        'black' => '#000000',
        'blanchedalmond' => '#ffebcd',
        'blue' => '#0000ff',
        'blueviolet' => '#8a2be2',
        'brown' => '#a52a2a',
        'burlywood' => '#deb887',
        'cadetblue' => '#5f9ea0',
        'chartreuse' => '#7fff00',
        'chocolate' => '#d2691e',
        'coral' => '#ff7f50',
        'cornflowerblue' => '#6495ed',
        'cornsilk' => '#fff8dc',
        'crimson' => '#dc143c',
        'cyan' => '#00ffff',
        'darkblue' => '#00008b',
        'darkcyan' => '#008b8b',
        'darkgoldenrod' => '#b8860b',
        'darkgray' => '#a9a9a9',
        'darkgreen' => '#006400',
        'darkgrey' => '#a9a9a9',
        'darkkhaki' => '#bdb76b',
        'darkmagenta' => '#8b008b',
        'darkolivegreen' => '#556b2f',
        'darkorange' => '#ff8c00',
        'darkorchid' => '#9932cc',
        'darkred' => '#8b0000',
        'darksalmon' => '#e9967a',
        'darkseagreen' => '#8fbc8f',
        'darkslateblue' => '#483d8b',
        'darkslategray' => '#2f4f4f',
        'darkslategrey' => '#2f4f4f',
        'darkturquoise' => '#00ced1',
        'darkviolet' => '#9400d3',
        'deeppink' => '#ff1493',
        'deepskyblue' => '#00bfff',
        'dimgray' => '#696969',
        'dimgrey' => '#696969',
        'dodgerblue' => '#1e90ff',
        'firebrick' => '#b22222',
        'floralwhite' => '#fffaf0',
        'forestgreen' => '#228b22',
        'fuchsia' => '#ff00ff',
        'gainsboro' => '#dcdcdc',
        'ghostwhite' => '#f8f8ff',
        'gold' => '#ffd700',
        'goldenrod' => '#daa520',
        'gray' => '#808080',
        'green' => '#008000',
        'greenyellow' => '#adff2f',
        'grey' => '#808080',
        'honeydew' => '#f0fff0',
        'hotpink' => '#ff69b4',
        'indianred' => '#cd5c5c',
        'indigo' => '#4b0082',
        'ivory' => '#fffff0',
        'khaki' => '#f0e68c',
        'lavender' => '#e6e6fa',
        'lavenderblush' => '#fff0f5',
        'lawngreen' => '#7cfc00',
        'lemonchiffon' => '#fffacd',
        'lightblue' => '#add8e6',
        'lightcoral' => '#f08080',
        'lightcyan' => '#e0ffff',
        'lightgoldenrodyellow' => '#fafad2',
        'lightgray' => '#d3d3d3',
        'lightgreen' => '#90ee90',
        'lightgrey' => '#d3d3d3',
        'lightpink' => '#ffb6c1',
        'lightsalmon' => '#ffa07a',
        'lightseagreen' => '#20b2aa',
        'lightskyblue' => '#87cefa',
        'lightslategray' => '#778899',
        'lightslategrey' => '#778899',
        'lightsteelblue' => '#b0c4de',
        'lightyellow' => '#ffffe0',
        'lime' => '#00ff00',
        'limegreen' => '#32cd32',
        'linen' => '#faf0e6',
        'magenta' => '#ff00ff',
        'maroon' => '#800000',
        'mediumaquamarine' => '#66cdaa',
        'mediumblue' => '#0000cd',
        'mediumorchid' => '#ba55d3',
        'mediumpurple' => '#9370db',
        'mediumseagreen' => '#3cb371',
        'mediumslateblue' => '#7b68ee',
        'mediumspringgreen' => '#00fa9a',
        'mediumturquoise' => '#48d1cc',
        'mediumvioletred' => '#c71585',
        'midnightblue' => '#191970',
        'mintcream' => '#f5fffa',
        'mistyrose' => '#ffe4e1',
        'moccasin' => '#ffe4b5',
        'navajowhite' => '#ffdead',
        'navy' => '#000080',
        'oldlace' => '#fdf5e6',
        'olive' => '#808000',
        'olivedrab' => '#6b8e23',
        'orange' => '#ffa500',
        'orangered' => '#ff4500',
        'orchid' => '#da70d6',
        'palegoldenrod' => '#eee8aa',
        'palegreen' => '#98fb98',
        'paleturquoise' => '#afeeee',
        'palevioletred' => '#db7093',
        'papayawhip' => '#ffefd5',
        'peachpuff' => '#ffdab9',
        'peru' => '#cd853f',
        'pink' => '#ffc0cb',
        'plum' => '#dda0dd',
        'powderblue' => '#b0e0e6',
        'purple' => '#800080',
        'rebeccapurple' => '#663399',
        'red' => '#ff0000',
        'rosybrown' => '#bc8f8f',
        'royalblue' => '#4169e1',
        'saddlebrown' => '#8b4513',
        'salmon' => '#fa8072',
        'sandybrown' => '#f4a460',
        'seagreen' => '#2e8b57',
        'seashell' => '#fff5ee',
        'sienna' => '#a0522d',
        'silver' => '#c0c0c0',
        'skyblue' => '#87ceeb',
        'slateblue' => '#6a5acd',
        'slategray' => '#708090',
        'slategrey' => '#708090',
        'snow' => '#fffafa',
        'springgreen' => '#00ff7f',
        'steelblue' => '#4682b4',
        'tan' => '#d2b48c',
        'teal' => '#008080',
        'thistle' => '#d8bfd8',
        'tomato' => '#ff6347',
        'turquoise' => '#40e0d0',
        'violet' => '#ee82ee',
        'wheat' => '#f5deb3',
        'white' => '#ffffff',
        'whitesmoke' => '#f5f5f5',
        'yellow' => '#ffff00',
        'yellowgreen' => '#9acd32',
    ];

    public string $name;

    public float $alpha;

    /**
     * An application name's color, as it was when this was made.
     */
    private ?Color $own;

    public function __construct(string $name, ?float $alpha = null)
    {
        $name = strtolower(trim($name));

        $this->own = self::names()->get($name);

        $this->name = match (true) {
            $name === strtolower(self::CURRENT_COLOR) => self::CURRENT_COLOR,
            $name === self::TRANSPARENT, isset(self::COLORS[$name]), $this->own !== null => $name,
            default => throw InvalidColorException::unparsable($name),
        };

        // An application's own color brings its alpha, unless one is given.
        $this->alpha = $alpha ?? ($this->name === self::TRANSPARENT ? 0.0 : $this->own?->alpha() ?? 1.0);

        self::checkRange('alpha', $this->alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(string $name, ?float $alpha = null): self
    {
        return new self($name, $alpha);
    }

    public static function exists(string $name): bool
    {
        return self::isBuiltIn($name) || self::names()->has($name);
    }

    /**
     * Whether CSS itself has the name.
     */
    public static function isBuiltIn(string $name): bool
    {
        $name = strtolower(trim($name));

        return isset(self::COLORS[$name]) || $name === self::TRANSPARENT || $name === strtolower(self::CURRENT_COLOR);
    }

    /**
     * The name whose color this is; there is none for most colors.
     */
    public static function fromXyz(array $xyz, float $alpha): static
    {
        $hex = substr(Hex::fromXyz($xyz, 1)->toCss(), 0, 7);
        $name = array_search($hex, self::COLORS, true)
            ?: array_search($hex, array_map(fn (Color $color): string => substr($color->toHex(), 0, 7), self::names()->all()), true);

        if ($name === false) {
            throw InvalidColorException::unnamed($hex);
        }

        return new self($name, $alpha);
    }

    /**
     * The application's own names, from the container.
     */
    private static function names(): ColorNames
    {
        return app(ColorNames::class);
    }

    public function isCurrentColor(): bool
    {
        return $this->name === self::CURRENT_COLOR;
    }

    public function toXyz(): array
    {
        if ($this->isCurrentColor()) {
            throw InvalidColorException::currentColorHasNoValue();
        }

        return match (true) {
            isset(self::COLORS[$this->name]) => new Hex(self::COLORS[$this->name])->toXyz(),
            $this->own !== null => Hex::from($this->own)->toXyz(),
            default => new Hex('#000000')->toXyz(),
        };
    }

    /**
     * The name, or `rgb()` when an alpha of its own makes it more than its name - and always for
     * an application's own name, which only the application knows.
     */
    public function toCss(): string
    {
        $ownAlpha = $this->name === self::TRANSPARENT ? $this->alpha !== 0.0 : $this->alpha < 1;

        return ($ownAlpha || $this->own !== null) && ! $this->isCurrentColor() ? Rgb::from($this)->toCss() : $this->name;
    }
}
