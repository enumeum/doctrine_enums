<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Fixture\AnotherEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Enumeum\DoctrineEnum\Type\EnumeumType;
use EnumeumTests\Fixture\AddedValuesStatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="another_entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'another_entity')]
class AnotherEntityEnumAddedValues
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="enumeum_enum", enumType=AddedValuesStatusType::class, options={"comment":"SOME Comment"})
     */
    #[ORM\Column(type: EnumeumType::NAME, enumType: AddedValuesStatusType::class, options: ['comment' => 'SOME Comment'])]
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
