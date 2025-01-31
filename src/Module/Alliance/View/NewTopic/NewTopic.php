<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\View\NewTopic;

use AccessViolation;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Entity\AllianceBoardInterface;
use Stu\Orm\Repository\AllianceBoardRepositoryInterface;

final class NewTopic implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_NEW_TOPIC';

    private $newTopicRequest;

    private $allianceBoardRepository;

    public function __construct(
        NewTopicRequestInterface $newTopicRequest,
        AllianceBoardRepositoryInterface $allianceBoardRepository
    ) {
        $this->newTopicRequest = $newTopicRequest;
        $this->allianceBoardRepository = $allianceBoardRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $alliance = $game->getUser()->getAlliance();

        /** @var AllianceBoardInterface $board */
        $board = $this->allianceBoardRepository->find($this->newTopicRequest->getBoardId());
        if ($board === null || $board->getAllianceId() != $alliance->getId()) {
            throw new AccessViolation();
        }

        $boardId = $board->getId();

        $game->setPageTitle(_('Allianzforum'));

        $game->appendNavigationPart(
            'alliance.php',
            _('Allianz')
        );
        $game->appendNavigationPart(
            'alliance.php?SHOW_BOARDS=1',
            _('Forum')
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?SHOW_BOARD=1&bid=%d',
                $boardId
            ),
            $board->getName()
        );
        $game->appendNavigationPart(
            sprintf(
                'alliance.php?SHOW_NEW_TOPIC=1&bid=%d',
                $boardId
            ),
            _('Thema erstellen')
        );

        $game->setTemplateFile('html/allianceboardcreatetopic.xhtml');
        $game->setTemplateVar('BOARD_ID', $boardId);
    }
}
