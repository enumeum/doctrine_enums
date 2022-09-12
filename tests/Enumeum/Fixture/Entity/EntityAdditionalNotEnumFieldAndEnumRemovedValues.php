<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine enumerations extension for Postgres" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
#[ORM\Table(name: 'entity')]
class EntityAdditionalNotEnumFieldAndEnumRemovedValues
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="enumeum_enum", enumType=RemovedValuesStatusType::class, options={"comment":"SOME Comment"})
     */
    #[ORM\Column(type: EnumeumType::NAME, enumType: RemovedValuesStatusType::class, options: ['comment' => 'SOME Comment'])]
    private RemovedValuesStatusType $status;

    /**
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $field;

    public function __construct(
        int $id,
        RemovedValuesStatusType $status,
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

    public function getStatus(): RemovedValuesStatusType
    {
        return $this->status;
    }

    public function getField(): int
    {
        return $this->field;
    }
}
