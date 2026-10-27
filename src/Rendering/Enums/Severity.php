<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering\Enums;

/**
 * How a code stands with the scan guards, from best to worst.
 */
enum Severity: string
{
    /** Nothing to say. */
    case Fine = 'fine';

    /** Changed to stay scannable: drawn a little otherwise than asked. */
    case Adjusted = 'adjusted';

    /** Drawn as asked, but it may not scan everywhere. */
    case Warning = 'warning';

    /** Not drawn: a guard set to throw would throw. */
    case Refused = 'refused';

    public function rank(): int
    {
        return array_search($this, self::cases(), true) ?: 0;
    }
}
