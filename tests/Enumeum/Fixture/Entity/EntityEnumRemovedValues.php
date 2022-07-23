<?php

declare(strict_types=1);

namespace EnumeumTests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Enumeum\DoctrineEnum\Type\EnumeumType;
use EnumeumTests\Fixture\RemovedValuesStatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: "entity")]
class EntityEnumRemovedValues
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type=enumeum_string, enumType=RemovedValuesStatusType::class)
     */
    #[ORM\Column(type: EnumeumType::NAME, enumType: RemovedValuesStatusType::class, options: ["comment" => "SOME Comment"])]
    private RemovedValuesStatusType $status;

    public function __construct(
        int $id,
        RemovedValuesStatusType $status,
    ) {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): RemovedValuesStatusType
    {
        return $this->status;
    }
}
