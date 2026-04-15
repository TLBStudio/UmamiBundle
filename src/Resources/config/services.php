<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tlb\UmamiBundle\Auth\CloudApiKeyAuthenticator;
use Tlb\UmamiBundle\Auth\SelfHostedAuthenticator;
use Tlb\UmamiBundle\Client\UmamiApiClient;
use Tlb\UmamiBundle\Event\UmamiEventSender;
use Tlb\UmamiBundle\Tracker\ScriptTagRenderer;
use Tlb\UmamiBundle\Twig\UmamiExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set('tlb_umami.tracker.renderer', ScriptTagRenderer::class);
    $services->alias(ScriptTagRenderer::class, 'tlb_umami.tracker.renderer');

    $services->set('tlb_umami.twig.extension', UmamiExtension::class)
        ->tag('twig.extension');
    $services->alias(UmamiExtension::class, 'tlb_umami.twig.extension');

    $services->set('tlb_umami.auth.cloud', CloudApiKeyAuthenticator::class);
    $services->set('tlb_umami.auth.self_hosted', SelfHostedAuthenticator::class);

    $services->set('tlb_umami.client', UmamiApiClient::class);
    $services->alias(UmamiApiClient::class, 'tlb_umami.client');

    $services->set('tlb_umami.event_sender', UmamiEventSender::class);
    $services->alias(UmamiEventSender::class, 'tlb_umami.event_sender');
};
