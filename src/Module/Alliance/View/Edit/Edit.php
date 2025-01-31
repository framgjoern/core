<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\View\Edit;

use AccessViolation;
use Stu\Module\Alliance\Lib\AllianceActionManagerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;

final class Edit implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'EDIT_ALLIANCE';

    private $allianceActionManager;

    public function __construct(
        AllianceActionManagerInterface $allianceActionManager
    ) {
        $this->allianceActionManager = $allianceActionManager;
    }

    public function handle(GameControllerInterface $game): void
    {
        $alliance = $game->getUser()->getAlliance();

        if (!$this->allianceActionManager->mayEdit((int) $alliance->getId(), $game->getUser()->getId())) {
            throw new AccessViolation();
        }
        $game->setPageTitle(_('Allianz editieren'));

        $game->appendNavigationPart(
            'alliance.php',
            _('Allianz')
        );
        $game->appendNavigationPart(
            'alliance.php?EDIT_ALLIANCE=1',
            _('Editieren')
        );
        $game->setTemplateFile('html/allianceedit.xhtml');
        $game->setTemplateVar('ALLIANCE', $alliance);
        $game->setTemplateVar(
            'CAN_EDIT_FACTION_MODE',
            $this->allianceActionManager->mayEditFactionMode($alliance, $game->getUser()->getFactionId())
        );
    }
}
