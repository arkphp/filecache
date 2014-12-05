<?php
use ddliu\filecache\FileCache;

class BaseTest extends PHPUnit_Framework_TestCase {
    protected $cache;
    public function setUp() {
        $this->cache = new FileCache([
            'root' => __DIR__ . '/cache',
        ]);
    }

    public function tearDown() {
        $this->cache->clear();
    }

    public function testSet() {
        $key = __FUNCTION__;
        $value = 'hello world';
        $this->cache->set($key, $value);
        $this->assertEquals($this->cache->get($key), $value);
    }

    public function testTTL() {
        $key = __FUNCTION__;
        $value = 'hello world';
        $this->cache->set($key, $value, 2);
        $this->assertEquals($this->cache->get($key), $value);
        sleep(3);
        $this->assertEquals($this->cache->get($key), false);
    }

    public function testCompress() {
        $key = __FUNCTION__;
        $value = 'hello world';
        $this->cache->set($key, $value, null, true);
        $meta = $this->cache->getMeta($key);
        $this->assertEquals($meta['compress'], '1');
        $this->assertEquals($this->cache->get($key), $value);
    }

    public function testDelete()
    {
        $key = __FUNCTION__;
        $value = 'hello world';
        $this->cache->set($key, $value);
        $this->cache->delete($key);
        $this->assertEquals($this->cache->get($key), false);
    }
}
