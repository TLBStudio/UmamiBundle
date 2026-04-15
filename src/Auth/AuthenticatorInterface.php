<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Auth;

interface AuthenticatorInterface
{
    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;

    public function invalidate(): void;
}
