<?php


class cacheManager {
    public $cache_path = 'cache/';
    public $cache_extension = '.cache';
    public $cache_time = 120; // seconds


    public function get($cache){
        $file = $this->cache_path . hash('sha256', $cache) . $this->cache_extension;

        return ($this->isCached($file)) ? @file_get_contents($file) : null;
    }

    public function isCached($file){
        $cached = file_exists($file) && (filemtime($file) + $this->cache_time >= time());

        //  Remove cached file if no longer needs to be cached
        if($cached && !(filemtime($file) + $this->cache_time >= time()))
            unlink($file);

        return $cached;
    }

    public function set($cache, $data){
        $file = $this->cache_path . hash('sha256', $cache) . $this->cache_extension;

        file_put_contents($file, $data);
    }
}