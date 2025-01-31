<?php

declare(strict_types=1);

namespace Stu\Module\Starmap\View\ShowSystem;

use AccessViolation;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\StarSystemRepositoryInterface;
use YRow;

final class ShowSystem implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SYSTEM';

    private $showSystemRequest;

    private $starSystemRepository;

    public function __construct(
        ShowSystemRequestInterface $showSystemRequest,
        StarSystemRepositoryInterface $starSystemRepository
    ) {
        $this->showSystemRequest = $showSystemRequest;
        $this->starSystemRepository = $starSystemRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        if (!$game->isAdmin()) {
            throw new AccessViolation();
        }
        $system = $this->starSystemRepository->find($this->showSystemRequest->getSystemId());

        if ($system === null) {
            return;
        }

        $fields = [];
        foreach (range(1, $system->getMaxY()) as $key => $value) {
            $fields[] = new YRow($value, 1, $system->getMaxX(), $system->getId());
        }

        $game->setTemplateFile('html/mapeditor_system.xhtml');
        $game->appendNavigationPart('starmap.php', _('Sternenkarte'));
        $game->appendNavigationPart(
            sprintf(
                'starmap.php?%s&sysid=%d',
                static::VIEW_IDENTIFIER,
                $system->getId()
            ),
            sprintf(_('System %s editieren'), $system->getName())
        );
        $game->setPageTitle(_('Sektion anzeigen'));
        $game->setTemplateVar('HEAD_ROW', range(1, $system->getMaxX()));
        $game->setTemplateVar('MAP_FIELDS', $fields);
    }
}