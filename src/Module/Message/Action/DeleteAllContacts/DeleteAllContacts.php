<?php

declare(strict_types=1);

namespace Stu\Module\Message\Action\DeleteAllContacts;

use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\ContactRepositoryInterface;

final class DeleteAllContacts implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_ALL_CONTACTS';

    private $contactRepository;

    public function __construct(
        ContactRepositoryInterface $contactRepository
    ) {
        $this->contactRepository = $contactRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $this->contactRepository->truncateByUser($game->getUser()->getId());

        $game->addInformation(_('Die Kontakte wurden gelöscht'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
