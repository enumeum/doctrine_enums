<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Fixture\AnotherEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="another_entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'another_entity')]
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
