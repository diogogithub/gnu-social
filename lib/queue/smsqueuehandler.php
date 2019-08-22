<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
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
 */

defined('GNUSOCIAL') || die();

/**
 * Queue handler for pushing new notices to local subscribers using SMS.
 */
class SmsQueueHandler extends QueueHandler
{
    function transport()
    {
        return 'sms';
    }

    function handle($notice) : bool
    {
        if (!($notice instanceof Notice)) {
            common_log(LOG_ERR, "Got a bogus notice, not broadcasting");
            return true;
        }

    	require_once(INSTALLDIR . '/lib/mail.php');
        return mail_broadcast_notice_sms($notice);
    }
}
