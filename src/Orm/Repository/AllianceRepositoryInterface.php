<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\AllianceInterface;

/**
 * @method null|AllianceInterface find(integer $id)
 */
interface AllianceRepositoryInterface extends ObjectRepository
{
    public function prototype(): AllianceInterface;

    public function save(AllianceInterface $post): void;

    public function delete(AllianceInterface $post): void;

    public function findAllOrdered(): array;

    public function findByApplicationState(bool $acceptApplications): array;
}
