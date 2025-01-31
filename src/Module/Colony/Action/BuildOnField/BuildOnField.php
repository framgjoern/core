<?php

declare(strict_types=1);

namespace Stu\Module\Colony\Action\BuildOnField;

use request;
use Stu\Module\Colony\Lib\ColonyStorageManagerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowBuildResult\ShowBuildResult;
use Stu\Orm\Entity\BuildingCostInterface;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Entity\PlanetFieldInterface;
use Stu\Orm\Repository\BuildingFieldAlternativeRepositoryInterface;
use Stu\Orm\Repository\BuildingRepositoryInterface;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;
use Stu\Orm\Repository\ResearchedRepositoryInterface;

final class BuildOnField implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_BUILD';

    private $colonyLoader;

    private $buildingFieldAlternativeRepository;

    private $researchedRepository;

    private $buildingRepository;

    private $planetFieldRepository;

    private $colonyStorageManager;

    private $colonyRepository;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        BuildingFieldAlternativeRepositoryInterface $buildingFieldAlternativeRepository,
        ResearchedRepositoryInterface $researchedRepository,
        BuildingRepositoryInterface $buildingRepository,
        PlanetFieldRepositoryInterface $planetFieldRepository,
        ColonyStorageManagerInterface $colonyStorageManager,
        ColonyRepositoryInterface $colonyRepository
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->buildingFieldAlternativeRepository = $buildingFieldAlternativeRepository;
        $this->researchedRepository = $researchedRepository;
        $this->buildingRepository = $buildingRepository;
        $this->planetFieldRepository = $planetFieldRepository;
        $this->colonyStorageManager = $colonyStorageManager;
        $this->colonyRepository = $colonyRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowBuildResult::VIEW_IDENTIFIER);

        $user = $game->getUser();
        $userId = $user->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            request::indInt('id'),
            $userId
        );

        $colonyId = $colony->getId();

        $field = $this->planetFieldRepository->getByColonyAndFieldId($colonyId, (int)request::indInt('fid'));

        if ($field === null) {
            return;
        }

        if ($field->getTerraformingId() > 0) {
            return;
        }
        $building = $this->buildingRepository->find((int) request::indInt('bid'));
        if ($building === null) {
            return;
        }

        $buildingId = $building->getId();
        $researchId = $building->getResearchId();

        if ($researchId > 0 && $this->researchedRepository->hasUserFinishedResearch($researchId, $userId) === false) {
            return;
        }
        if ($building->getBuildableFields()->containsKey((int) $field->getFieldType()) === false) {
            return;
        }
        if (
            $building->hasLimitColony() &&
            $this->planetFieldRepository->getCountByColonyAndBuilding($colonyId, $buildingId) >= $building->getLimitColony()
        ) {
            $game->addInformationf(
                _('Dieses Gebäude kann auf dieser Kolonie nur %d mal gebaut werden'),
                $building->getLimitColony()
            );
            return;
        }
        if ($building->hasLimit() && $this->planetFieldRepository->getCountByBuildingAndUser($buildingId, $userId) >= $building->getLimit()) {
            $game->addInformationf(
                _('Dieses Gebäude kann insgesamt nur %d mal gebaut werden'),
                $building->getLimit()
            );
            return;
        }
        $storage = $colony->getStorage();
        foreach ($building->getCosts() as $obj) {
            $commodityId = $obj->getGoodId();

            if ($field->hasBuilding()) {

                $currentBuildingCost = $field->getBuilding()->getCosts();

                $result = array_filter(
                    $currentBuildingCost->toArray(),
                    function (BuildingCostInterface $buildingCost) use ($commodityId): bool {
                        return $commodityId === $buildingCost->getGoodId();
                    }
                );

                if (
                    !$storage->containsKey($commodityId) &&
                    $result === []
                ) {
                    $game->addInformationf(
                        _('Es werden %d %s benötigt - Es ist jedoch keines vorhanden'),
                        $obj->getAmount(),
                        $obj->getGood()->getName()
                    );
                    return;
                }
            } else {
                if (!$storage->containsKey($commodityId)) {
                    $game->addInformationf(
                        _('Es werden %s %s benötigt - Es ist jedoch keines vorhanden'),
                        $obj->getAmount(),
                        $obj->getGood()->getName()
                    );
                    return;
                }
            }
            if (!$storage->containsKey($commodityId)) {
                $amount = 0;
            } else {
                $amount = $storage[$commodityId]->getAmount();
            }
            if ($field->hasBuilding()) {
                $result = array_filter(
                    $currentBuildingCost->toArray(),
                    function (BuildingCostInterface $buildingCost) use ($commodityId): bool {
                        return $commodityId === $buildingCost->getGoodId();
                    }
                );
                if ($result !== []) {
                    $amount += current($result)->getHalfAmount();
                }
            }
            if ($obj->getAmount() > $amount) {
                $game->addInformationf(
                    _('Es werden %d %s benötigt - Vorhanden sind nur %d'),
                    $obj->getAmount(),
                    $obj->getGood()->getName(),
                    $amount
                );
                return;
            }
        }

        if ($colony->getEps() < $building->getEpsCost()) {
            $game->addInformationf(
                _('Zum Bau wird %d Energie benötigt - Vorhanden ist nur %d'),
                $building->getEpsCost(),
                $colony->getEps()
            );
            return;
        }

        if ($field->hasBuilding()) {
            if ($colony->getEps() > $colony->getMaxEps() - $field->getBuilding()->getEpsStorage()) {
                if ($colony->getMaxEps() - $field->getBuilding()->getEpsStorage() < $building->getEpsCost()) {
                    $game->addInformation(_('Nach der Demontage steht nicht mehr genügend Energie zum Bau zur Verfügung'));
                    return;
                }
            }
            $this->removeBuilding($field, $colony, $game);
        }

        foreach ($building->getCosts() as $obj) {
            $this->colonyStorageManager->lowerStorage($colony, $obj->getGood(), $obj->getAmount());
        }
        // Check for alternative building
        $alt_building = $this->buildingFieldAlternativeRepository->getByBuildingAndFieldType(
            $buildingId,
            (int) $field->getFieldType()
        );
        if ($alt_building !== null) {
            $building = $alt_building->getAlternativeBuilding();
        }

        $colony->lowerEps($building->getEpsCost());
        $field->setBuilding($building);
        $field->setActive(time() + $building->getBuildtime());

        $this->colonyRepository->save($colony);

        $this->planetFieldRepository->save($field);

        $game->addInformationf(
            _('%s wird gebaut - Fertigstellung: %s'),
            $building->getName(),
            date('d.m.Y H:i', $field->getBuildtime())
        );
    }

    private function removeBuilding(PlanetFieldInterface $field, ColonyInterface $colony, GameControllerInterface $game)
    {
        if (!$field->hasBuilding()) {
            return;
        }
        if (!$field->getBuilding()->isRemoveAble()) {
            return;
        }
        $this->deActivateBuilding($field, $colony, $game);
        $colony->lowerMaxStorage($field->getBuilding()->getStorage());
        $colony->lowerMaxEps($field->getBuilding()->getEpsStorage());
        $game->addInformationf(
            _('%s auf Feld %d wurde demontiert'),
            $field->getBuilding()->getName(),
            $field->getFieldId()
        );
        $game->addInformation(_('Es konnten folgende Waren recycled werden'));
        foreach ($field->getBuilding()->getCosts() as $value) {
            $halfAmount = $value->getHalfAmount();
            if ($colony->getStorageSum() + $halfAmount > $colony->getMaxStorage()) {
                $amount = $colony->getMaxStorage() - $colony->getStorageSum();
            } else {
                $amount = $halfAmount;
            }
            if ($amount <= 0) {
                break;
            }

            $this->colonyStorageManager->upperStorage($colony, $value->getGood(), $amount);

            $game->addInformationf('%d %s', $amount, $value->getGood()->getName());
        }
        $field->clearBuilding();

        $this->planetFieldRepository->save($field);

        $this->colonyRepository->save($colony);
    }

    protected function deActivateBuilding(PlanetFieldInterface $field, ColonyInterface $colony, GameControllerInterface $game)
    {
        if (!$field->hasBuilding()) {
            return;
        }
        if (!$field->isActivateAble()) {
            return;
        }
        if (!$field->isActive()) {
            return;
        }
        $colony->upperWorkless($field->getBuilding()->getWorkers());
        $colony->lowerWorkers($field->getBuilding()->getWorkers());
        $colony->lowerMaxBev($field->getBuilding()->getHousing());
        $field->setActive(0);

        $this->planetFieldRepository->save($field);

        $this->colonyRepository->save($colony);

        $field->getBuilding()->postDeactivation($colony);

        $game->addInformationf(
            _('%s auf Feld %d wurde deaktiviert'),
            $field->getBuilding()->getName(),
            $field->getFieldId()
        );
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
