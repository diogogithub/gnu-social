<?php

declare(strict_types = 1);
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Class for an exception when a WebFinger acct: URI can not be constructed
 * using the data we have in a Profile.
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Exception
 * @package   StatusNet
 *
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 *
 * @see      http://status.net/
 */

namespace Component\FreeNetwork\Exception;

use function App\Core\I18n\_m;
use App\Util\Exception\ServerException;
use Throwable;

/**
 * Class for an exception when a WebFinger acct: URI can not be constructed
 * using the data we have in a Profile.
 *
 * @category Exception
 * @package  StatusNet
 *
 * @author   Mikael Nordfeldth <mmn@hethane.se>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 *
 * @see     http://status.net/
 */
class WebfingerReconstructionException extends ServerException
{
    public function __construct(string $message = '', int $code = 500, ?Throwable $previous = null)
    {
        // We could log an entry here with the search parameters
        parent::__construct(_m('WebFinger URI generation failed.'));
    }
}
