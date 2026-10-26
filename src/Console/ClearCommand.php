<?php

declare(strict_types=1);

namespace ExeQue\Qr\Console;

use ExeQue\Qr\Qr;
use Illuminate\Console\Command;

/**
 * @internal Registered by the service provider.
 */
final class ClearCommand extends Command
{
    protected $name = 'qr:clear';

    protected $description = 'Make every cached QR code stale';

    public function handle(Qr $qr): int
    {
        $qr->flush();

        $this->components->info('Cached QR codes cleared.');

        return self::SUCCESS;
    }
}
