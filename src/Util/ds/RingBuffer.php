<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Util\ds;

use Ds\Deque;
use Functional as F;

class RingBuffer implements \Serializable, \ArrayAccess
{
    private int $capacity;
    private Deque $elements;

    public function __construct(int $c)
    {
        $this->capacity = $c;
        $this->elements = new Deque();
    }

    public function add($e)
    {
        if ($this->capacity !== 0 && $this->elements->count() >= $this->capacity) {
            $this->elements->shift();
        }
        $this->elements->unshift($e);
    }

    public function remove(int $index)
    {
        return $this->elements->remove($index);
    }

    public function get(int $index)
    {
        return $this->elements->get($index);
    }

    // ------ Serialization
    public function serialize()
    {
        return serialize($this->capacity) . serialize($this->elements);
    }

    public function unserialize($data)
    {
        list($this->capacity, $this->elements) = F\map(explode(';', $data), F\ary('unserialize', 1));
    }
    // ------ End Serialization

    // ------ Array interface
    public function offsetSet($index, $value)
    {
        if (is_null($index)) {
            $this->add($value);
        } else {
            $this->elements->set($index, $value);
        }
    }

    public function offsetExists($index)
    {
        return is_int($index) && $index >= 0 && $index < $this->elements->count();
    }

    public function offsetUnset($index)
    {
        $this->elements->remove($index);
    }

    public function offsetGet($index)
    {
        return $this->offsetExists($index) ? $this->get($index) : null;
    }
    // ------ End Array interface
}
