<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\Action\CreateAlliance;

use Stu\Component\Alliance\AllianceEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Alliance\View\Create\Create;
use Stu\Orm\Repository\AllianceJobRepositoryInterface;
use Stu\Orm\Repository\AllianceRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class CreateAlliance implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CREATE_ALLIANCE';

    private $createAllianceRequest;

    private $allianceJobRepository;

    private $allianceRepository;
    private $userRepository;


    public function __construct(
        CreateAllianceRequestInterface $createAllianceRequest,
        AllianceJobRepositoryInterface $allianceJobRepository,
        AllianceRepositoryInterface $allianceRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->createAllianceRequest = $createAllianceRequest;
        $this->allianceJobRepository = $allianceJobRepository;
        $this->allianceRepository = $allianceRepository;
        $this->userRepository = $userRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();

        $name = $this->createAllianceRequest->getName();
        $faction_mode = $this->createAllianceRequest->getFactionMode();
        $description = $this->createAllianceRequest->getDescription();

        if (mb_strlen($name) < 5) {
            $game->setView(Create::VIEW_IDENTIFIER);
            $game->addInformation(_('Der Name muss aus mindestens 5 Zeichen bestehen'));
            return;
        }

        $alliance = $this->allianceRepository->prototype();
        $alliance->setName($name);
        $alliance->setDescription($description);
        $alliance->setDate(time());
        if ($faction_mode === 1) {
            $alliance->setFaction($user->getFaction());
        }

        $this->allianceRepository->save($alliance);

        $user->setAlliance($alliance);

        $this->userRepository->save($user);

        $this->allianceJobRepository->truncateByUser($userId);

        $job = $this->allianceJobRepository->prototype();
        $job->setType(AllianceEnum::ALLIANCE_JOBS_FOUNDER);
        $job->setAlliance($alliance);
        $job->setUser($user);

        $this->allianceJobRepository->save($job);

        $game->addInformation(_('Die Allianz wurde gegründet'));
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
