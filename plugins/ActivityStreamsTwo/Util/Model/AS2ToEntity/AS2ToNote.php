<?php

namespace Plugin\ActivityStreamsTwo\Util\Model\AS2ToEntity;

use App\Core\Security;
use App\Entity\Note;
use DateTime;

abstract class AS2ToNote
{
    /**
     * @param array $args
     *
     * @throws \Exception
     *
     * @return Note
     */
    public static function translate(array $args): Note
    {
        $map = [
            'isLocal'  => false,
            'created'  => new DateTime($args['published'] ?? 'now'),
            'rendered' => $args['content'] ?? null,
            'modified' => new DateTime(),
        ];
        if (!is_null($map['rendered'])) {
            $map['content'] = Security::sanitize($map['rendered']);
        }

        $obj = new Note();
        foreach ($map as $prop => $val) {
            $set = "set{$prop}";
            $obj->{$set}($val);
        }
        return $obj;
    }
}