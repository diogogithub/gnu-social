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
use ActivityUtils;
use ActivityVerb;
use Conversation;
use DOMDocument;
use Exception;
use Notice;
use PHPUnit\Framework\TestCase;
use User;
use User_group;

require_once INSTALLDIR . '/lib/util/common.php';

final class ActivityGenerationTests extends TestCase
{
    public static $author1 = null;
    public static $author2 = null;

    public static $targetUser1 = null;
    public static $targetUser2 = null;

    public static $targetGroup1 = null;
    public static $targetGroup2 = null;

    public static function setUpBeforeClass(): void
    {
        $authorNick1 = 'activitygenerationtestsuser' . common_random_hexstr(4);
        $authorNick2 = 'activitygenerationtestsuser' . common_random_hexstr(4);

        $targetNick1 = 'activitygenerationteststarget' . common_random_hexstr(4);
        $targetNick2 = 'activitygenerationteststarget' . common_random_hexstr(4);

        $groupNick1 = 'activitygenerationtestsgroup' . common_random_hexstr(4);
        $groupNick2 = 'activitygenerationtestsgroup' . common_random_hexstr(4);

        try {
            self::$author1 = User::register(['nickname' => $authorNick1,
                'email' => $authorNick1 . '@example.net',
                'email_confirmed' => true,]);

            self::$author2 = User::register(['nickname' => $authorNick2,
                'email' => $authorNick2 . '@example.net',
                'email_confirmed' => true,]);

            self::$targetUser1 = User::register(['nickname' => $targetNick1,
                'email' => $targetNick1 . '@example.net',
                'email_confirmed' => true,]);

            self::$targetUser2 = User::register(['nickname' => $targetNick2,
                'email' => $targetNick2 . '@example.net',
                'email_confirmed' => true,]);

            self::$targetGroup1 = User_group::register(['nickname' => $groupNick1,
                'userid' => self::$author1->id,
                'aliases' => [],
                'local' => true,
                'location' => null,
                'description' => null,
                'fullname' => null,
                'homepage' => null,
                'mainpage' => null,]);
            self::$targetGroup2 = User_group::register(['nickname' => $groupNick2,
                'userid' => self::$author1->id,
                'aliases' => [],
                'local' => true,
                'location' => null,
                'description' => null,
                'fullname' => null,
                'homepage' => null,
                'mainpage' => null,]);
        } catch (Exception $e) {
            static::tearDownAfterClass();
            throw $e;
        }
    }

    public function testBasicNoticeActivity()
    {
        $notice = $this->_fakeNotice();

        $entry = $notice->asAtomEntry(true);

        $element = $this->_entryToElement($entry, false);

        static::assertSame($notice->getUri(), ActivityUtils::childContent($element, 'id'));
        static::assertSame('New note by ' . self::$author1->nickname, ActivityUtils::childContent($element, 'title'));
        static::assertSame($notice->rendered, ActivityUtils::childContent($element, 'content'));
        static::assertSame(strtotime($notice->created), strtotime(ActivityUtils::childContent($element, 'published')));
        static::assertSame(strtotime($notice->created), strtotime(ActivityUtils::childContent($element, 'updated')));
        static::assertSame(ActivityVerb::POST, ActivityUtils::childContent($element, 'verb', Activity::SPEC));
        static::assertSame(ActivityObject::NOTE, ActivityUtils::childContent($element, 'object-type', Activity::SPEC));
    }

    public function testNamespaceFlag()
    {
        $notice = $this->_fakeNotice();

        $entry = $notice->asAtomEntry(true);

        $element = $this->_entryToElement($entry, false);

        static::assertTrue($element->hasAttribute('xmlns'));
        static::assertTrue($element->hasAttribute('xmlns:thr'));
        static::assertTrue($element->hasAttribute('xmlns:georss'));
        static::assertTrue($element->hasAttribute('xmlns:activity'));
        static::assertTrue($element->hasAttribute('xmlns:media'));
        static::assertTrue($element->hasAttribute('xmlns:poco'));
        static::assertTrue($element->hasAttribute('xmlns:ostatus'));
        static::assertTrue($element->hasAttribute('xmlns:statusnet'));

        $entry = $notice->asAtomEntry(false);

        $element = $this->_entryToElement($entry, true);

        static::assertFalse($element->hasAttribute('xmlns'));
        static::assertFalse($element->hasAttribute('xmlns:thr'));
        static::assertFalse($element->hasAttribute('xmlns:georss'));
        static::assertFalse($element->hasAttribute('xmlns:activity'));
        static::assertFalse($element->hasAttribute('xmlns:media'));
        static::assertFalse($element->hasAttribute('xmlns:poco'));
        static::assertFalse($element->hasAttribute('xmlns:ostatus'));
        static::assertFalse($element->hasAttribute('xmlns:statusnet'));
    }

