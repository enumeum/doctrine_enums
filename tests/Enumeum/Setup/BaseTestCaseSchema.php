<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EnumeumTests\Setup;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Enumeum\DoctrineEnum\EventSubscriber\PostGenerateSchemaSubscriber;

abstract class BaseTestCaseSchema extends BaseTestCase
{
    use ConnectionPostgres13Trait;

    /**
     * @throws ORMException
     */
    protected function getDefaultMockEntityManager(
        array $params,
        EventManager $evm = null,
        Configuration $config = null
    ): EntityManager {
        $config = null === $config ? $this->getDefaultConfiguration() : $config;
        $evm = $evm ?: new EventManager();
        $em = EntityManager::create($params, $config, $evm);
        $evm->addEventSubscriber(new PostGenerateSchemaSubscriber(
            $this->getDefinitionRegistry(),
            $this->getTableColumnRegistry($em->getConnection()),
        ));

        return $this->em = $em;
    }
}
