<?php

declare(strict_types=1);

namespace Stu\Module\Ship\View\ShowTradeMenuPayment;

use AccessViolation;
use request;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\TradeLicenseRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;
use Stu\Orm\Repository\TradeStorageRepositoryInterface;

final class ShowTradeMenuPayment implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_TRADEMENU_CHOOSE_PAYMENT';

    private $shipLoader;

    private $tradeLicenseRepository;

    private $tradeLibFactory;

    private $tradePostRepository;

    private $tradeStorageRepository;

    private $shipRepository;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TradeLicenseRepositoryInterface $tradeLicenseRepository,
        TradeLibFactoryInterface $tradeLibFactory,
        TradePostRepositoryInterface $tradePostRepository,
        TradeStorageRepositoryInterface $tradeStorageRepository,
        ShipRepositoryInterface $shipRepository
    ) {
        $this->shipLoader = $shipLoader;
        $this->tradeLicenseRepository = $tradeLicenseRepository;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradePostRepository = $tradePostRepository;
        $this->tradeStorageRepository = $tradeStorageRepository;
        $this->shipRepository = $shipRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );
        $game->setMacro('html/shipmacros.xhtml/entity_not_available');

        /**
         * @var TradePostInterface $tradepost
         */
        $tradepost = $this->tradePostRepository->find((int) request::getIntFatal('postid'));
        if ($tradepost === null) {
            return;
        }

        if (!$ship->canInteractWith($tradepost->getShip())) {
            return;
        }

        $game->showMacro('html/shipmacros.xhtml/trademenupayment');

        $game->setTemplateVar('TRADEPOST', $this->tradeLibFactory->createTradeAccountTal($tradepost, $userId));
        $game->setTemplateVar('SHIP', $ship);

        if (!$this->tradeLicenseRepository->hasLicenseByUserAndTradePost($userId, (int) $tradepost->getId())) {
            $licenseCostGood = $tradepost->getLicenceCostGood();
            $licenseCost = $tradepost->calculateLicenceCost();

            $game->setTemplateVar(
                'DOCKED_SHIPS_FOR_LICENSE',
                $this->shipRepository->getWithTradeLicensePayment(
                    $userId,
                    $tradepost->getShipId(),
                    $licenseCostGood->getId(),
                    $licenseCost
                )
            );
            $game->setTemplateVar(
                'ACCOUNTS_FOR_LICENSE',
                $this->tradeStorageRepository->getByTradeNetworkAndUserAndCommodityAmount(
                    $tradepost->getTradeNetwork(),
                    $userId,
                    $licenseCostGood->getId(),
                    $licenseCost
                )
            );
        }
    }
}