    public function testSourceFlag()
    {
        $notice = $this->_fakeNotice();

        // Test with no source

        $entry = $notice->asAtomEntry(false, false);

        $element = $this->_entryToElement($entry, true);

        $source = ActivityUtils::child($element, 'source');

        static::assertNull($source);

        // Test with source

        $entry = $notice->asAtomEntry(false, true);

        $element = $this->_entryToElement($entry, true);

        $source = ActivityUtils::child($element, 'source');

        static::assertNotNull($source);
    }

    public function testSourceContent()
    {
        $notice = $this->_fakeNotice();
        // make a time difference!
        sleep(2);
        $notice2 = $this->_fakeNotice();

        $entry = $notice->asAtomEntry(false, true);

        $element = $this->_entryToElement($entry, true);

        $source = ActivityUtils::child($element, 'source');

        $atomUrl = common_local_url('ApiTimelineUser', ['id' => self::$author1->id, 'format' => 'atom']);

        $profile = self::$author1->getProfile();

        static::assertSame($atomUrl, ActivityUtils::childContent($source, 'id'));
        static::assertSame($atomUrl, ActivityUtils::getLink($source, 'self', 'application/atom+xml'));
        static::assertSame($profile->profileurl, ActivityUtils::getPermalink($source));
        static::assertSame(strtotime($notice2->created), strtotime(ActivityUtils::childContent($source, 'updated')));
        // XXX: do we care here?
        static::assertFalse(is_null(ActivityUtils::childContent($source, 'title')));
        static::assertSame(common_config('license', 'url'), ActivityUtils::getLink($source, 'license'));
    }

    public function testAuthorFlag()
    {
        $notice = $this->_fakeNotice();

        // Test with no author

        $entry = $notice->asAtomEntry(false, false, false);

        $element = $this->_entryToElement($entry, true);

        static::assertNull(ActivityUtils::child($element, 'author'));
        static::assertNull(ActivityUtils::child($element, 'actor', Activity::SPEC));

        // Test with source

        $entry = $notice->asAtomEntry(false, false, true);

        $element = $this->_entryToElement($entry, true);

        $author = ActivityUtils::child($element, 'author');
        $actor = ActivityUtils::child($element, 'actor', Activity::SPEC);

        static::assertFalse(is_null($author));
        static::assertTrue(is_null($actor)); // <activity:actor> is obsolete, no longer added
    }

    public function testAuthorContent()
    {
        $notice = $this->_fakeNotice();

        // Test with author

        $entry = $notice->asAtomEntry(false, false, true);

        $element = $this->_entryToElement($entry, true);

        $author = ActivityUtils::child($element, 'author');

        static::assertSame(self::$author1->getNickname(), ActivityUtils::childContent($author, 'name'));
        static::assertSame(self::$author1->getUri(), ActivityUtils::childContent($author, 'uri'));
    }

    /**
     * We no longer create <activity:actor> entries, they have merged to <atom:author>
     */
    public function testActorContent()
    {
        $notice = $this->_fakeNotice();

        // Test with author

        $entry = $notice->asAtomEntry(false, false, true);

        $element = $this->_entryToElement($entry, true);

        $actor = ActivityUtils::child($element, 'actor', Activity::SPEC);

        static::assertSame($actor, null);
    }

