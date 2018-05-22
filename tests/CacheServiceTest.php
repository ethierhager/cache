<?php

namespace Kbra\Cache\Tests;

use Kbra\Cache\CacheService;
use Kbra\Cache\CacheServiceException;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\CacheManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CacheServiceTest extends TestCase
{
    /** @var array */
    private $settings;

    /** @var int */
    private $ttl;

    /** @var CacheService */
    private $cacheService;

    /** @var ExtendedCacheItemPoolInterface */
    private $cachePool;

    /** @var ExtendedCacheItemInterface */
    private $cacheItem;


    public function setUp()
    {
        $this->settings = [
            'driver' => 'devnull',
            'maxRetries' => 2,
            'config' => [],
        ];

        /** @var ExtendedCacheItemPoolInterface */
        $this->cachePool = $this->createMock(ExtendedCacheItemPoolInterface::class);

        /** @var ExtendedCacheItemInterface */
        $this->cacheItem = $this->createMock(ExtendedCacheItemInterface::class);

        /** @var CacheService */
        $this->cacheService = $this->getMockBuilder(CacheService::class)
            ->setConstructorArgs([$this->settings])
            ->setMethods(['getCachePool'])
            ->getMock();

        $this->ttl = CacheManager::getDefaultConfig()['defaultTtl'];
    }

    public function testConnectionFailsAndRetries()
    {
        $this->cacheService
            ->expects($this->exactly($this->settings['maxRetries']))
            ->method('getCachePool')
            ->will($this->throwException(new \RedisException()));

        $this->expectException(CacheServiceException::class);

        $this->cacheService->connect();
    }

    public function testConnectionSucceeds()
    {
        $this->cacheService
            ->expects($this->once())
            ->method('getCachePool')
            ->will($this->returnValue($this->cachePool));

        $this->cacheService->connect();
    }

    public function testSet()
    {
        $name = 'this is a test';
        $options = ['this','is','a','test'];
        $value = 'this test is super awesome';

        $this->cacheService
            ->expects($this->any())
            ->method('getCachePool')
            ->will($this->returnValue($this->cachePool));

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($this->cacheItem));

        $this->cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($this->identicalTo($value))
            ->will($this->returnValue($this->cacheItem));

        $this->cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($this->identicalTo($this->ttl));

        $this->cachePool
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $result = $this->cacheService->set($name, $value, $options);

        $this->assertTrue($result);
    }

    public function testSetWithTagsAndTtl()
    {
        $name = 'this is a test';
        $options = ['this','is','a','test'];
        $value = 'this test is super awesome';
        $tags = ['TAG1','Tag2','tagThree'];
        $ttl = 11;

        $this->cacheService
            ->expects($this->any())
            ->method('getCachePool')
            ->will($this->returnValue($this->cachePool));

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($this->cacheItem));

        $this->cacheItem
            ->expects($this->once())
            ->method('addTags')
            ->with($this->identicalTo(['tag1','tag2','tagthree']));

        $this->cacheItem
            ->expects($this->once())
            ->method('set')
            ->with($this->identicalTo($value))
            ->will($this->returnValue($this->cacheItem));

        $this->cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($this->identicalTo($ttl));

        $this->cachePool
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $result = $this->cacheService->set($name, $value, $options, $tags, $ttl);

        $this->assertTrue($result);
    }

    public function testGet()
    {
        $name = 'this is a test';
        $options = ['this','is','a','test'];

        $this->cacheService
            ->expects($this->any())
            ->method('getCachePool')
            ->will($this->returnValue($this->cachePool));

        $this->cachePool
            ->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($this->cacheItem));

        $this->cacheItem
            ->expects($this->once())
            ->method('get');

        $this->cacheService->get($name, $options);
    }

    public function testClearTags()
    {
        $tags = ['taco','hotdog'];

        $this->cacheService
            ->expects($this->any())
            ->method('getCachePool')
            ->will($this->returnValue($this->cachePool));

        $this->cachePool
            ->expects($this->once())
            ->method('deleteItemsByTags')
            ->with($this->identicalTo($tags));

        $this->cacheService->clearTags($tags);
    }

}
