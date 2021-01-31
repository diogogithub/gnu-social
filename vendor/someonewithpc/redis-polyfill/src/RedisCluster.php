<?php

if (!class_exists('RedisCluster')) {
    class RedisCluster extends Redis
    {
    }
}
