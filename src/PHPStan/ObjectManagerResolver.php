<?php

namespace App\PHPStan;

use Doctrine\Persistence\ObjectManager;

class ObjectManagerResolver
{
	private ?ObjectManager $objectManager = null;

	public function getManager(): ObjectManager
	{
		if ($this->objectManager !== null) {
			return $this->objectManager;
		} else {
            $this->objectManager = require __DIR__ . '/../../config/phpstan-bootstrap.php';
            return $this->objectManager;
        }
	}
}
