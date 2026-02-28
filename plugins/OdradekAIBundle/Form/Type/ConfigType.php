<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('odradek_ai_api_key', PasswordType::class, [
            'label'       => 'plugin.odradek_ai.config.api_key',
            'required'    => false,
            'attr'        => ['placeholder' => 'sk-...', 'autocomplete' => 'off'],
            'always_empty' => false,
        ]);

        $builder->add('odradek_ai_model', ChoiceType::class, [
            'label'   => 'plugin.odradek_ai.config.model',
            'choices' => [
                'Mistral Large (Latest)' => 'mistral-large-latest',
                'Mistral Small (Latest)' => 'mistral-small-latest',
                'Codestral (Latest)'     => 'codestral-latest',
            ],
            'required' => true,
        ]);

        $builder->add('odradek_ai_enabled', CheckboxType::class, [
            'label'    => 'plugin.odradek_ai.config.enabled',
            'required' => false,
        ]);

        $builder->add('odradek_ai_max_tokens', IntegerType::class, [
            'label' => 'plugin.odradek_ai.config.max_tokens',
            'data'  => 8000,
            'attr'  => ['min' => 1000, 'max' => 32000],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'odradek_ai_config';
    }

    public function getName(): string
    {
        return 'odradek_ai_config';
    }
}
