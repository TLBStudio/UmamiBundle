<?php

declare(strict_types=1);

namespace Tlb\UmamiBundle\Tests\Tracker;

use PHPUnit\Framework\TestCase;
use Tlb\UmamiBundle\Tracker\ScriptTagRenderer;
use Tlb\UmamiBundle\Tracker\TrackerConfig;
use Tlb\UmamiBundle\Twig\UmamiExtension;
use Twig\Markup;

final class ScriptTagRendererTest extends TestCase
{
    public function testRendersScriptTagWithConfiguredAttributes(): void
    {
        $renderer = new ScriptTagRenderer(new TrackerConfig(
            true,
            'https://analytics.example.com/script.js',
            'website-id',
            'https://analytics.example.com',
            true,
            false,
            ['example.com', 'www.example.com'],
            'marketing',
            true,
        ));

        $html = $renderer->render();

        self::assertStringContainsString('<script', $html);
        self::assertStringContainsString('defer', $html);
        self::assertStringContainsString('src="https://analytics.example.com/script.js"', $html);
        self::assertStringContainsString('data-website-id="website-id"', $html);
        self::assertStringContainsString('data-domains="example.com,www.example.com"', $html);
        self::assertStringContainsString('data-tag="marketing"', $html);
        self::assertStringNotContainsString('data-auto-track=', $html);
        self::assertStringNotContainsString('data-do-not-track=', $html);
        self::assertStringContainsString('data-cache="true"', $html);
        self::assertStringContainsString('</script>', $html);
    }

    public function testSupportsOverrides(): void
    {
        $renderer = new ScriptTagRenderer(new TrackerConfig(
            true,
            'https://analytics.example.com/script.js',
            'website-id',
            null,
            true,
            false,
            [],
            null,
            false,
        ));

        $html = $renderer->render([
            'src' => 'https://cdn.example.com/alt.js',
            'data-auto-track' => 'false',
            'defer' => false,
        ]);

        self::assertStringContainsString('src="https://cdn.example.com/alt.js"', $html);
        self::assertStringContainsString('data-auto-track="false"', $html);
        self::assertStringNotContainsString(' defer', $html);
    }

    public function testIncludesOnlyNonDefaultBooleansFromConfig(): void
    {
        $renderer = new ScriptTagRenderer(new TrackerConfig(
            true,
            'https://analytics.example.com/script.js',
            'website-id',
            null,
            false,
            true,
            [],
            null,
            false,
        ));

        $html = $renderer->render();

        self::assertStringContainsString('data-auto-track="false"', $html);
        self::assertStringContainsString('data-do-not-track="true"', $html);
        self::assertStringNotContainsString('data-cache=', $html);
    }

    public function testOmitsHostUrlWhenSameAsScriptOrigin(): void
    {
        $renderer = new ScriptTagRenderer(new TrackerConfig(
            true,
            'https://analytics.example.com/script.js',
            'website-id',
            'https://analytics.example.com',
            true,
            false,
            [],
            null,
            false,
        ));

        $html = $renderer->render();

        self::assertStringNotContainsString('data-host-url=', $html);
    }

    public function testTwigExtensionReturnsMarkup(): void
    {
        $renderer = new ScriptTagRenderer(new TrackerConfig(
            true,
            'https://analytics.example.com/script.js',
            'website-id',
            null,
            true,
            false,
            [],
            null,
            false,
        ));
        $extension = new UmamiExtension($renderer);

        $result = $extension->umamiScript();

        self::assertInstanceOf(Markup::class, $result);
        self::assertStringContainsString('data-website-id="website-id"', (string) $result);
    }
}
