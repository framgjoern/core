<?php

namespace Stu\Orm\Entity;

use Lib\AllianceMemberWrapper;

interface AllianceInterface
{
    public function getId(): int;

    public function getName(): string;

    public function setName(string $name): AllianceInterface;

    public function getDescription(): string;

    public function setDescription(string $description): AllianceInterface;

    public function getHomepage(): string;

    public function setHomepage(string $homepage): AllianceInterface;

    public function getDate(): int;

    public function setDate(int $date): AllianceInterface;

    public function getFactionId(): int;

    public function setFactionId(int $faction_id): AllianceInterface;

    public function getAcceptApplications(): bool;

    public function setAcceptApplications(bool $acceptApplications): AllianceInterface;

    public function getAvatar(): string;

    public function setAvatar(string $avatar): AllianceInterface;

    public function getFullAvatarPath(): string;

    public function getFounder(): AllianceJobInterface;

    public function getSuccessor(): ?AllianceJobInterface;

    public function getDiplomatic(): ?AllianceJobInterface;

    /**
     * @return AllianceMemberWrapper[]
     */
    public function getMembers(): array;
}