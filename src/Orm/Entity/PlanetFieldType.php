<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\PlanetFieldTypeRepository")
 * @Table(
 *     name="stu_colonies_fieldtypes",
 *     indexes={@Index(name="field_id_idx", columns={"field_id"})}
 * )
 **/
class PlanetFieldType implements PlanetFieldTypeInterface
{
    /** @Id @Column(type="integer") @GeneratedValue * */
    private $id;

    /** @Column(type="integer") * */
    private $field_id = 0;

    /** @Column(type="string") * */
    private $description = '';

    /** @Column(type="integer") * */
    private $normal_id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFieldType(): int
    {
        return $this->field_id;
    }

    public function setFieldType(int $fieldType): PlanetFieldTypeInterface
    {
        $this->field_id = $fieldType;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): PlanetFieldTypeInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getBaseFieldType(): int
    {
        return $this->normal_id;
    }

    public function setBaseFieldType(int $baseFieldType): PlanetFieldTypeInterface
    {
        $this->normal_id = $baseFieldType;

        return $this;
    }

}
