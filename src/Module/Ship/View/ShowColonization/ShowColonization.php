<?php

declare(strict_types=1);

namespace Stu\Module\Ship\View\ShowColonization;

use request;
use Stu\Module\Colony\Lib\ColonyLibFactoryInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Orm\Repository\ColonyRepositoryInterface;

final class ShowColonization implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_COLONIZATION';

    private $shipLoader;

    private $colonyLibFactory;

    private $colonyRepository;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        ColonyLibFactoryInterface $colonyLibFactory,
        ColonyRepositoryInterface $colonyRepository
    ) {
        $this->shipLoader = $shipLoader;
        $this->colonyLibFactory = $colonyLibFactory;
        $this->colonyRepository = $colonyRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $colony = $this->colonyRepository->find((int)request::getIntFatal('colid'));

        if ($colony === null) {
            return;
        }
        // @todo add sanity checks

        $game->setPageTitle(_('Kolonie gründen'));
        $game->setTemplateFile('html/ajaxwindow.xhtml');
        $game->setMacro('html/shipmacros.xhtml/colonization');

        $game->setTemplateVar('currentColony', $colony);
        $game->setTemplateVar('SHIP', $ship);
        $game->setTemplateVar('COLONY_SURFACE', $this->colonyLibFactory->createColonySurface($colony));
    }
}
