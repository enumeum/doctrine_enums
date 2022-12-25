<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Fixture\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EnumeumTests\Fixture\BaseStatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'entity')]
class Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="string", enumType=BaseStatusType::class, options={"comment":"SOME Comment"})
     */
    #[ORM\Column(type: Types::STRING, enumType: BaseStatusType::class, options: ['comment' => 'SOME Comment'])]
    private BaseStatusType $status;

    public function __construct(
        int $id,
        BaseStatusType $status,
    ) {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): BaseStatusType
    {
        return $this->status;
    }
}