    public function testReplyLink()
    {
        $orig = $this->_fakeNotice(self::$targetUser1);

        $text = '@' . self::$targetUser1->nickname . ' reply text ' . common_random_hexstr(4);

        $reply = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null, 'reply_to' => $orig->id]);

        $entry = $reply->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $irt = ActivityUtils::child($element, 'in-reply-to', 'http://purl.org/syndication/thread/1.0');

        static::assertNotNull($irt);
        static::assertSame($orig->getUri(), $irt->getAttribute('ref'));
        static::assertSame($orig->getUrl(), $irt->getAttribute('href'));
    }

    public function testReplyAttention()
    {
        $orig = $this->_fakeNotice(self::$targetUser1);

        $text = '@' . self::$targetUser1->nickname . ' reply text ' . common_random_hexstr(4);

        $reply = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null, 'reply_to' => $orig->id]);

        $entry = $reply->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        static::assertSame(self::$targetUser1->getUri(), ActivityUtils::getLink($element, 'mentioned'));
    }

    public function testMultipleReplyAttention()
    {
        $orig = $this->_fakeNotice(self::$targetUser1);

        $text = '@' . self::$targetUser1->nickname . ' reply text ' . common_random_hexstr(4);

        $reply = Notice::saveNew(self::$targetUser2->id, $text, 'test', ['uri' => null, 'reply_to' => $orig->id]);

        $text = '@' . self::$targetUser1->nickname . ' @' . self::$targetUser2->nickname . ' reply text ' . common_random_hexstr(4);

        $reply2 = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null, 'reply_to' => $reply->id]);

        $entry = $reply2->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $links = ActivityUtils::getLinks($element, 'mentioned');

        $hrefs = [];

        foreach ($links as $link) {
            $hrefs[] = $link->getAttribute('href');
        }

        static::assertTrue(in_array(self::$targetUser1->getUri(), $hrefs));
        static::assertTrue(in_array(self::$targetUser2->getUri(), $hrefs));
    }

    public function testGroupPostAttention()
    {
        $text = '!' . self::$targetGroup1->nickname . ' reply text ' . common_random_hexstr(4);

        $notice = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null]);

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        static::assertSame(self::$targetGroup1->getUri(), ActivityUtils::getLink($element, 'mentioned'));
    }

    public function testMultipleGroupPostAttention()
    {
        $text = '!' . self::$targetGroup1->nickname . ' !' . self::$targetGroup2->nickname . ' reply text ' . common_random_hexstr(4);

        $notice = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null]);

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $links = ActivityUtils::getLinks($element, 'mentioned');

        $hrefs = [];

        foreach ($links as $link) {
            $hrefs[] = $link->getAttribute('href');
        }

        static::assertTrue(in_array(self::$targetGroup1->getUri(), $hrefs));
        static::assertTrue(in_array(self::$targetGroup2->getUri(), $hrefs));
    }

    public function testRepeatLink()
    {
        $notice = $this->_fakeNotice(self::$author1);
        $repeat = $notice->repeat(self::$author2->getProfile(), 'test');

        $entry = $repeat->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $noticeInfo = ActivityUtils::child($element, 'notice_info', 'http://status.net/schema/api/1/');

        static::assertNotNull($noticeInfo);
        static::assertSame($notice->id, $noticeInfo->getAttribute('repeat_of'));
        static::assertSame($repeat->id, $noticeInfo->getAttribute('local_id'));
    }

    public function testTag()
    {
        $tag1 = common_random_hexstr(4);

        $notice = $this->_fakeNotice(self::$author1, '#' . $tag1);

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $category = ActivityUtils::child($element, 'category');

        static::assertNotNull($category);
        static::assertSame($tag1, $category->getAttribute('term'));
    }

    public function testMultiTag()
    {
        $tag1 = common_random_hexstr(4);
        $tag2 = common_random_hexstr(4);

        $notice = $this->_fakeNotice(self::$author1, '#' . $tag1 . ' #' . $tag2);

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $categories = $element->getElementsByTagName('category');

        static::assertNotNull($categories);
        static::assertSame(2, $categories->length);

        $terms = [];

        for ($i = 0; $i < $categories->length; ++$i) {
            $cat = $categories->item($i);
            $terms[] = $cat->getAttribute('term');
        }

        static::assertTrue(in_array($tag1, $terms));
        static::assertTrue(in_array($tag2, $terms));
    }

    public function testGeotaggedActivity()
    {
        $notice = Notice::saveNew(self::$author1->id, common_random_hexstr(4), 'test', ['uri' => null, 'lat' => 45.5, 'lon' => -73.6]);

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        static::assertSame('45.5000000 -73.6000000', ActivityUtils::childContent($element, 'point', 'http://www.georss.org/georss'));
    }

    public function testNoticeInfo()
    {
        $notice = $this->_fakeNotice();

        $entry = $notice->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $noticeInfo = ActivityUtils::child($element, 'notice_info', 'http://status.net/schema/api/1/');

        static::assertSame($notice->id, $noticeInfo->getAttribute('local_id'));
        static::assertSame($notice->source, $noticeInfo->getAttribute('source'));
        static::assertSame('', $noticeInfo->getAttribute('repeat_of'));
        static::assertSame('', $noticeInfo->getAttribute('repeated'));
//        $this->assertEquals('', $noticeInfo->getAttribute('favorite'));
        static::assertSame('', $noticeInfo->getAttribute('source_link'));
    }

    public function testNoticeInfoRepeatOf()
    {
        $notice = $this->_fakeNotice();

        $repeat = $notice->repeat(self::$author2->getProfile(), 'test');

        $entry = $repeat->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        $noticeInfo = ActivityUtils::child($element, 'notice_info', 'http://status.net/schema/api/1/');

        static::assertSame($notice->id, $noticeInfo->getAttribute('repeat_of'));
    }

    public function testNoticeInfoRepeated()
    {
        $notice = $this->_fakeNotice();

        $repeat = $notice->repeat(self::$author2->getProfile(), 'test');

        $entry = $notice->asAtomEntry(false, false, false, self::$author2->getProfile());

        $element = $this->_entryToElement($entry, true);

        $noticeInfo = ActivityUtils::child($element, 'notice_info', 'http://status.net/schema/api/1/');

        static::assertSame('true', $noticeInfo->getAttribute('repeated'));

        $entry = $notice->asAtomEntry(false, false, false, self::$targetUser1->getProfile());

        $element = $this->_entryToElement($entry, true);

        $noticeInfo = ActivityUtils::child($element, 'notice_info', 'http://status.net/schema/api/1/');

        static::assertSame('false', $noticeInfo->getAttribute('repeated'));
    }

    /*    public function testNoticeInfoFave()
        {
            $notice = $this->_fakeNotice();

            $fave = Fave::addNew(self::$author2->getProfile(), $notice);

            // Should be set if user has faved

            $entry = $notice->asAtomEntry(false, false, false, self::$author2);

            $element = $this->_entryToElement($entry, true);

            $noticeInfo = ActivityUtils::child($element, 'notice_info', "http://status.net/schema/api/1/");

            $this->assertEquals('true', $noticeInfo->getAttribute('favorite'));

            // Shouldn't be set if user has not faved

            $entry = $notice->asAtomEntry(false, false, false, self::$targetUser1);

            $element = $this->_entryToElement($entry, true);

            $noticeInfo = ActivityUtils::child($element, 'notice_info', "http://status.net/schema/api/1/");

            $this->assertEquals('false', $noticeInfo->getAttribute('favorite'));
        }*/

    public function testConversationLink()
    {
        $orig = $this->_fakeNotice(self::$targetUser1);

        $text = '@' . self::$targetUser1->nickname . ' reply text ' . common_random_hexstr(4);

        $reply = Notice::saveNew(self::$author1->id, $text, 'test', ['uri' => null, 'reply_to' => $orig->id]);

        $conv = Conversation::getKV('id', $reply->conversation);

        $entry = $reply->asAtomEntry();

        $element = $this->_entryToElement($entry, true);

        static::assertSame($conv->getUrl(), ActivityUtils::getLink($element, 'ostatus:conversation'));
    }

    public static function tearDownAfterClass(): void
    {
        if (!is_null(self::$author1)) {
            self::$author1->getProfile()->delete();
        }

        if (!is_null(self::$author2)) {
            self::$author2->getProfile()->delete();
        }

        if (!is_null(self::$targetUser1)) {
            self::$targetUser1->getProfile()->delete();
        }

        if (!is_null(self::$targetUser2)) {
            self::$targetUser2->getProfile()->delete();
        }

        if (!is_null(self::$targetGroup1)) {
            self::$targetGroup1->delete();
        }

        if (!is_null(self::$targetGroup2)) {
            self::$targetGroup2->delete();
        }
    }

    private function _fakeNotice($user = null, $text = null)
    {
        if (empty($user)) {
            $user = self::$author1;
        }

        if (empty($text)) {
            $text = 'fake-o text-o ' . common_random_hexstr(32);
        }

        return Notice::saveNew($user->id, $text, 'test', ['uri' => null]);
    }

    private function _entryToElement($entry, $namespace = false)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n\n";
        $xml .= '<feed';
        if ($namespace) {
            $xml .= ' xmlns="http://www.w3.org/2005/Atom"';
            $xml .= ' xmlns:thr="http://purl.org/syndication/thread/1.0"';
            $xml .= ' xmlns:georss="http://www.georss.org/georss"';
            $xml .= ' xmlns:activity="http://activitystrea.ms/spec/1.0/"';
            $xml .= ' xmlns:media="http://purl.org/syndication/atommedia"';
            $xml .= ' xmlns:poco="http://portablecontacts.net/spec/1.0"';
            $xml .= ' xmlns:ostatus="http://ostatus.org/schema/1.0"';
            $xml .= ' xmlns:statusnet="http://status.net/schema/api/1/"';
        }
        $xml .= '>' . "\n" . $entry . "\n" . '</feed>' . "\n";
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $feed = $doc->documentElement;
        $entries = $feed->getElementsByTagName('entry');

        return $entries->item(0);
    }
}
