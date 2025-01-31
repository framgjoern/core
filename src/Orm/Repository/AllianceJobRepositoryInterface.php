<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\AllianceJobInterface;

/**
 * @method null|AllianceJobInterface find(integer $id)
 */
interface AllianceJobRepositoryInterface extends ObjectRepository
{
    public function prototype(): AllianceJobInterface;

    public function save(AllianceJobInterface $post): void;

    public function delete(AllianceJobInterface $post): void;

    /**
     * @return AllianceJobInterface[]
     */
    public function getByUser(int $userId): array;

    /**
     * @return AllianceJobInterface[]
     */
    public function getByAlliance(int $allianceId): array;

    public function truncateByUser(int $userId): void;

    public function truncateByAlliance(int $allianceId): void;

    /**
     * @return AllianceJobInterface[]
     */
    public function getByAllianceAndType(int $allianceId, int $typeId): array;

    public function getByUserAndAllianceAndType(int $userId, int $allianceId, int $type): ?AllianceJobInterface;

    public function getSingleResultByAllianceAndType(int $allianceId, int $typeId): ?AllianceJobInterface;
}
