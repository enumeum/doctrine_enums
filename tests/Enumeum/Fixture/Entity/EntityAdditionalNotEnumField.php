<?php

declare(strict_types=1);

namespace EnumeumTests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Enumeum\DoctrineEnum\Type\EnumeumType;
use EnumeumTests\Fixture\BaseStatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: "entity")]
class EntityAdditionalNotEnumField
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="enumeum_enum", enumType=BaseStatusType::class, options={"comment":"SOME Comment"})
     */
    #[ORM\Column(type: EnumeumType::NAME, enumType: BaseStatusType::class, options: ["comment" => "SOME Comment"])]
    private BaseStatusType $status;

    /**
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $field;

    public function __construct(
        int $id,
        BaseStatusType $status,
        int $field,
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->field = $field;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): BaseStatusType
    {
        return $this->status;
    }

    public function getField(): int
    {
        return $this->field;
    }
}
