<?php

namespace App\Util;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;

class SchemaDefDriver extends StaticPHPDriver
{
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $schema = $className::schemaDef();
        $metadata->addField($schema[0]);
    }
}
