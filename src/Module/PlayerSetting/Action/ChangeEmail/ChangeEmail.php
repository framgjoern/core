<?php

declare(strict_types=1);

namespace Stu\Module\PlayerSetting\Action\ChangeEmail;

use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ChangeEmail implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CHANGE_EMAIL';

    private $changeEmailRequest;

    private $userRepository;

    public function __construct(
        ChangeEmailRequestInterface $changeEmailRequest,
        UserRepositoryInterface $userRepository
    ) {
        $this->changeEmailRequest = $changeEmailRequest;
        $this->userRepository = $userRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $value = $this->changeEmailRequest->getEmailAddress();
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $game->addInformation(_('Die E-Mailadresse ist ungültig'));
            return;
        }

        $user = $game->getUser();

        $user->setEmail($value);

        $this->userRepository->save($user);

        $game->addInformation(_('Deine E-Mailadresse wurde geändert'));
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
