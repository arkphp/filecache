<?php
/**
 * filecache
 * @copyright 2014 Liu Dong <ddliuhb@gmail.com>
 * @license MIT
 */

namespace ddliu\filecache;

class FileCache {
    protected $options = array();

    public function __construct($options = array()) {
        $this->options = array_merge(array(
            'root' => '.',
            'hash' => 'md5',
            'depth' => 3,
            'compress' => false,
            'ttl' => 0,
        ), $options);
    }

    /**
     * Set cache
     * @param string $key The cache key
     * @param mixed $data Data to store
     * @param int $ttl Time to live, use the construct option by default.
     * @param boolean $compress Compress or not, use the construct option by default.
     */
    public function set($key, $data, $ttl = null, $compress = null)
    {
        $path = $this->getPath($key);
        if ($compress === null) {
            $compress = $this->options['compress'];
        }

        if ($ttl === null) {
            $ttl = $this->options['ttl'];
        }

        $meta = array(
        );

        if ($compress) {
            $meta['compress'] = 1;
        }

        if ($ttl) {
            $meta['expires'] = time() + $ttl;
        }

        $meta['time'] = time();

        $content = '';
        foreach ($meta as $key => $value) {
            $content .= $key . ':' . $value . "\n";
        }

        $content .= "\n";

        $content .=  $this->encode($data, $compress);

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

        return $this->decode($data, !empty($meta['compress']));
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

    protected function encode($data, $compress) {
        $data = json_encode($data);

        if ($compress) {
            $data = gzcompress($data);
        }

        return $data;
    }

    protected function decode($data, $isCompress) {
        if ($isCompress) {
            $data = gzuncompress($data);
        }

        return json_decode($data, true);
    }
}