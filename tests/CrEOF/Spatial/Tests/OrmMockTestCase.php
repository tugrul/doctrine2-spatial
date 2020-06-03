<?php

namespace CrEOF\Spatial\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqliteDriver;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;

/**
 * Common test code
 */
abstract class OrmMockTestCase extends TestCase
{
    protected $mockEntityManager;

    protected function setUp()
    {
        $this->mockEntityManager = $this->getMockEntityManager();
    }

    protected function getMockConnection()
    {
        $driver = $this->getMockBuilder(PDOSqliteDriver::class)
                        ->setMethods(array('getDatabasePlatform'))
                        ->getMock();
        $platform = $this->getMockBuilder(SqlitePlatform::class)
                        ->setMethods(array('getName'))
                        ->getMock();

        $platform->method('getName')
            ->willReturn('YourSQL');
        $driver->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection = new Connection(array(), $driver);

        return $connection;
    }

    /**
     * @return EntityManager
     */
    protected function getMockEntityManager()
    {
        if (isset($this->mockEntityManager)) {
            return $this->mockEntityManager;
        }

        $config = new Configuration();

        $config->setMetadataCacheImpl(new ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('CrEOF\Spatial\Tests\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(realpath(__DIR__ . '/Fixtures')), true));

        return EntityManager::create($this->getMockConnection(), $config);
    }
}
