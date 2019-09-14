<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\Action\DeclineApplication;

use AccessViolation;
use PM;
use Stu\Module\Alliance\Lib\AllianceActionManagerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Alliance\View\Applications\Applications;
use Stu\Orm\Repository\AllianceJobRepositoryInterface;

final class DeclineApplication implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_DECLINE_APPLICATION';

    private $declineApplicationRequest;

    private $allianceJobRepository;

    private $allianceActionManager;

    public function __construct(
        DeclineApplicationRequestInterface $declineApplicationRequest,
        AllianceJobRepositoryInterface $allianceJobRepository,
        AllianceActionManagerInterface $allianceActionManager
    ) {
        $this->declineApplicationRequest = $declineApplicationRequest;
        $this->allianceJobRepository = $allianceJobRepository;
        $this->allianceActionManager = $allianceActionManager;
    }

    public function handle(GameControllerInterface $game): void
    {
        $alliance = $game->getUser()->getAlliance();

        if (!$this->allianceActionManager->mayEdit((int) $alliance->getId(), $game->getUser()->getId())) {
            new AccessViolation;
        }

        $appl = $this->allianceJobRepository->find($this->declineApplicationRequest->getApplicationId());
        if ($appl === null || $appl->getAllianceId() != $alliance->getId()) {
            new AccessViolation;
        }

        $this->allianceJobRepository->delete($appl);

        $text = sprintf(
            _('Deine Bewerbung bei der Allianz %s wurde abgelehnt'),
            $alliance->getName()
        );

        PM::sendPM(USER_NOONE, $appl->getUserId(), $text);

        $game->setView(Applications::VIEW_IDENTIFIER);

        $game->addInformation(_('Die Bewerbung wurde abgelehnt'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
