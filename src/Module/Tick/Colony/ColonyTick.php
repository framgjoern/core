<?php

namespace Stu\Module\Tick\Colony;

use Doctrine\Common\Collections\Collection;
use Stu\Component\Game\GameEnum;
use Stu\Lib\ColonyProduction\ColonyProduction;
use Stu\Module\Colony\Lib\ColonyStorageManagerInterface;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Database\Lib\CreateDatabaseEntryInterface;
use Stu\Module\Research\ResearchState;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Entity\PlanetFieldInterface;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\ModuleQueueRepositoryInterface;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;
use Stu\Orm\Repository\ResearchedRepositoryInterface;
use Stu\Orm\Repository\ShipRumpUserRepositoryInterface;

final class ColonyTick implements ColonyTickInterface
{
    public const PEOPLE_FOOD = 7;

    private $commodityRepository;

    private $researchedRepository;

    private $shipRumpUserRepository;

    private $moduleQueueRepository;

    private $planetFieldRepository;

    private $privateMessageSender;

    private $colonyStorageManager;

    private $colonyRepository;

    private $createDatabaseEntry;

    private $msg = [];

    public function __construct(
        CommodityRepositoryInterface $commodityRepository,
        ResearchedRepositoryInterface $researchedRepository,
        ShipRumpUserRepositoryInterface $shipRumpUserRepository,
        ModuleQueueRepositoryInterface $moduleQueueRepository,
        PlanetFieldRepositoryInterface $planetFieldRepository,
        PrivateMessageSenderInterface $privateMessageSender,
        ColonyStorageManagerInterface $colonyStorageManager,
        ColonyRepositoryInterface $colonyRepository,
        CreateDatabaseEntryInterface $createDatabaseEntry
    ) {
        $this->commodityRepository = $commodityRepository;
        $this->researchedRepository = $researchedRepository;
        $this->shipRumpUserRepository = $shipRumpUserRepository;
        $this->moduleQueueRepository = $moduleQueueRepository;
        $this->planetFieldRepository = $planetFieldRepository;
        $this->privateMessageSender = $privateMessageSender;
        $this->colonyStorageManager = $colonyStorageManager;
        $this->colonyRepository = $colonyRepository;
        $this->createDatabaseEntry = $createDatabaseEntry;
    }

    public function work(ColonyInterface $colony): void
    {
        $this->mainLoop($colony);
        $this->proceedStorage($colony);

        $this->colonyRepository->save($colony);

        $this->proceedModules($colony);
        $this->sendMessages($colony);
    }

    private function mainLoop(ColonyInterface $colony)
    {
        $i = 1;
        $storage = $colony->getStorage();
        $production = $colony->getProductionRaw();

        while (true) {
            $rewind = 0;
            foreach ($production as $commodityId => $pro) {
                if ($pro->getProduction() >= 0) {
                    continue;
                }
                $storageItem = $storage[$pro->getGoodId()] ?? null;
                if ($storageItem !== null && $storageItem->getAmount() + $pro->getProduction() >= 0) {
                    continue;
                }

                $field = $this->getBuildingToDeactivateByGood($colony, $commodityId);
                //echo $i." hit by good ".$field->getFieldId()." - produce ".$pro->getProduction()." MT ".microtime()."\n";
                $this->deactivateBuilding($colony, $field, $commodityId);
                $rewind = 1;
            }
            if ($rewind == 0 && $colony->getEpsProduction() < 0 && $colony->getEps() + $colony->getEpsProduction() < 0) {
                $field = $this->getBuildingToDeactivateByEpsUsage($colony,);
                //echo $i." hit by eps ".$field->getFieldId()." - complete usage ".$colony->getEpsProduction()." - usage ".$field->getBuilding()->getEpsProduction()." MT ".microtime()."\n";
                $this->deactivateBuilding($colony, $field, 0);
                $rewind = 1;
            }
            if ($rewind == 1) {
                reset($production);
                $i++;
                if ($i == 100) {
                    // SECURITY
                    //echo "HIT SECURITY BREAK\n";
                    break;
                }
                continue;
            }
            break;
        }
        $colony->setEps(min($colony->getMaxEps(), $colony->getEps() + $colony->getEpsProduction()));
    }

