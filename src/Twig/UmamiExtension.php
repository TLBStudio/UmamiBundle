<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;
use Tlb\UmamiBundle\Tracker\ScriptTagRenderer;

final class UmamiExtension extends AbstractExtension
{
    public function __construct(private readonly ScriptTagRenderer $renderer)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('umami_script', $this->umamiScript(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function umamiScript(array $overrides = []): Markup
    {
        return new Markup($this->renderer->render($overrides), 'UTF-8');
    }
}
