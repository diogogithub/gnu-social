<?php
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

namespace Tests\Unit;

if (!defined('INSTALLDIR')) {
    define('INSTALLDIR', dirname(dirname(__DIR__)));
}
if (!defined('PUBLICDIR')) {
    define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');
}
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use Activity;
use ActivityObject;
use DOMDocument;
use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/util/common.php';

final class UserFeedParseTest extends TestCase
{
    public function testFeed1()
    {
        global $_testfeed1;
        $dom = new DOMDocument();
        $dom->loadXML($_testfeed1);
        static::assertFalse(empty($dom));

        $entries = $dom->getElementsByTagName('entry');

        $entry1 = $entries->item(0);
        static::assertFalse(empty($entry1));

        $feedEl = $dom->getElementsByTagName('feed')->item(0);
        static::assertFalse(empty($feedEl));

        // Test actor (from activity:subject)

        $act1 = new Activity($entry1, $feedEl);
        static::assertFalse(empty($act1));
        static::assertFalse(empty($act1->actor));
        static::assertSame($act1->actor->type, ActivityObject::PERSON);
        static::assertSame($act1->actor->title, 'Zach Copley');
        static::assertSame($act1->actor->id, 'http://localhost/statusnet/user/1');
        static::assertSame($act1->actor->link, 'http://localhost/statusnet/zach');

        $avatars = $act1->actor->avatarLinks;

        static::assertSame(
            $avatars[0]->url,
            'http://localhost/statusnet/theme/default/default-avatar-profile.png'
        );

        static::assertSame(
            $avatars[1]->url,
            'http://localhost/statusnet/theme/default/default-avatar-stream.png'
        );

        static::assertSame(
            $avatars[2]->url,
            'http://localhost/statusnet/theme/default/default-avatar-mini.png'
        );

        static::assertSame($act1->actor->displayName, 'Zach Copley');

        $poco = $act1->actor->poco;
        static::assertSame($poco->preferredUsername, 'zach');
        static::assertSame($poco->address->formatted, 'El Cerrito, CA');
        static::assertSame($poco->urls[0]->type, 'homepage');
        static::assertSame($poco->urls[0]->value, 'http://zach.copley.name');
        static::assertSame($poco->urls[0]->primary, true);
        static::assertSame($poco->note, 'Zach Hack Attack');

        // test the post

        //var_export($act1);
        static::assertSame($act1->objects[0]->type, 'http://activitystrea.ms/schema/1.0/note');
        static::assertSame($act1->objects[0]->title, 'And now for something completely insane...');

        static::assertSame($act1->objects[0]->content, 'And now for something completely insane...');
        static::assertSame($act1->objects[0]->id, 'http://localhost/statusnet/notice/3');
    }
}

$_testfeed1 = <<<TESTFEED1
<?xml version="1.0" encoding="UTF-8"?>
<feed xml:lang="en-US" xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xmlns:georss="http://www.georss.org/georss" xmlns:activity="http://activitystrea.ms/spec/1.0/" xmlns:media="http://purl.org/syndication/atommedia" xmlns:poco="http://portablecontacts.net/spec/1.0" xmlns:ostatus="http://ostatus.org/schema/1.0">
 <id>http://localhost/statusnet/api/statuses/user_timeline/1.atom</id>
 <title>zach timeline</title>
 <subtitle>Updates from zach on Zach Dev!</subtitle>
 <logo>http://localhost/statusnet/theme/default/default-avatar-profile.png</logo>
 <updated>2010-03-04T01:41:14+00:00</updated>
<author>
 <name>zach</name>
 <uri>http://localhost/statusnet/user/1</uri>

</author>
 <link href="http://localhost/statusnet/zach" rel="alternate" type="text/html"/>
 <link href="http://localhost/statusnet/main/sup#1" rel="http://api.friendfeed.com/2008/03#sup" type="application/json"/>
 <link href="http://localhost/statusnet/main/push/hub" rel="hub"/>
 <link href="http://localhost/statusnet/main/salmon/user/1" rel="http://salmon-protocol.org/ns/salmon-replies"/>
 <link href="http://localhost/statusnet/main/salmon/user/1" rel="http://salmon-protocol.org/ns/salmon-mention"/>
 <link href="http://localhost/statusnet/api/statuses/user_timeline/1.atom" rel="self" type="application/atom+xml"/>
<activity:subject>
 <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
 <id>http://localhost/statusnet/user/1</id>
 <title>Zach Copley</title>
 <link rel="alternate" type="text/html" href="http://localhost/statusnet/zach"/>
 <link rel="avatar" type="image/png" media:width="96" media:height="96" href="http://localhost/statusnet/theme/default/default-avatar-profile.png"/>
 <link rel="avatar" type="image/png" media:width="48" media:height="48" href="http://localhost/statusnet/theme/default/default-avatar-stream.png"/>
 <link rel="avatar" type="image/png" media:width="24" media:height="24" href="http://localhost/statusnet/theme/default/default-avatar-mini.png"/>

<poco:preferredUsername>zach</poco:preferredUsername>
<poco:displayName>Zach Copley</poco:displayName>
<poco:note>Zach Hack Attack</poco:note>
<poco:address>
 <poco:formatted>El Cerrito, CA</poco:formatted>
</poco:address>
<poco:urls>
 <poco:type>homepage</poco:type>
 <poco:value>http://zach.copley.name</poco:value>
 <poco:primary>true</poco:primary>

</poco:urls>
</activity:subject>
<entry>
 <title>And now for something completely insane...</title>
 <link rel="alternate" type="text/html" href="http://localhost/statusnet/notice/3"/>
 <id>http://localhost/statusnet/notice/3</id>
 <published>2010-03-04T01:41:07+00:00</published>
 <updated>2010-03-04T01:41:07+00:00</updated>
 <link rel="ostatus:conversation" href="http://localhost/statusnet/conversation/3"/>
 <content type="html">And now for something completely insane...</content>
</entry>

</feed>
TESTFEED1;
