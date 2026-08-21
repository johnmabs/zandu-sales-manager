<?php

declare(strict_types=1);

namespace Zandu\Platform\Operations;

final class GracefulShutdown
{
    private bool $requested = false;

    public function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, $this->request(...));
        pcntl_signal(SIGINT, $this->request(...));
    }

    public function request(): void
    {
        $this->requested = true;
    }

    public function isRequested(): bool
    {
        return $this->requested;
    }
}
