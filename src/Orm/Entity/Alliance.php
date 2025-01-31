<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Noodlehaus\ConfigInterface;
use Stu\Component\Alliance\AllianceEnum;
use Stu\Orm\Repository\AllianceJobRepositoryInterface;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\AllianceRepository")
 * @Table(
 *     name="stu_alliances",
 *     indexes={
 *     }
 * )
 **/
class Alliance implements AllianceInterface
{
    /** @Id @Column(type="integer") @GeneratedValue * */
    private $id;

    /** @Column(type="string") */
    private $name = '';

    /** @Column(type="text") */
    private $description = '';

    /** @Column(type="string") */
    private $homepage = '';

    /** @Column(type="integer") */
    private $date = 0;

    /** @Column(type="integer", nullable=true) */
    private $faction_id;

    /** @Column(type="boolean") */
    private $accept_applications = false;

    /** @Column(type="string", length=32) */
    private $avatar = '';

    /**
     * @ManyToOne(targetEntity="Faction")
     * @JoinColumn(name="faction_id", referencedColumnName="id")
     */
    private $faction;

    /**
     * @OneToMany(targetEntity="User", mappedBy="alliance")
     */
    private $members;

    private $founder;

    private $successor;

    private $diplomatic;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): AllianceInterface
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): AllianceInterface
    {
        $this->description = $description;
        return $this;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    public function setHomepage(string $homepage): AllianceInterface
    {
        $this->homepage = $homepage;
        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): AllianceInterface
    {
        $this->date = $date;
        return $this;
    }

    public function getFactionId(): ?int
    {
        return $this->faction_id;
    }

    public function setFaction(?FactionInterface $faction): AllianceInterface
    {
        $this->faction = $faction;
        return $this;
    }

    public function getAcceptApplications(): bool
    {
        return $this->accept_applications;
    }

    public function setAcceptApplications(bool $acceptApplications): AllianceInterface
    {
        $this->accept_applications = $acceptApplications;
        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): AllianceInterface
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getFullAvatarPath(): string
    {
        // @todo refactor
        global $container;

        $config = $container->get(ConfigInterface::class);

        return sprintf(
            '%s/%s.png',
            $config->get('game.alliance_avatar_path'),
            $this->getAvatar()
        );
    }

    public function getFounder(): AllianceJobInterface
    {
        if ($this->founder === null) {
            // @todo refactor
            global $container;

            $this->founder = $container->get(AllianceJobRepositoryInterface::class)
                ->getSingleResultByAllianceAndType(
                    $this->getId(),
                    AllianceEnum::ALLIANCE_JOBS_FOUNDER
                );
        }
        return $this->founder;
    }

    public function getSuccessor(): ?AllianceJobInterface
    {
        if ($this->successor === null) {
            // @todo refactor
            global $container;

            $this->successor = $container->get(AllianceJobRepositoryInterface::class)
                ->getSingleResultByAllianceAndType(
                    $this->getId(),
                    AllianceEnum::ALLIANCE_JOBS_SUCCESSOR
                );
        }
        return $this->successor;
    }

    public function getDiplomatic(): ?AllianceJobInterface
    {
        if ($this->diplomatic === null) {
            // @todo refactor
            global $container;

            $this->diplomatic = $container->get(AllianceJobRepositoryInterface::class)
                ->getSingleResultByAllianceAndType(
                    $this->getId(),
                    AllianceEnum::ALLIANCE_JOBS_DIPLOMATIC
                );

        }
        return $this->diplomatic;
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }
}
