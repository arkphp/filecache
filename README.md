# filecache [![Build Status](https://travis-ci.org/ddliu/php-filecache.svg)](https://travis-ci.org/ddliu/php-filecache)

Cache with file system.

## Why File Cache?

In cases you don't want to have other dependencies or don't want to waste your RAM.

## Features

- Compression with `gzcompress`
- Expiration
- Multi level cache directories

## Installation

```
composer require ddliu/filecache
```

## Usage

```php
<?php
use ddliu\filecache\FileCache;

$cache = new FileCache([
    'root' => '/path/to/cache/root', // Cache root
    'ttl' => 0,                    // Time to live
    'compress' => false,             // Compress data with gzcompress or not
    'serialize' => 'json',          // How to serialize data: json, php, raw
]);

$cache->set('key1', 'value1');
$cache->get('key1');

// Set TTL and compression
$cache->set('key2', array('hello', 'world'), array(
    'ttl' => 10,
    'compress' => true
)); 

sleep(11);

$cache->get('key2');

$cache->delete('key1');

$cache->clear(); // clear all caches by removing the root path of the cache
```