<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Component\Game\GameEnum;
use Stu\Orm\Entity\Colony;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Entity\Map;
use Stu\Orm\Entity\MapRegionSettlement;
use Stu\Orm\Entity\PlanetType;
use Stu\Orm\Entity\StarSystemInterface;
use Stu\Orm\Entity\UserInterface;

final class ColonyRepository extends EntityRepository implements ColonyRepositoryInterface
{
    public function prototype(): ColonyInterface
    {
        return new Colony();
    }

    public function save(ColonyInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->persist($post);
        $em->flush($post);
    }

    public function delete(ColonyInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->remove($post);
        $em->flush($post);
    }

    public function getAmountByUser(int $userId, bool $isMoon = false): int
    {
        return (int)$this->getEntityManager()->createQuery(
            sprintf(
                'SELECT count(c.id) from %s c WHERE c.user_id = :userId AND c.colonies_classes_id IN (
                    SELECT pt.id FROM %s pt WHERE pt.is_moon = :isMoon
                )',
                Colony::class,
                PlanetType::class
            )
        )->setParameters([
            'userId' => $userId,
            'isMoon' => $isMoon,
        ])->getSingleScalarResult();
    }

    public function getStartingByFaction(int $factionId): iterable
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT c FROM %s c INDEX BY c.id WHERE c.user_id = :userId AND c.colonies_classes_id IN (
                    SELECT pt.id FROM %s pt WHERE pt.allow_start = 1
                ) AND c.systems_id IN (
                    SELECT m.systems_id FROM %s m WHERE m.systems_id > 0 AND m.region_id IN (
                        SELECT mrs.region_id from %s mrs WHERE mrs.faction_id = :factionId
                    )
                )',
                Colony::class,
                PlanetType::class,
                Map::class,
                MapRegionSettlement::class
            )
        )->setParameters([
            'userId' => GameEnum::USER_NOONE,
            'factionId' => $factionId
        ])->getResult();
    }

    public function getByPosition(?StarSystemInterface $starSystem, int $sx, int $sy): ?ColonyInterface
    {
        return $this->findOneBy([
            'systems_id' => $starSystem,
            'sx' => $sx,
            'sy' => $sy
        ]);
    }

    public function getOrderedListByUser(UserInterface $user): iterable
    {
        return $this->findBy(
            ['user_id' => $user],
            ['id' => 'asc']
        );
    }

    public function getByTick(int $tick): iterable
    {
        /**
         * @todo the tick value is not in use atm
         */
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT c FROM %s c WHERE c.user_id != :userId',
            Colony::class
            )
        )->setParameters([
            'userId' => GameEnum::USER_NOONE,
        ])->getResult();
    }
}
