<?php

declare(strict_types=1);

namespace Stu\Module\Communication\View\ShowKnRating;

use Stu\Component\Communication\Kn\KnFactoryInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;

final class ShowKnRating implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_KN_RATING';

    private $knFactory;

    public function __construct(
        KnFactoryInterface $knFactory
    ) {
        $this->knFactory = $knFactory;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->showMacro('html/commmacros.xhtml/knrating');

        $post = $game->getViewContext()['knPost'] ?? null;

        if ($post === null) {
            return;
        }

        $game->setTemplateVar(
            'STATUS_BAR',
            $this->knFactory
                ->createKnItem($post, $game->getUser())
                ->getRatingBar()
        );
    }
}
