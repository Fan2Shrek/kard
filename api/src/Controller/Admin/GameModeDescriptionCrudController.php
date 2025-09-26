<?php

namespace App\Controller\Admin;

use App\Entity\GameModeDescription;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

/**
 * @extends AbstractCrudController<GameModeDescription>
 */
class GameModeDescriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GameModeDescription::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('gameMode'),
            TextEditorField::new('description'),
            ImageField::new('img')
                ->setBasePath('/uploads/game_mode')
                ->setUploadDir('public/uploads/game_mode')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
        ];
    }
}
