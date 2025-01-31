<?php

namespace App\Controller\Admin;

use App\Entity\GameMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class GameModeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GameMode::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ChoiceField::new('value'),
        ];
    }
}
