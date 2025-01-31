<?php

declare(strict_types=1);

namespace Stu\Module\Message\Action\DeletePms;

use Stu\Module\Message\Action\DeletePms\DeletePmsRequestInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\PrivateMessageRepositoryInterface;

final class DeletePms implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DELETE_PMS';

    private $deletePmsRequest;

    private $privateMessageRepository;

    public function __construct(
        DeletePmsRequestInterface $deletePmsRequest,
        PrivateMessageRepositoryInterface $privateMessageRepository
    ) {
        $this->deletePmsRequest = $deletePmsRequest;
        $this->privateMessageRepository = $privateMessageRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();

        foreach ($this->deletePmsRequest->getDeletionIds() as $messageId) {
            $pm = $this->privateMessageRepository->find($messageId);

            if ($pm === null || $pm->getRecipient() !== $user) {
                continue;
            }

            $this->privateMessageRepository->delete($pm);
        }
        $game->addInformation(_('Die Nachrichten wurden gelöscht'));
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
