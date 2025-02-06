<?php

namespace App\Form\Admin;

use App\Entity\Purchase\Purchase;
use App\Repository\Purchase\DurationPurchaseRepository;
use App\Repository\Purchase\OneTimePurchaseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseItemType extends AbstractType
{
    public function __construct(
        private OneTimePurchaseRepository $oneTimePurchaseRepository,
        private DurationPurchaseRepository $durationPurchaseRepository,
    ) {  
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('purchase', EntityType::class, [
                'label' => false,
                'class' => Purchase::class,
                'choices' => array_merge(
                    $this->oneTimePurchaseRepository->findAll(),
                    $this->durationPurchaseRepository->findAll(),
                ),
                'choice_label' => 'getNameWithType',
                'placeholder' => 'Choisissez une offre',
            ])
        ;
    }
}
