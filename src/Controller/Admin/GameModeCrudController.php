<?php

namespace App\Controller\Admin;

use App\Entity\GameMode;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
