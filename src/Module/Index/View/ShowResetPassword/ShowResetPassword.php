<?php

declare(strict_types=1);

namespace Stu\Module\Index\View\ShowResetPassword;

use InvalidParamException;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ShowResetPassword implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_RESET_PASSWORD';

    private $showResetPasswordRequest;

    private $userRepository;

    public function __construct(
        ShowResetPasswordRequestInterface $showResetPasswordRequest,
        UserRepositoryInterface $userRepository
    ) {
        $this->showResetPasswordRequest = $showResetPasswordRequest;
        $this->userRepository = $userRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $this->userRepository->getByResetToken($this->showResetPasswordRequest->getToken());
        if ($user === null) {
            throw new InvalidParamException;
        }
        $game->setTemplateFile('html/index_resetpassword.xhtml');
        $game->setPageTitle(_('Password zurücksetzen - Star Trek Universe'));
        $game->setTemplateVar('TOKEN', $user->getPasswordToken());
    }
}
