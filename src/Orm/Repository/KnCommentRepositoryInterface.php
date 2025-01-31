<?php

namespace Stu\Orm\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Stu\Orm\Entity\KnCommentInterface;
use Stu\Orm\Entity\KnPostInterface;

interface KnCommentRepositoryInterface extends ObjectRepository
{
    /**
     * @return KnCommentInterface[]
     */
    public function getByPost(int $postId): array;

    public function getAmountByPost(KnPostInterface $post): int;

    public function prototype(): KnCommentInterface;

    public function save(KnCommentInterface $comment): void;

    public function delete(KnCommentInterface $comment): void;

    public function truncateByUser(int $userId): void;
}
