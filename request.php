<?php

class SimpleJsonRequest
{
    private static function makeRequest(string $method, string $url, array $parameters = null, array $data = null)
    {
        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/json',
                'content' => $data ? json_encode($data) : null
            ]
        ];

        $url .= ($parameters ? '?' . http_build_query($parameters) : '');

        return file_get_contents($url, false, stream_context_create($opts));
    }

    private static function redisInit()
    {
        try {
            //Connecting to Redis server on localhost 
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379); 
            
            if ($redis->ping()) {
                echo "Cache server running";
            }

            return $redis;
        } catch (RedisException $e) {
            return false;
        }
    }

    private static function getCacheData(string $url, array $parameters = null)
    {
        $redis = self::redisInit();

        if ($redis) {
            $url .= ($parameters ? '?' . http_build_query($parameters) : '');

            $cacheKey = md5($url);

            if ($redis->exists($cacheKey)) {

                return  $redis->get($cacheKey);
            } else {
                $data = self::makeRequest('GET', $url, $parameters);

                $redis->set($cacheKey, $data);

                return $data;
            }
        } else {
            return self::makeRequest('GET', $url, $parameters);
        }
    }

    private static function deleteCacheData(string $url, array $parameters = null)
    {
        $redis = self::redisInit();

        if ($redis) {
            $url .= ($parameters ? '?' . http_build_query($parameters) : '');

            $cacheKey = md5($url);

            $redis->del($cacheKey);
        }
    }

    public static function get(string $url, array $parameters = null)
    {
        // Get data from the cache server
        $response = self::getCacheData($url, $parameters);

        return json_decode($response);
    }

    public static function post(string $url, array $parameters = null, array $data)
    {
        $response = self::makeRequest('POST', $url, $parameters, $data);

        if ($response) {
            self::deleteCacheData($url, $parameters);
        }

        return json_decode($response);
    }

    public static function put(string $url, array $parameters = null, array $data)
    {
        $response = self::makeRequest('PUT', $url, $parameters, $data);

        if ($response) {
            self::deleteCacheData($url, $parameters);
        }

        return json_decode($response);
    }

    public static function patch(string $url, array $parameters = null, array $data)
    {
        $response = self::makeRequest('PATCH', $url, $parameters, $data);

        if ($response) {
            self::deleteCacheData($url, $parameters);
        }

        return json_decode($response);
    }

    public static function delete(string $url, array $parameters = null, array $data = null)
    {
        $response = self::makeRequest('DELETE', $url, $parameters, $data);

        if ($response) {
            self::deleteCacheData($url, $parameters);
        }

        return json_decode($response);
    }
}

// $request = new SimpleJsonRequest();
// $res     = $request->get('https://jsonplaceholder.typicode.com/todos/2');
// $res = $request->delete('https://jsonplaceholder.typicode.com/todos/2');