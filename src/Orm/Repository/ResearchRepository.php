<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Orm\Entity\Research;
use Stu\Orm\Entity\Researched;
use Stu\Orm\Entity\ResearchInterface;

final class ResearchRepository extends EntityRepository implements ResearchRepositoryInterface
{

    public function getAvailableResearch(int $userId): array
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT t FROM %s t WHERE t.id NOT IN (
                    SELECT r.research_id from %s r WHERE r.user_id = :userId
                )',
                Research::class,
                Researched::class,
            )
        )
            ->setParameter('userId', $userId)
            ->getResult();
    }

    /**
     * Retrieves all tech entries for a faction. It relys on some fancy id magic, so consider this a temporary solution
     */
    public function getForFaction(int $factionId): array
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT t FROM %s t WHERE t.id LIKE :factionId OR t.id LIKE \'%%0\'',
                Research::class,
            )
        )
            ->setParameter('factionId', sprintf('%%%d', $factionId))
            ->getResult();
    }

    public function getPlanetColonyLimitByUser(int $userId): int
    {
        return (int)$this->getEntityManager()->createQuery(
            sprintf(
                'SELECT SUM(r.upper_planetlimit) FROM %s r WHERE r.id IN (
                    SELECT ru.research_id FROM %s ru WHERE ru.user_id = :userId AND ru.aktiv = 0
                )',
                Research::class,
                Researched::class
            )
        )->setParameters([
            'userId' => $userId
        ])->getSingleScalarResult();
    }

    public function getMoonColonyLimitByUser(int $userId): int
    {
        return (int)$this->getEntityManager()->createQuery(
            sprintf(
                'SELECT SUM(r.upper_moonlimit) FROM %s r WHERE r.id IN (
                    SELECT ru.research_id FROM %s ru WHERE ru.user_id = :userId AND ru.aktiv = 0
                )',
                Research::class,
                Researched::class
            )
        )->setParameters([
            'userId' => $userId
        ])->getSingleScalarResult();
    }

    public function save(ResearchInterface $research): void
    {
        $em = $this->getEntityManager();

        $em->persist($research);
        $em->flush($research);
    }
}
