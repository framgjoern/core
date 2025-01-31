<?php

declare(strict_types=1);

namespace Stu\Module\Notes\View\ShowNewNote;

use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Repository\NoteRepositoryInterface;

final class ShowNewNote implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_NEW_NOTE';

    private $noteRepository;

    public function __construct(
        NoteRepositoryInterface $noteRepository
    ) {
        $this->noteRepository = $noteRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $note = $this->noteRepository->prototype();
        $note->setTitle('');
        $note->setText('');

        $game->setPageTitle("Notiz: " . $note->getTitle());

        $game->appendNavigationPart(
            'notes.php',
            _('Notizen')
        );
        $game->appendNavigationPart(
            sprintf(
                'notes.php?%s=1',
                static::VIEW_IDENTIFIER
            ),
            _('Neue Notiz')
        );
        $game->showMacro('html/notes.xhtml/note');
        $game->setTemplateVar('NOTE', $note);
        $game->setTemplateVar('IS_NEW', true);
    }
}
