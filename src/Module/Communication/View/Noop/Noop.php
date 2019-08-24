<?php

declare(strict_types=1);

namespace Stu\Module\Communication\View\Noop;

use Stu\Control\GameControllerInterface;
use Stu\Control\ViewControllerInterface;

final class Noop implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'NOOP';

    public function handle(GameControllerInterface $game): void
    {
        $game->setTemplateFile('html/ajaxempty.xhtml');
        $game->setAjaxMacro('html/sitemacros.xhtml/noop');
    }
}