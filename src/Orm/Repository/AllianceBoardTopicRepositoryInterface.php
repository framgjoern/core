<?php

namespace Stu\Orm\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\AllianceBoardTopicInterface;

interface AllianceBoardTopicRepositoryInterface extends ObjectRepository
{
    public function prototype(): AllianceBoardTopicInterface;

    public function save(AllianceBoardTopicInterface $post): void;

    public function delete(AllianceBoardTopicInterface $post): void;

    /**
     * @return AllianceBoardTopicInterface[]
     */
    public function getRecentByAlliance(int $allianceId, int $limit = 3): array;

    public function getAmountByBoardId(int $boardId): int;

    /**
     * @return AllianceBoardTopicInterface[]
     */
    public function getByBoardIdOrdered(int $boardId): array;
}