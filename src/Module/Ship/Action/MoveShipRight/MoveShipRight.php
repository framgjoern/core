<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\MoveShipRight;

use request;
use Stu\Module\Ship\Lib\ShipMover;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\Lib\ShipMoverInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;

final class MoveShipRight implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_MOVE_RIGHT';

    private $shipLoader;

    private $shipMover;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        ShipMoverInterface $shipMover
    ) {
        $this->shipLoader = $shipLoader;
        $this->shipMover = $shipMover;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $fields = request::postString('navapp');
        if ($fields <= 0 || $fields > 9 || strlen($fields) > 1) {
            $fields = 1;
        }
        $this->shipMover->checkAndMove(
            $ship,
            $ship->getPosX() + $fields,
            $ship->getPosY()
        );
        $game->addInformationMerge($this->shipMover->getInformations());

        if ($ship->getIsDestroyed()) {
            return;
        }

        $game->setView(ShowShip::VIEW_IDENTIFIER);
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
