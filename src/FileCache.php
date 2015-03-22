<?php
/**
 * filecache
 * @copyright 2015 Liu Dong <ddliuhb@gmail.com>
 * @license MIT
 */

namespace Ark\Filecache;

class FileCache {
    protected $options = array();

    public function __construct($options = array()) {
        $this->options = array_merge(array(
            'root' => '.',
            'hash' => 'md5',
            'serialize' => 'php',
            'depth' => 3,
            'compress' => false,
            'ttl' => 0,
        ), $options);
    }

    /**
     * Set cache
     * @param string $key The cache key
     * @param mixed $data Data to store
     * @param array $options Override default options
     */
    public function set($key, $data, $options = array())
    {
        $path = $this->getPath($key);
        $options = array_merge(array(
            'serialize' => $this->options['serialize'],
            'ttl' => $this->options['ttl'],
            'compress' => $this->options['compress']?1:0
        ), $options);

        if (isset($options['ttl']) && $options['ttl']) {
            $options['expires'] = time() + $options['ttl'];
        }

        if (isset($options['ttl'])) {
            unset($options['ttl']);
        }

        $options['time'] = time();

        $content = '';
        foreach ($options as $key => $value) {
            $content .= $key . ':' . $value . "\n";
        }

        $content .= "\n";

        // serialize
        $data = $this->serialize($data, $options['serialize']);

        // compress
        if ($options['compress']) {
            $data = gzcompress($data);
        }

        $content .=  $data;

        return Util::writeFile($path, $content);
    }

    /**
     * Get cache with key
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$meta = $this->getMeta($key)) {
            return false;
        }

        // cache expired
        if (!empty($meta['expires']) && $meta['expires'] < time()) {
            return false;
        }

        if (!$data = file_get_contents($this->getPath($key), false, null, $meta['_META_SIZE'])) {
            return false;
        }

        // uncompress
        if ($meta['compress']) {
            $data = gzuncompress($data);
        }

        // unserialize
        $data = $this->unserialize($data, $meta['serialize']);

        return $data;
    }

    /**
     * Get metadata of the cache
     * @param  string $key
     * @return array|false
     */
    public function getMeta($key)
    {
        $path = $this->getPath($key);
        if (!$fp = @fopen($path, 'r')) {
            return false;
        }


        $data = fread($fp, 100);
        fclose($fp);

        $endPos = strpos($data, "\n\n");
        if (!$endPos) {
            return false;
        }

        $data = substr($data, 0, $endPos);
        $data = explode("\n", $data);

        $meta = array();

        foreach ($data as $row) {
            $row = explode(':', $row, 2);
            $meta[$row[0]] = $row[1];
        }

        $meta['_META_SIZE'] = $endPos + 2;

        return $meta;
    }

    /**
     * Delete cache by key
     * @param  string $key
     * @return boolean
     */
    public function delete($key)
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }

    /**
     * Clear all caches by removing the cache root
     * @return boolean
     */
    public function clear() {
        return Util::removeDir($this->options['root']);
    }

    /**
     * Get cache path of the key
     * @param  string $key
     * @return string
     */
    public function getPath($key) {
        return $this->options['root'] . '/' . $this->getRelativePathWithCache($key);
    }

    protected function getRelativePathWithCache($key) {
        static $cache = array();
        if (!isset($cache[$key])) {
            $cache[$key] = $this->genPath($this->getKeyHash($key));
        }

        if (count($cache) > 100) {
            $cache = array(
                $key => $cache[$key],
            );
        }

        return $cache[$key];
    }

    protected function getKeyHash($key) {
        if ($this->options['hash']) {
            return $this->options['hash']($key);
        } else {
            return $key;
        }
    }

    protected function genPath($key) {
        $depth = $this->options['depth'];
        if ($depth <= 1) {
            return $key;
        }

        $len = strlen($key);
        $parts = array();
        for ($i = 0; $i < $len; $i++) {
            if ($i >= $depth) {
                $parts[] = substr($key, $i);
                break;
            }

            $parts[] = $key[$i];
        }

        return implode('/', $parts);
    }

    protected function serialize($data, $type) {
        if ($type === 'raw') {
            return (string) $data;
        }

        if ($type === 'json') {
            return json_encode($data);
        }

        if ($type === 'php') {
            return serialize($data);
        }

        throw new \Exception('Unknown serialization type: '.$type);
    }

    protected function unserialize($data, $type) {
        if ($type === 'raw') {
            return $data;
        }

        if ($type === 'json') {
            return json_decode($data, true);
        }

        if ($type === 'php') {
            return unserialize($data);
        }

        throw new \Exception('Unknown serialization type: '.$type);
    }
}