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

defined('GNUSOCIAL') || die();

/**
 * Table Definition for profile_tag
 */
class Profile_tag extends Managed_DataObject
{
    public $__table = 'profile_tag';                     // table name
    public $tagger;                          // int(4)  primary_key not_null
    public $tagged;                          // int(4)  primary_key not_null
    public $tag;                             // varchar(64)  primary_key not_null
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(

            'fields' => array(
                'tagger' => array('type' => 'int', 'not null' => true, 'description' => 'user making the tag'),
                'tagged' => array('type' => 'int', 'not null' => true, 'description' => 'profile tagged'),
                'tag' => array('type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'hash tag associated with this notice'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date the tag was added'),
            ),
            'primary key' => array('tagger', 'tagged', 'tag'),
            'foreign keys' => array(
                'profile_tag_tagger_fkey' => array('profile', array('tagger' => 'id')),
                'profile_tag_tagged_fkey' => array('profile', array('tagged' => 'id')),
            ),
            'indexes' => array(
                'profile_tag_modified_idx' => array('modified'),
                'profile_tag_tagger_tag_idx' => array('tagger', 'tag'),
                'profile_tag_tagged_idx' => array('tagged'),
            ),
        );
    }

    public function links()
    {
        return array('tagger,tag' => 'profile_list:tagger,tag');
    }

    public function getMeta()
    {
        return Profile_list::pkeyGet(array('tagger' => $this->tagger, 'tag' => $this->tag));
    }

    public static function getSelfTagsArray(Profile $target)
    {
        return self::getTagsArray($target->getID(), $target->getID(), $target);
    }

    public static function setSelfTags(Profile $target, array $newtags, array $privacy = [])
    {
        return self::setTags($target->getID(), $target->getID(), $newtags, $privacy);
    }

    public static function getTags($tagger, $tagged, $auth_user = null)
    {
        $profile_list = new Profile_list();
        $include_priv = 1;

        if (!($auth_user instanceof User ||
            $auth_user instanceof Profile) ||
            ($auth_user->id !== $tagger)) {
            $profile_list->private = false;
            $include_priv = 0;
        }

        $key = sprintf('profile_tag:tagger_tagged_privacy:%d-%d-%d', $tagger, $tagged, $include_priv);
        $tags = Profile_list::getCached($key);
        if ($tags !== false) {
            return $tags;
        }

        $qry = 'select profile_list.* from profile_list left join '.
               'profile_tag on (profile_list.tag = profile_tag.tag and '.
               'profile_list.tagger = profile_tag.tagger) where '.
               'profile_tag.tagger = %d and profile_tag.tagged = %d ';
        $qry = sprintf($qry, $tagger, $tagged);

        if (!$include_priv) {
            $qry .= ' AND profile_list.private IS NOT TRUE';
        }

        $profile_list->query($qry);

        Profile_list::setCache($key, $profile_list);

        return $profile_list;
    }

    public static function getTagsArray($tagger, $tagged, Profile $scoped = null)
    {
        $ptag = new Profile_tag();

        $qry = sprintf(
            'SELECT profile_tag.tag '.
            'FROM profile_tag INNER JOIN profile_list '.
            ' ON (profile_tag.tagger = profile_list.tagger ' .
            '     and profile_tag.tag = profile_list.tag) ' .
            'WHERE profile_tag.tagger = %d ' .
            'AND   profile_tag.tagged = %d ',
            $tagger,
            $tagged
        );

        if (!$scoped instanceof Profile || $scoped->getID() !== $tagger) {
            $qry .= 'AND profile_list.private IS NOT TRUE';
        }

        $tags = array();

        $ptag->query($qry);

        while ($ptag->fetch()) {
            $tags[] = $ptag->tag;
        }

        return $tags;
    }

    public static function setTags($tagger, $tagged, array $newtags, array $privacy = [])
    {
        $newtags = array_unique($newtags);
        $oldtags = self::getTagsArray($tagger, $tagged, Profile::getByID($tagger));

        $ptag = new Profile_tag();

        // Delete stuff that's in old and not in new

        $to_delete = array_diff($oldtags, $newtags);

        // Insert stuff that's in new and not in old

        $to_insert = array_diff($newtags, $oldtags);

        foreach ($to_delete as $deltag) {
            self::unTag($tagger, $tagged, $deltag);
        }

        foreach ($to_insert as $instag) {
            $private = isset($privacy[$instag]) ? $privacy[$instag] : false;
            self::setTag($tagger, $tagged, $instag, null, $private);
        }
        return true;
    }

    # set a single tag
    public static function setTag($tagger, $tagged, $tag, $desc=null, $private = false)
    {
        $ptag = Profile_tag::pkeyGet(array('tagger' => $tagger,
                                           'tagged' => $tagged,
                                           'tag' => $tag));

        # if tag already exists, return it
        if ($ptag instanceof Profile_tag) {
            return $ptag;
        }

        $tagger_profile = Profile::getByID($tagger);
        $tagged_profile = Profile::getByID($tagged);

        if (Event::handle('StartTagProfile', array($tagger_profile, $tagged_profile, $tag))) {
            if (!$tagger_profile->canTag($tagged_profile)) {
                // TRANS: Client exception thrown trying to set a tag for a user that cannot be tagged.
                throw new ClientException(_('You cannot tag this user.'));
            }

            $tags = new Profile_list();
            $tags->tagger = $tagger;
            $count = (int) $tags->count('distinct tag');

            if ($count >= common_config('peopletag', 'maxtags')) {
                // TRANS: Client exception thrown trying to set more tags than allowed.
                throw new ClientException(sprintf(
                    _('You already have created %d or more tags ' .
                      'which is the maximum allowed number of tags. ' .
                      'Try using or deleting some existing tags.'),
                    common_config('peopletag', 'maxtags')
                ));
            }

            $plist = new Profile_list();
            $plist->query('START TRANSACTION');

            $profile_list = Profile_list::ensureTag($tagger, $tag, $desc, $private);

            if ($profile_list->taggedCount() >= common_config('peopletag', 'maxpeople')) {
                // TRANS: Client exception thrown when trying to add more people than allowed to a list.
                throw new ClientException(sprintf(
                    _('You already have %1$d or more people in list %2$s, ' .
                      'which is the maximum allowed number. ' .
                      'Try unlisting others first.'),
                    common_config('peopletag', 'maxpeople'),
                    $tag
                ));
            }

            $newtag = new Profile_tag();

            $newtag->tagger = $tagger;
            $newtag->tagged = $tagged;
            $newtag->tag = $tag;

            $result = $newtag->insert();

            if (!$result) {
                common_log_db_error($newtag, 'INSERT', __FILE__);
                $plist->query('ROLLBACK');
                return false;
            }

            try {
                $plist->query('COMMIT');
                Event::handle('EndTagProfile', array($newtag));
            } catch (Exception $e) {
                $newtag->delete();
                $profile_list->delete();
                throw $e;
            }

            $profile_list->taggedCount(true);
            self::blowCaches($tagger, $tagged);
        }

        return $newtag;
    }

    public static function unTag($tagger, $tagged, $tag)
    {
        $ptag = Profile_tag::pkeyGet(array('tagger' => $tagger,
                                           'tagged' => $tagged,
                                           'tag'    => $tag));
        if (!$ptag) {
            return true;
        }

        if (Event::handle('StartUntagProfile', array($ptag))) {
            $orig = clone($ptag);
            $result = $ptag->delete();
            if ($result === false) {
                common_log_db_error($this, 'DELETE', __FILE__);
                return false;
            }
            Event::handle('EndUntagProfile', array($orig));
            $profile_list = Profile_list::pkeyGet(array('tag' => $tag, 'tagger' => $tagger));
            if (!empty($profile_list)) {
                $profile_list->taggedCount(true);
            }
            self::blowCaches($tagger, $tagged);
            return true;
        }
    }

    // @fixme: move this to Profile_list?
    public static function cleanup($profile_list)
    {
        $ptag = new Profile_tag();
        $ptag->tagger = $profile_list->tagger;
        $ptag->tag = $profile_list->tag;
        $ptag->find();

        while ($ptag->fetch()) {
            if (Event::handle('StartUntagProfile', array($ptag))) {
                $orig = clone($ptag);
                $result = $ptag->delete();
                if (!$result) {
                    common_log_db_error($this, 'DELETE', __FILE__);
                }
                Event::handle('EndUntagProfile', array($orig));
            }
        }
    }

    // move a tag!
    public static function moveTag($orig, $new)
    {
        $tags = new Profile_tag();
        $result = $tags->query(sprintf(
            <<<'END'
            UPDATE profile_tag
              SET tag = %1$s, tagger = %2$s, modified = CURRENT_TIMESTAMP
              WHERE tag = %3$s AND tagger = %4$s
            END,
            $tags->_quote($new->tag),
            $tags->_quote($new->tagger),
            $tags->_quote($orig->tag),
            $tags->_quote($orig->tagger)
        ));

        if ($result === false) {
            common_log_db_error($tags, 'UPDATE', __FILE__);
            throw new Exception('Could not move Profile_tag, see db log for details.');
        }
        return $result;
    }

    public static function blowCaches($tagger, $tagged)
    {
        foreach (array(0, 1) as $perm) {
            self::blow(sprintf('profile_tag:tagger_tagged_privacy:%d-%d-%d', $tagger, $tagged, $perm));
        }
        return true;
    }

    // Return profiles with a given tag
    public static function getTagged($tagger, $tag)
    {
        $profile = new Profile();
        $profile->query('SELECT profile.* ' .
                        'FROM profile JOIN profile_tag ' .
                        'ON profile.id = profile_tag.tagged ' .
                        'WHERE profile_tag.tagger = ' . $profile->escape($tagger) . ' ' .
                        "AND profile_tag.tag = '" . $profile->escape($tag) . "' ");
        $tagged = [];
        while ($profile->fetch()) {
            $tagged[] = clone($profile);
        }
        return true;
    }

    public function insert()
    {
        $result = parent::insert();
        if ($result) {
            self::blow(
                'profile_list:tagged_count:%d:%s',
                $this->tagger,
                $this->tag
            );
        }
        return $result;
    }

    public function delete($useWhere = false)
    {
        $result = parent::delete($useWhere);
        if ($result !== false) {
            self::blow(
                'profile_list:tagged_count:%d:%s',
                $this->tagger,
                $this->tag
            );
        }
        return $result;
    }
}
