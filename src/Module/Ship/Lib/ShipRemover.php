<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib;

use Stu\Component\Game\GameEnum;
use Stu\Component\Ship\ShipEnum;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\FleetRepositoryInterface;
use Stu\Orm\Repository\ShipCrewRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\ShipRumpRepositoryInterface;
use Stu\Orm\Repository\ShipStorageRepositoryInterface;
use Stu\Orm\Repository\ShipSystemRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ShipRemover implements ShipRemoverInterface
{
    private $shipSystemRepository;

    private $shipStorageRepository;

    private $shipCrewRepository;

    private $fleetRepository;

    private $shipRepository;

    private $userRepository;

    private $shipRumpRepository;

    public function __construct(
        ShipSystemRepositoryInterface $shipSystemRepository,
        ShipStorageRepositoryInterface $shipStorageRepository,
        ShipCrewRepositoryInterface $shipCrewRepository,
        FleetRepositoryInterface $fleetRepository,
        ShipRepositoryInterface $shipRepository,
        UserRepositoryInterface $userRepository,
        ShipRumpRepositoryInterface $shipRumpRepository
    ) {
        $this->shipSystemRepository = $shipSystemRepository;
        $this->shipStorageRepository = $shipStorageRepository;
        $this->shipCrewRepository = $shipCrewRepository;
        $this->fleetRepository = $fleetRepository;
        $this->shipRepository = $shipRepository;
        $this->userRepository = $userRepository;
        $this->shipRumpRepository = $shipRumpRepository;
    }

    public function destroy(ShipInterface $ship): void
    {
        $ship->deactivateSystems();

        if ($ship->isFleetLeader()) {
            $this->changeFleetLeader($ship);
        }

        $ship->setFormerRumpId($ship->getRump()->getId());
        $ship->setRump($this->shipRumpRepository->find(ShipEnum::TRUMFIELD_CLASS));
        $ship->setHuell((int) round($ship->getMaxHuell()/20));
        $ship->setUser($this->userRepository->find(GameEnum::USER_NOONE));
        $ship->setBuildplan(null);
        $ship->setShield(0);
        $ship->setEps(0);
        $ship->setFleetId(0);
        $ship->setAlertState(1);
        $ship->setWarpState(false);
        $ship->setDockedTo(null);
        $ship->setName(_('Trümmer'));
        $ship->setIsDestroyed(true);
        $ship->cancelRepair();

        $this->shipSystemRepository->truncateByShip((int) $ship->getId());
        // @todo Torpedos löschen

        $this->shipRepository->save($ship);
    }

    public function remove(ShipInterface $ship): void
    {
        if ($ship->isFleetLeader()) {
            $this->changeFleetLeader($ship);
        }
        $ship->deactivateTraktorBeam();

        $this->shipStorageRepository->truncateForShip((int) $ship->getId());
        $this->shipCrewRepository->truncateByShip((int) $ship->getId());
        $this->shipSystemRepository->truncateByShip((int) $ship->getId());

        $this->shipRepository->delete($ship);
    }

    private function changeFleetLeader(ShipInterface $obj): void
    {
        $fleet = $obj->getFleet();

        $ship = current(
            array_filter(
                $obj->getFleet()->getShips()->toArray(),
                function (ShipInterface $ship) use ($obj): bool {
                    return $ship->getId() !== $obj->getId();
                }
            )
        );

        if (!$ship) {
            $this->fleetRepository->delete($fleet);
            $obj->setFleetId(0);
            return;
        }
        $obj->getFleet()->setLeadShip($ship);

        $this->fleetRepository->save($fleet);
    }
}
