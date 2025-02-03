<?php

namespace App\Twig\Components;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Twig\Extra\Html\Cva;

#[AsTwigComponent(template: 'components/ui/LinkButton.html.twig')]
class LinkButton
{
    public string $href;

    public string $text;

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
                'iconLeft' => null,
                'iconRight' => null,
                'size' => 'medium',
                'unstyled' => false,
                'variant' => 'default',
            ])
            ->setRequired(['href', 'text'])
            ->setAllowedTypes('variant', ['null', 'string'])
            ->setAllowedTypes('size', ['null', 'string'])
            ->setAllowedTypes('iconLeft', ['null', 'string'])
            ->setAllowedTypes('iconRight', ['null', 'string'])
            ->setAllowedValues('variant', ['default', 'danger', 'success']) // Add more variants if needed
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
    public function getLinkButton(): Cva
    {
        return new Cva(
            variants: [
                'unstyled' => [
                    'false' => 'link-button',
                    'true' => '',
                ],
                'variant' => [
                    'default' => 'link-button--default',
                    'danger' => 'link-button--danger',
                    'success' => 'link-button--success',
                ],
                'size' => [
                    'small' => 'link-button--small',
                    'medium' => 'link-button--medium',
                    'large' => 'link-button--large',
                ],
            ]
        );
    }
}
