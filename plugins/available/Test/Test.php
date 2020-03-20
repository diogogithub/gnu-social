<?php

namespace Plugin\Test;

class Test
{
    public function onTest(string $foo)
    {
        dump('Event handled: ' . $foo);
    }
}
