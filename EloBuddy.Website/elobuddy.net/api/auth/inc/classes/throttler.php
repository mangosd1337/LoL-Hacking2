<?php


class throttler {
    private $db, $cache, $conf;

    public function __construct($db, $cache, $conf){
        $this->db = $db;
        $this->cache = $cache;
        $this->conf = $conf;

        $this->cache->cache_time = $conf['ban_time'];
    }


    public function isThrottled($data_name){
        // A cache entry exists
        // IP has exceeded the maximum requests
        if($this->cache->get($data_name) !== null)
            return true;

        $data = $this->getData($data_name);

        // No entry in database
        // Create an entry and we can safely return false
        if(empty($data)){
            $this->addDBEntry($data_name);
            return false;
        }

        // IP has exceeded the limit
        // Add a cache entry and deny requests for that time
        if($this->hasExceededLimit($data_name, $data)){
            $this->cache->set($data_name, 'b'); // block
            $this->db->update('api_throttler', ['hits' => 0, 'start_time' => time()], ['data_name' => $data_name]);
            return true;
        }


        $this->db->update('api_throttler', ['hits[+]' => 1], ['data_name' => $data_name]);
        return false;
    }

    public function hasExceededLimit($data_name, $data){
        $hits = $this->conf['hits'];
        $retention_time = $this->conf['session_duration'];

        // Allowed duration hasn't been "expired"
        if(time() <= $data['start_time'] + $retention_time){
            return $data['hits'] >= $hits;
        }else{
            // Allowed duration has "expired"
            // Update the "session" start time and reset the hits
            $this->db->update('api_throttler', ['hits' => 1, 'start_time' => time()], ['data_name' => $data_name]);
        }
        return false;
        //return !(time() <= $data['start_time'] + $retention_time) && $data['hits'] <= $hits;
    }


    public function addDBEntry($data_name){
        // Hit 1 since we got a request
        $this->db->insert('api_throttler', ['hits' => 1, 'start_time' => time(), 'data_name' => $data_name]);
    }

    // Make private
    public function getData($data_name){
        $data = $this->db->get('api_throttler', ['hits', 'start_time'], ['data_name' => $data_name]);
        return ($data == false) ? [] : $data;

    }





}
