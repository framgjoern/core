<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\BuyTradeLicense;

use request;
use Stu\Component\Game\GameEnum;
use Stu\Module\Ship\Lib\PositionCheckerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\Lib\ShipStorageManagerInterface;
use Stu\Module\Ship\View\ShowTradeMenu\ShowTradeMenu;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\TradeLicenseRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;

final class BuyTradeLicense implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_PAY_TRADELICENCE';

    private $shipLoader;

    private $tradeLicenseRepository;

    private $tradeLibFactory;

    private $tradePostRepository;

    private $shipStorageManager;

    private $shipRepository;

    private $positionChecker;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TradeLicenseRepositoryInterface $tradeLicenseRepository,
        TradeLibFactoryInterface $tradeLibFactory,
        TradePostRepositoryInterface $tradePostRepository,
        ShipStorageManagerInterface $shipStorageManager,
        ShipRepositoryInterface $shipRepository,
        PositionCheckerInterface $positionChecker
    ) {
        $this->shipLoader = $shipLoader;
        $this->tradeLicenseRepository = $tradeLicenseRepository;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradePostRepository = $tradePostRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->shipRepository = $shipRepository;
        $this->positionChecker = $positionChecker;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowTradeMenu::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        /** @var TradePostInterface $tradepost */
        $tradepost = $this->tradePostRepository->find((int) request::getIntFatal('postid'));
        if ($tradepost === null) {
            return;
        }

        if (!$this->positionChecker->checkPosition($ship, $tradepost->getShip())) {
            return;
        }
        $targetId = (int) request::getIntFatal('target');
        $mode = request::getStringFatal('method');

        if ($this->tradeLicenseRepository->getAmountByUser($userId) >= GameEnum::MAX_TRADELICENCE_COUNT) {
            return;
        }

        if ($this->tradeLicenseRepository->hasLicenseByUserAndTradePost($userId, (int) $tradepost->getId())) {
            return;
        }
        switch ($mode) {
            case 'ship':
                $obj = $this->shipRepository->find($targetId);
                if ($obj === null || $obj->getUserId() !== $userId) {
                    return;
                }
                if (!$this->positionChecker->checkPosition($tradepost->getShip(), $obj)) {
                    return;
                }

                $commodityId = (int) $tradepost->getLicenceCostGood()->getId();

                $storage = $obj->getStorage()[$commodityId] ?? null;
                if ($storage === null || $storage->getAmount() < $tradepost->calculateLicenceCost()) {
                    return;
                }
                $this->shipStorageManager->lowerStorage(
                    $obj,
                    $tradepost->getLicenceCostGood(),
                    $tradepost->calculateLicenceCost()
                );
                break;
            case 'account':
                /** @var TradePostInterface $targetTradepost */
                $targetTradepost = $this->tradePostRepository->find($targetId);
                if ($targetTradepost === null) {
                    return;
                }

                $storageManager = $this->tradeLibFactory->createTradePostStorageManager($targetTradepost, $userId);
                $commodityId = (int) $tradepost->getLicenceCostGood()->getId();
                $costs = (int) $tradepost->calculateLicenceCost();

                $stor = $storageManager->getStorage()[$commodityId] ?? null;
                if ($stor === null) {
                    return;
                }
                if ($stor->getAmount() < $costs) {
                    return;
                }
                if ($targetTradepost->getTradeNetwork() != $tradepost->getTradeNetwork()) {
                    return;
                }

                $storageManager->lowerStorage($commodityId, $costs);
                break;
            default:
                return;
        }
        $licence = $this->tradeLicenseRepository->prototype();
        $licence->setTradePost($tradepost);
        $licence->setUser($game->getUser());
        $licence->setDate(time());

        $this->tradeLicenseRepository->save($licence);
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
