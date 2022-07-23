<?php

declare(strict_types=1);

namespace EnumeumTests\Fixture\AnotherEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="another_entity")
 */
#[ORM\Entity]
#[ORM\Table(name: "another_entity")]
class AnotherEntityWithoutTypedField
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    public function __construct(
        int $id,
    ) {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
