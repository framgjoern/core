<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\DeleteDockPrivilege;

use request;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowDockingPrivileges\ShowDockingPrivileges;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Repository\DockingPrivilegeRepositoryInterface;

final class DeleteDockPrivilege implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_DOCKPRIVILEGE';

    private $shipLoader;

    private $dockingPrivilegeRepository;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        DockingPrivilegeRepositoryInterface $dockingPrivilegeRepository
    ) {
        $this->shipLoader = $shipLoader;
        $this->dockingPrivilegeRepository = $dockingPrivilegeRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $game->setView(ShowDockingPrivileges::VIEW_IDENTIFIER);
        $privilege = $this->dockingPrivilegeRepository->find((int) request::getIntFatal('privilegeid'));

        if ($privilege->getShip()->getId() !== $ship->getId()) {
            return;
        }

        $this->dockingPrivilegeRepository->delete($privilege);
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
