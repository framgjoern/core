<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Stu\Component\Ship\ShipEnum;
use Stu\Orm\Repository\AllianceRepositoryInterface;
use Stu\Orm\Repository\FactionRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\DockingPrivilegeRepository")
 * @Table(
 *     name="stu_dockingrights",
 *     indexes={
 *         @Index(name="ship_idx", columns={"ships_id"})
 *     }
 * )
 **/
class DockingPrivilege implements DockingPrivilegeInterface
{
    /** @Id @Column(type="integer") @GeneratedValue * */
    private $id;

    /** @Column(type="integer") * */
    private $ships_id = 0;

    /** @Column(type="integer") * */
    private $target = 0;

    /** @Column(type="smallint") * */
    private $privilege_type = 0;

    /** @Column(type="smallint") * */
    private $privilege_mode = 0;

    /**
     * @ManyToOne(targetEntity="Ship", inversedBy="dockingPrivileges")
     * @JoinColumn(name="ships_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ship;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTargetId(): int
    {
        return $this->target;
    }

    public function setTargetId(int $targetId): DockingPrivilegeInterface
    {
        $this->target = $targetId;
        return $this;
    }

    public function getPrivilegeType(): int
    {
        return $this->privilege_type;
    }

    public function setPrivilegeType(int $privilegeType): DockingPrivilegeInterface
    {
        $this->privilege_type = $privilegeType;
        return $this;
    }

    public function getPrivilegeMode(): int
    {
        return $this->privilege_mode;
    }

    public function setPrivilegeMode(int $privilegeMode): DockingPrivilegeInterface
    {
        $this->privilege_mode = $privilegeMode;
        return $this;
    }

    public function getPrivilegeModeString(): string
    {
        // @todo refactor
        if ($this->getPrivilegeMode() == ShipEnum::DOCK_PRIVILEGE_MODE_ALLOW) {
            return _('Erlaubt');
        }
        return _('Verboten');
    }

    public function isDockingAllowed(): bool
    {
        return $this->getPrivilegeMode() == ShipEnum::DOCK_PRIVILEGE_MODE_ALLOW;
    }

    public function getTargetName(): string
    {
        // @todo refactor
        global $container;
        switch ($this->getPrivilegeType()) {
            case ShipEnum::DOCK_PRIVILEGE_USER:
                return $container->get(UserRepositoryInterface::class)->find((int)$this->getTargetId())->getUser();
            case ShipEnum::DOCK_PRIVILEGE_ALLIANCE:
                return $container->get(AllianceRepositoryInterface::class)->find((int)$this->getTargetId())->getName();
            case ShipEnum::DOCK_PRIVILEGE_FACTION:
                return $container->get(FactionRepositoryInterface::class)->find((int)$this->getTargetId())->getName();

        }
        return $container->get(ShipRepositoryInterface::class)->find($this->getTargetId())->getName();
    }

    public function getShip(): ShipInterface
    {
        return $this->ship;
    }

    public function setShip(ShipInterface $ship): DockingPrivilegeInterface
    {
        $this->ship = $ship;
        return $this;
    }
}
