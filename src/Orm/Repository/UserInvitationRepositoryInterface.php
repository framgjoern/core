<?php

namespace Stu\Orm\Repository;

use DateTimeInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Entity\UserInvitationInterface;

/**
 * @method null|UserInvitationInterface find(integer $id)
 */
interface UserInvitationRepositoryInterface extends ObjectRepository
{
    public function prototype(): UserInvitationInterface;

    public function save(UserInvitationInterface $userInvitation): void;

    public function getInvitationsByUser(UserInterface $user): array;

    public function getByToken(string $token): ?UserInvitationInterface;

    public function truncateExpiredTokens(DateTimeInterface $ttl): void;
}
