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
 * Table Definition for avatar
 */

class Avatar extends Managed_DataObject
{
    public $__table = 'avatar';                          // table name
    public $profile_id;                      // int(4)  primary_key not_null
    public $original;                        // bool    default_false
    public $width;                           // int(4)  primary_key not_null
    public $height;                          // int(4)  primary_key not_null
    public $mediatype;                       // varchar(32)   not_null
    public $filename;                        // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'),
                'original' => array('type' => 'bool', 'default' => false, 'description' => 'uploaded by user or generated?'),
                'width' => array('type' => 'int', 'not null' => true, 'description' => 'image width'),
                'height' => array('type' => 'int', 'not null' => true, 'description' => 'image height'),
                'mediatype' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'file type'),
                'filename' => array('type' => 'varchar', 'length' => 191, 'description' => 'local filename, if local'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('profile_id', 'width', 'height'),
            'unique keys' => array(
//                'avatar_filename_key' => array('filename'),
            ),
            'foreign keys' => array(
                'avatar_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
        );
    }

    // We clean up the file, too
    public function delete($useWhere = false)
    {
        $filename = $this->filename;
        if (file_exists(Avatar::path($filename))) {
            @unlink(Avatar::path($filename));
        }

        return parent::delete($useWhere);
    }

    /*
     * Deletes all avatars (but may spare the original) from a profile.
     *
     * @param   Profile $target     The profile we're deleting avatars of.
     * @param   boolean $original   Whether original should be removed or not.
     */
    public static function deleteFromProfile(Profile $target, $original = true)
    {
        try {
            $avatars = self::getProfileAvatars($target);
            foreach ($avatars as $avatar) {
                if ($avatar->original && !$original) {
                    continue;
                }
                $avatar->delete();
            }
        } catch (NoAvatarException $e) {
            // There are no avatars to delete, a sort of success.
        }

        return true;
    }

    protected static $_avatars = [];

    /*
     * Get an avatar by profile. Currently can't call newSize with $height
     */
    public static function byProfile(Profile $target, $width=null, $height=null)
    {
        $width  = intval($width);
        $height = !is_null($height) ? intval($height) : null;
        if (is_null($height)) {
            $height = $width;
        }

        $size = "{$width}x{$height}";
        if (!isset(self::$_avatars[$target->id])) {
            self::$_avatars[$target->id] = array();
        } elseif (isset(self::$_avatars[$target->id][$size])) {
            return self::$_avatars[$target->id][$size];
        }

        $avatar = null;
        if (Event::handle('StartProfileGetAvatar', array($target, $width, &$avatar))) {
            $avatar = self::pkeyGet(
                array(
                    'profile_id' => $target->id,
                    'width'      => $width,
                    'height'     => $height,
                )
            );
            Event::handle('EndProfileGetAvatar', array($target, $width, &$avatar));
        }

        if (is_null($avatar)) {
            // Obviously we can't find an avatar, so let's resize the original!
            $avatar = Avatar::newSize($target, $width);
        } elseif (!($avatar instanceof Avatar)) {
            throw new NoAvatarException($target, $avatar);
        }

        self::$_avatars[$target->id]["{$avatar->width}x{$avatar->height}"] = $avatar;
        return $avatar;
    }

    public static function getUploaded(Profile $target)
    {
        $avatar = new Avatar();
        $avatar->profile_id = $target->id;
        $avatar->original = true;
        if (!$avatar->find(true)) {
            throw new NoAvatarException($target, $avatar);
        }
        if (!file_exists(Avatar::path($avatar->filename))) {
            // The delete call may be odd for, say, unmounted filesystems
            // that cause a file to currently not exist, but actually it does...
            $avatar->delete();
            throw new NoAvatarException($target, $avatar);
        }
        return $avatar;
    }

    public static function getProfileAvatars(Profile $target)
    {
        $avatar = new Avatar();
        $avatar->profile_id = $target->id;
        if (!$avatar->find()) {
            throw new NoAvatarException($target, $avatar);
        }
        return $avatar->fetchAll();
    }

    /**
     * Where should the avatar go for this user?
     * @param int $id user id
     * @param string $extension file extension
     * @param int|null $size file size
     * @param string|null $extra extra bit for the filename
     * @return string
     */
    public static function filename(int $id, string $extension, ?int $size = null, ?string $extra = null)
    {
        if ($size) {
            return $id . '-' . $size . (($extra) ? ('-' . $extra) : '') . $extension;
        } else {
            return $id . '-original' . (($extra) ? ('-' . $extra) : '') . $extension;
        }
    }

    public static function path($filename)
    {
        $dir = common_config('avatar', 'dir');

        if ($dir[strlen($dir)-1] != '/') {
            $dir .= '/';
        }

        return $dir . $filename;
    }

    public static function url($filename)
    {
        $path = common_config('avatar', 'url_base');

        if ($path[strlen($path)-1] != '/') {
            $path .= '/';
        }

        if ($path[0] != '/') {
            $path = '/'.$path;
        }

        $server = common_config('avatar', 'server');

        if (empty($server)) {
            $server = common_config('site', 'server');
        }

        $ssl = (common_config('avatar', 'ssl') || GNUsocial::useHTTPS());

        $protocol = ($ssl) ? 'https' : 'http';

        return $protocol.'://'.$server.$path.$filename;
    }

    public function displayUrl()
    {
        return Avatar::url($this->filename);
    }

    public static function urlByProfile(Profile $target, $width = null, $height = null)
    {
        try {
            return self::byProfile($target, $width, $height)->displayUrl();
        } catch (Exception $e) {
            return self::defaultImage($width);
        }
    }

    public static function defaultImage($size = null)
    {
        if (is_null($size)) {
            $size = AVATAR_PROFILE_SIZE;
        }
        static $sizenames = array(AVATAR_PROFILE_SIZE => 'profile',
                                  AVATAR_STREAM_SIZE => 'stream',
                                  AVATAR_MINI_SIZE => 'mini');
        return Theme::path('default-avatar-'.$sizenames[$size].'.png');
    }

    public static function newSize(Profile $target, $width)
    {
        $width = intval($width);
        if ($width < 1 || $width > common_config('avatar', 'maxsize')) {
            // TRANS: An error message when avatar size is unreasonable
            throw new Exception(_m('Avatar size too large'));
        }
        // So far we only have square avatars and I don't have time to
        // rewrite support for non-square ones right now ;)
        $height = $width;

        $original = Avatar::getUploaded($target);

        $imagefile = new ImageFile(null, Avatar::path($original->filename));
        $filename = Avatar::filename(
            $target->getID(),
            image_type_to_extension($imagefile->preferredType()),
            $width,
            common_timestamp()
        );
        $imagefile->resizeTo(Avatar::path($filename), array('width'=>$width, 'height'=>$height));

        $scaled = clone($original);
        $scaled->original = false;
        $scaled->width = $width;
        $scaled->height = $height;
        $scaled->filename = $filename;
        $scaled->created = common_sql_now();

        if (!$scaled->insert()) {
            // TRANS: An error message when unable to insert avatar data into the db
            throw new Exception(_m('Could not insert new avatar data to database'));
        }

        // Return the new avatar object
        return $scaled;
    }
}
