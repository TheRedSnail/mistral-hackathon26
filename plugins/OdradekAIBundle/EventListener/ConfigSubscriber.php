<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use MauticPlugin\OdradekAIBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'formAlias'  => 'odradek_ai_config',
            'formType'   => ConfigType::class,
            'formTheme'  => '@OdradekAI/FormTheme/Config/_config_odradek_ai_config_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('OdradekAIBundle'),
        ]);
    }
}
