<?php

declare(strict_types=1);

namespace Stu\Module\Colony\Action\BuildTorpedos;

use request;
use Stu\Module\Colony\Lib\ColonyStorageManagerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowColony\ShowColony;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\TorpedoTypeRepositoryInterface;

final class BuildTorpedos implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_BUILD_TORPEDOS';

    private $colonyLoader;

    private $torpedoTypeRepository;

    private $colonyStorageManager;

    private $colonyRepository;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        TorpedoTypeRepositoryInterface $torpedoTypeRepository,
        ColonyStorageManagerInterface $colonyStorageManager,
        ColonyRepositoryInterface $colonyRepository
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->torpedoTypeRepository = $torpedoTypeRepository;
        $this->colonyStorageManager = $colonyStorageManager;
        $this->colonyRepository = $colonyRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            request::indInt('id'),
            $userId
        );

        $buildableTorpedoTypes = $this->torpedoTypeRepository->getForUser($userId);

        $torps = request::postArray('torps');
        $storage = $colony->getStorage();
        $msg = array();
        foreach ($torps as $torp_id => $count) {
            if (!array_key_exists($torp_id, $buildableTorpedoTypes)) {
                continue;
            }
            $count = intval($count);
            $torp = $buildableTorpedoTypes[$torp_id];
            if ($torp->getEnergyCost() * $count > $colony->getEps()) {
                $count = floor($colony->getEps() / $torp->getEnergyCost());
            }
            if ($count <= 0) {
                continue;
            }
            foreach ($torp->getProductionCosts() as $id => $cost) {
                if (!$storage->containsKey($cost->getGoodId())) {
                    $count = 0;
                    break;
                }
                if ($count * $cost->getAmount() > $storage[$cost->getGoodId()]->getAmount()) {
                    $count = floor($storage[$cost->getGoodId()]->getAmount() / $cost->getAmount());
                }
            }
            if ($count == 0) {
                continue;
            }
            foreach ($torp->getProductionCosts() as $id => $cost) {
                $this->colonyStorageManager->lowerStorage($colony, $cost->getGood(), $cost->getAmount() * $count);
            }

            $this->colonyStorageManager->upperStorage($colony, $torp->getCommodity(), $count * $torp->getProductionAmount());

            $msg[] = sprintf(
                _('Es wurden %d Torpedos des Typs %s hergestellt'),
                $count * $torp->getProductionAmount(),
                $torp->getName()
            );
            $colony->lowerEps($count * $torp->getEnergyCost());
        }
        $this->colonyRepository->save($colony);

        if (count($msg) > 0) {
            $game->addInformationMerge($msg);
        } else {
            $game->addInformation(_('Es wurden keine Torpedos hergestellt'));
        }
        $game->setView(ShowColony::VIEW_IDENTIFIER);
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
