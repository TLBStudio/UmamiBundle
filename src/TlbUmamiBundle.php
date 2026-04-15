<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Tlb\UmamiBundle\DependencyInjection\TlbUmamiExtension;

final class TlbUmamiBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new TlbUmamiExtension();
        }

        return $this->extension;
    }
}
