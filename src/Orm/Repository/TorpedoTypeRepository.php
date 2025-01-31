<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Orm\Entity\Researched;
use Stu\Orm\Entity\TorpedoType;

final class TorpedoTypeRepository extends EntityRepository implements TorpedoTypeRepositoryInterface
{
    public function getForUser(int $userId): array
    {
        /** @noinspection SyntaxError */
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT t FROM %s t INDEX BY t.id WHERE t.research_id IN (
                    SELECT r.research_id from %s r WHERE r.aktiv = 0 AND r.user_id = :userId
                )',
                TorpedoType::class,
                Researched::class,
            )
        )
            ->setParameter('userId', $userId)
            ->getResult();
    }

    public function getByLevel(int $level): array
    {
        /** @noinspection SyntaxError */
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT t FROM %s t INDEX BY t.id WHERE t.level = :level',
                TorpedoType::class,
            )
        )
            ->setParameter('level', $level)
            ->getResult();
    }
}