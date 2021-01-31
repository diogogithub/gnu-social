<?php

if (!class_exists('RedisException')) {
    class RedisException extends Redis
    {
    }
}
