<?php

declare(strict_types = 1);

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

namespace App\Core\DB;

use DateTime;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class UpdateListener
{
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();

        if (!method_exists($entity, 'setModified')) {
            return false;
        }

        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $md  = $em->getClassMetadata(\get_class($entity));

        $entity->setModified(new DateTime());
        $uow->recomputeSingleEntityChangeSet($md, $entity);

        return true;
    }
}