    private function deactivateBuilding(ColonyInterface $colony, PlanetFieldInterface $field, int $commodityId): void
    {
        if ($commodityId === 0) {
            $ext = "Energie";
        } else {
            $ext = $this->commodityRepository->find($commodityId)->getName();
        }

        $this->msg[] = $field->getBuilding()->getName() . " auf Feld " . $field->getFieldId() . " deaktiviert (Mangel an " . $ext . ")";

        $colony->upperWorkless($field->getBuilding()->getWorkers());
        $colony->lowerWorkers($field->getBuilding()->getWorkers());
        $colony->lowerMaxBev($field->getBuilding()->getHousing());
        $this->mergeProduction($colony, $field->getBuilding()->getGoods());
        $field->getBuilding()->postDeactivation($colony);

        $field->setActive(0);

        $this->planetFieldRepository->save($field);
    }

    private function getBuildingToDeactivateByGood(ColonyInterface $colony, int $commodityId): PlanetFieldInterface
    {
        $fields = $this->planetFieldRepository->getCommodityConsumingByColonyAndCommodity(
            $colony->getId(),
            $commodityId
        );

        return current($fields);
    }

    private function getBuildingToDeactivateByEpsUsage(ColonyInterface $colony): PlanetFieldInterface
    {
        $fields = $this->planetFieldRepository->getEnergyConsumingByColony($colony->getId(), 1);

        return current($fields);
    }

    private function proceedStorage(ColonyInterface $colony): void
    {
        $emigrated = 0;
        $production = $this->proceedFood($colony);
        $sum = $colony->getStorageSum();
        $storage = $colony->getStorage();

        foreach ($production as $commodityId => $obj) {
            if ($obj->getProduction() >= 0) {
                continue;
            }
            if ($commodityId == CommodityTypeEnum::GOOD_FOOD) {
                $storageItem = $storage[CommodityTypeEnum::GOOD_FOOD] ?? null;
                if ($storageItem === null && $obj->getProduction() < 1) {
                    $this->proceedEmigration($colony, true);
                    $emigrated = 1;
                } elseif ($storageItem->getAmount() + $obj->getProduction() < 0) {
                    $this->proceedEmigration($colony, true, abs($storageItem->getAmount() + $obj->getProduction()));
                    $emigrated = 1;
                }
            }
            $this->colonyStorageManager->lowerStorage(
                $colony,
                $this->commodityRepository->find($commodityId),
                abs($obj->getProduction())
            );
            $sum -= abs($obj->getProduction());
        }
        foreach ($production as $commodityId => $obj) {
            if ($obj->getProduction() <= 0 || !$obj->getGood()->isSaveable()) {
                continue;
            }
            if ($sum >= $colony->getMaxStorage()) {
                break;
            }
            if ($sum + $obj->getProduction() > $colony->getMaxStorage()) {
                $this->colonyStorageManager->upperStorage(
                    $colony,
                    $this->commodityRepository->find($commodityId),
                    $colony->getMaxStorage() - $sum
                );
                break;
            }
            $this->colonyStorageManager->upperStorage(
                $colony,
                $this->commodityRepository->find($commodityId),
                $obj->getProduction()
            );
            $sum += $obj->getProduction();
        }

        $current_research = $this->researchedRepository->getCurrentResearch($colony->getUserId());

        if ($current_research && $current_research->getActive()) {
            if (isset($production[$current_research->getResearch()->getGoodId()])) {
                (new ResearchState(
                    $this->researchedRepository,
                    $this->shipRumpUserRepository,
                    $this->privateMessageSender,
                    $this->createDatabaseEntry
                )
                )->advance(
                    $current_research,
                    $production[$current_research->getResearch()->getGoodId()]->getProduction()
                );
            }
        }
        if ($colony->getPopulation() > $colony->getMaxBev()) {
            $this->proceedEmigration($colony);
            return;
        }
        if ($colony->getPopulationLimit() > 0 && $colony->getPopulation() > $colony->getPopulationLimit() && $colony->getWorkless()) {
            if (($free = ($colony->getPopulationLimit() - $colony->getWorkers())) > 0) {
                $this->msg[] = sprintf(
                    _('Es sind %d Arbeitslose ausgewandert'),
                    ($colony->getWorkless() - $free)
                );
                $colony->setWorkless($free);
            } else {
                $this->msg[] = _('Es sind alle Arbeitslosen ausgewandert');
                $colony->setWorkless(0);
            }
        }
        if ($emigrated == 0) {
            $this->proceedImmigration($colony);
        }
    }

