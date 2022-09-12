<?php

declare(strict_types=1);

namespace EnumeumTests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Enumeum\DoctrineEnum\Type\EnumeumType;
use EnumeumTests\Fixture\AddedValuesStatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: "entity")]
class EntityChangedCommentAndEnumAddedValues
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="enumeum_enum", enumType=AddedValuesStatusType::class, options={"comment":"CHANGED Comment"})
     */
    #[ORM\Column(type: EnumeumType::NAME, enumType: AddedValuesStatusType::class, options: ["comment" => "CHANGED Comment"])]
    private AddedValuesStatusType $status;

    public function __construct(
        int $id,
        AddedValuesStatusType $status,
    ) {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): AddedValuesStatusType
    {
        return $this->status;
    }
}