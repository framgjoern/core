<?php

namespace Stu\Orm\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\TradeShoutboxInterface;

interface TradeShoutboxRepositoryInterface extends ObjectRepository
{
    /**
     * @return TradeShoutboxInterface[]
     */
    public function getByTradeNetwork(int $tradeNetworkId): array;

    public function deleteHistory(int $tradeNetworkId, int $limit = 30): void;

    public function prototype(): TradeShoutboxInterface;

    public function save(TradeShoutboxInterface $tradeShoutbox): void;

    public function truncateByUser(int $userId): void;
}