    private function proceedModules(ColonyInterface $colony): void
    {
        foreach ($this->moduleQueueRepository->getByColony((int) $colony->getId()) as $queue) {
            if ($colony->hasActiveBuildingWithFunction($queue->getBuildingFunction())) {
                $this->colonyStorageManager->upperStorage(
                    $colony,
                    $queue->getModule()->getCommodity(),
                    $queue->getAmount()
                );

                $this->msg[] = sprintf(
                    _('Es wurden %d %s hergestellt'),
                    $queue->getAmount(),
                    $queue->getModule()->getName()
                );
                $this->moduleQueueRepository->delete($queue);
            }
        }
    }

    /**
     * @return ColonyProduction[]
     */
    private function proceedFood(ColonyInterface $colony): array
    {
        $foodvalue = $colony->getBevFood();
        $prod = &$colony->getProductionRaw();
        if (!array_key_exists(CommodityTypeEnum::GOOD_FOOD, $prod)) {
            $obj = new ColonyProduction();
            $obj->setGoodId(CommodityTypeEnum::GOOD_FOOD);
            $obj->lowerProduction($foodvalue);
            $prod[CommodityTypeEnum::GOOD_FOOD] = $obj;
        } else {
            $prod[CommodityTypeEnum::GOOD_FOOD]->lowerProduction($foodvalue);
        }
        return $prod;
    }

    private function proceedImmigration(ColonyInterface $colony): void
    {
        // @todo
        $im = $colony->getImmigration();
        $colony->upperWorkless($im);
    }

    private function proceedEmigration(ColonyInterface $colony, $foodrelated = false, $foodmissing = false)
    {
        if ($colony->getWorkless()) {
            if ($foodmissing > 0) {
                $bev = $foodmissing * self::PEOPLE_FOOD;
                if ($bev > $colony->getWorkless()) {
                    $bev = $colony->getWorkless();
                }
            } else {
                if ($foodrelated) {
                    $bev = $colony->getWorkless();
                } else {
                    $bev = rand(1, $colony->getWorkless());
                }
            }
            $colony->lowerWorkless($bev);
            if ($foodrelated) {
                $this->msg[] = $bev . " Einwohner sind aufgrund des Nahrungsmangels ausgewandert";
            } else {
                $this->msg[] = $bev . " Einwohner sind ausgewandert";
            }
        }
    }

    private function sendMessages(ColonyInterface $colony): void
    {
        if ($this->msg === []) {
            return;
        }
        $text = "Tickreport der Kolonie " . $colony->getName() . "\n";
        foreach ($this->msg as $key => $msg) {
            $text .= $msg . "\n";
        }

        $this->privateMessageSender->send(GameEnum::USER_NOONE, (int)$colony->getUserId(), $text,
            PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY);

        $this->msg = [];
    }

    private function mergeProduction(ColonyInterface $colony, Collection $commodityProduction): void
    {
        $prod = $colony->getProductionRaw();
        foreach ($commodityProduction as $obj) {
            $commodityId = $obj->getGoodId();
            if (!array_key_exists($commodityId, $prod)) {
                $data = new ColonyProduction;
                $data->setGoodId($commodityId);
                $data->setProduction($obj->getAmount() * -1);
                $colony->setProductionRaw($colony->getProductionRaw() + array($commodityId => $data));
            } else {
                if ($obj->getAmount() < 0) {
                    $prod[$commodityId]->upperProduction(abs($obj->getAmount()));
                } else {
                    $prod[$commodityId]->lowerProduction($obj->getAmount());
                }
            }
        }
    }

}
