<?php

if (!class_exists('Redis')) {
    class Redis
    {
        public function __construct()
        {
            throw new Exception("This polyfill doesn't actually implement anything, it's purpose is to allow for optional use in the symfony framework");
        }
    }
}
