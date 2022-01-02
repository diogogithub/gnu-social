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

/**
 * Class for 'assertion' exceptions. Logs when an unexpected state is found and is treated as a ServerException downstream
 * HTTP code 500
 *
 * @category  Exception
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Exception;

use App\Core\Log;
use Throwable;

class BugFoundException extends ServerException
{
    public function __construct(string $log_message, array $log_context = [], string $message = '', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $frame = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, limit: 2)[1];
        $file  = mb_substr($frame['file'], mb_strlen(INSTALLDIR) + 1);
        Log::critical("{$log_message} in {$file}:{$frame['line']}", $log_context);
    }
}
