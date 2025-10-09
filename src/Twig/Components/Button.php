<?php

namespace App\Twig\Components;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Twig\Extra\Html\Cva;

#[AsTwigComponent(template: 'components/ui/Button.html.twig')]
class Button
{
    public ?string $href = null;

    public ?string $text = null;

    public bool $unstyled = false;

    public ?string $variant = 'default';

    public ?string $size = 'medium';

    public ?string $iconLeft = null;

    public ?string $iconRight = null;

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PreMount(priority: 1)]
    public function preMount(array $data): array
    {
        $resolver = (new OptionsResolver())
            ->setDefaults([
                'href' => null,
                'text' => null,
                'iconLeft' => null,
                'iconRight' => null,
                'size' => 'medium',
                'unstyled' => false,
                'variant' => 'default',
            ])
            ->setAllowedTypes('href', ['null', 'string'])
            ->setAllowedTypes('variant', ['null', 'string'])
            ->setAllowedTypes('size', ['null', 'string'])
            ->setAllowedTypes('text', ['null', 'string'])
            ->setAllowedTypes('iconLeft', ['null', 'string'])
            ->setAllowedTypes('iconRight', ['null', 'string'])
            ->setAllowedValues('variant', ['default', 'danger', 'success', 'transparent'])
            ->setAllowedValues('size', [null, 'small', 'medium', 'large'])
            ->setIgnoreUndefined(true)
            ->setNormalizer(
                'size',
                static fn (Options $options, string $value): ?string => $options['unstyled'] ? null : $value
            )
            ->setNormalizer(
                'variant',
                static fn (Options $options, ?string $value): ?string => $options['unstyled'] ? null : $value
            )
        ;

        return $resolver->resolve($data) + $data;
    }

    #[ExposeInTemplate()]
    public function getButton(): Cva
    {
        return new Cva(
            variants: [
                'unstyled' => [
                    'false' => 'button',
                    'true' => '',
                ],
                'variant' => [
                    'default' => '',
                    'danger' => 'button--danger',
                    'success' => 'button--success',
                    'transparent' => 'button--transparent',
                ],
                'size' => [
                    'small' => 'button--small',
                    'medium' => 'button--medium',
                    'large' => 'button--large',
                ],
            ]
        );
    }
}
