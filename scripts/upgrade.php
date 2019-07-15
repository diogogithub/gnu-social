#!/usr/bin/env php
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

/**
 * Upgrade database schema and data to latest software and check DB integrity
 * Usage: php upgrade.php [options]
 *
 * @package   GNUsocial
 * @author    Bhuvan Krishna <bhuvan@swecha.net>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'dfx::';
$longoptions = ['debug', 'files', 'extensions='];

$helptext = <<<END_OF_UPGRADE_HELP
php upgrade.php [options]
Upgrade database schema and data to latest software

END_OF_UPGRADE_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';


if (!defined('DEBUG')) {
    define('DEBUG', (bool)have_option('d', 'debug'));
}

function main()
{
    // "files" option enables possibly disk/resource intensive operations
    // that aren't really _required_ for the upgrade
    $iterate_files = (bool)have_option('f', 'files');

    if (Event::handle('StartUpgrade')) {
        fixupConversationURIs();

        updateSchemaCore();
        updateSchemaPlugins();

        // These replace old "fixup_*" scripts

        fixupNoticeConversation();
        initConversation();
        fixupGroupURI();
        if ($iterate_files) {
            printfnq("Running file iterations:\n");
            printfnq("* "); fixupFileGeometry();
            printfnq("* "); deleteLocalFileThumbnailsWithoutFilename();
            printfnq("* "); deleteMissingLocalFileThumbnails();
            printfnq("* "); fixupFileThumbnailUrlhash();
            printfnq("* "); setFilehashOnLocalFiles();
            printfnq("DONE.\n");
        } else {
            printfnq("Skipping intensive/long-running file iteration functions (enable with -f, should be done at least once!)\n");
        }

        initGroupProfileId();
        initLocalGroup();
        initNoticeReshare();

        initSubscriptionURI();
        initGroupMemberURI();

        initProfileLists();

        migrateProfilePrefs();

        Event::handle('EndUpgrade');
    }
}

function tableDefs()
{
    $schema = [];
    require INSTALLDIR . '/db/core.php';
    return $schema;
}

function updateSchemaCore()
{
    printfnq("Upgrading core schema...");

    $schema = Schema::get();
    $schemaUpdater = new SchemaUpdater($schema);
    foreach (tableDefs() as $table => $def) {
        $schemaUpdater->register($table, $def);
    }
    $schemaUpdater->checkSchema();

    printfnq("DONE.\n");
}

function updateSchemaPlugins()
{
    printfnq("Upgrading plugin schema...");

    Event::handle('BeforePluginCheckSchema');
    Event::handle('CheckSchema');

    printfnq("DONE.\n");
}

function fixupNoticeConversation()
{
    printfnq("Ensuring all notices have a conversation ID...");

    $notice = new Notice();
    $notice->whereAdd('conversation is null');
    $notice->whereAdd('conversation = 0', 'OR');
    $notice->orderBy('id'); // try to get originals before replies
    $notice->find();

    while ($notice->fetch()) {
        try {
            $cid = null;

            $orig = clone($notice);

            if (!empty($notice->reply_to)) {
                $reply = Notice::getKV('id', $notice->reply_to);

                if ($reply instanceof Notice && !empty($reply->conversation)) {
                    $notice->conversation = $reply->conversation;
                }
                unset($reply);
            }

            // if still empty
            if (empty($notice->conversation)) {
                $child = new Notice();
                $child->reply_to = $notice->getID();
                $child->limit(1);
                if ($child->find(true) && !empty($child->conversation)) {
                    $notice->conversation = $child->conversation;
                }
                unset($child);
            }

            // if _still_ empty we just create our own conversation
            if (empty($notice->conversation)) {
                $notice->conversation = $notice->getID();
            }

            $result = $notice->update($orig);

            unset($orig);
        } catch (Exception $e) {
            print("Error setting conversation: " . $e->getMessage());
        }
    }

    printfnq("DONE.\n");
}

function fixupGroupURI()
{
    printfnq("Ensuring all groups have an URI...");

    $group = new User_group();
    $group->whereAdd('uri IS NULL');

    if ($group->find()) {
        while ($group->fetch()) {
            $orig = User_group::getKV('id', $group->id);
            $group->uri = $group->getUri();
            $group->update($orig);
        }
    }

    printfnq("DONE.\n");
}

function initConversation()
{
    if (common_config('fix', 'upgrade_initConversation') <= 1) {
        printfnq(sprintf("Skipping %s, fixed by previous upgrade.\n", __METHOD__));
        return;
    }

    printfnq("Ensuring all conversations have a row in conversation table...");

    $notice = new Notice();
    $notice->selectAdd();
    $notice->selectAdd('DISTINCT conversation');
    $notice->joinAdd(['conversation', 'conversation:id'], 'LEFT');  // LEFT to get the null values for conversation.id
    $notice->whereAdd('conversation.id IS NULL');

    if ($notice->find()) {
        printfnq(" fixing {$notice->N} missing conversation entries...");
    }

    while ($notice->fetch()) {
        $id = $notice->conversation;

        $uri = common_local_url('conversation', ['id' => $id]);

        // @fixme db_dataobject won't save our value for an autoincrement
        // so we're bypassing the insert wrappers
        $conv = new Conversation();
        $sql = "INSERT INTO conversation (id,uri,created) VALUES (%d,'%s','%s')";
        $sql = sprintf(
            $sql,
            $id,
            $conv->escape($uri),
            $conv->escape(common_sql_now())
        );
        $conv->query($sql);
    }

    // This is something we should only have to do once unless introducing new, bad code.
    if (DEBUG) {
        printfnq(sprintf('Storing in config that we have done %s', __METHOD__));
    }
    common_config_set('fix', 'upgrade_initConversation', 1);

    printfnq("DONE.\n");
}

function fixupConversationURIs()
{
    printfnq("Ensuring all conversations have a URI...");

    $conv = new Conversation();
    $conv->whereAdd('uri IS NULL');

    if ($conv->find()) {
        $rounds = 0;
        while ($conv->fetch()) {
            $uri = common_local_url('conversation', ['id' => $conv->id]);
            $sql = sprintf(
                'UPDATE conversation SET uri="%1$s" WHERE id="%2$d";',
                $conv->escape($uri),
                $conv->id
            );
            $conv->query($sql);
            if (($conv->N-++$rounds) % 500 == 0) {
                printfnq(sprintf(' %d items left...', $conv->N-$rounds));
            }
        }
    }

    printfnq("DONE.\n");
}

function initGroupProfileId()
{
    printfnq("Ensuring all User_group entries have a Profile and profile_id...");

    $group = new User_group();
    $group->whereAdd('NOT EXISTS (SELECT id FROM profile WHERE id = user_group.profile_id)');
    $group->find();

    while ($group->fetch()) {
        try {
            // We must create a new, incrementally assigned profile_id
            $profile = new Profile();
            $profile->nickname   = $group->nickname;
            $profile->fullname   = $group->fullname;
            $profile->profileurl = $group->mainpage;
            $profile->homepage   = $group->homepage;
            $profile->bio        = $group->description;
            $profile->location   = $group->location;
            $profile->created    = $group->created;
            $profile->modified   = $group->modified;

            $profile->query('BEGIN');
            $id = $profile->insert();
            if (empty($id)) {
                $profile->query('ROLLBACK');
                throw new Exception('Profile insertion failed, profileurl: '.$profile->profileurl);
            }
            $group->query("UPDATE user_group SET profile_id={$id} WHERE id={$group->id}");
            $profile->query('COMMIT');

            $profile->free();
        } catch (Exception $e) {
            printfv("Error initializing Profile for group {$group->nickname}:" . $e->getMessage());
        }
    }

    printfnq("DONE.\n");
}

function initLocalGroup()
{
    printfnq("Ensuring all local user groups have a local_group...");

    $group = new User_group();
    $group->whereAdd('NOT EXISTS (select group_id from local_group where group_id = user_group.id)');
    $group->find();

    while ($group->fetch()) {
        try {
            // Hack to check for local groups
            if ($group->getUri() == common_local_url('groupbyid', ['id' => $group->id])) {
                $lg = new Local_group();

                $lg->group_id = $group->id;
                $lg->nickname = $group->nickname;
                $lg->created  = $group->created; // XXX: common_sql_now() ?
                $lg->modified = $group->modified;

                $lg->insert();
            }
        } catch (Exception $e) {
            printfv("Error initializing local group for {$group->nickname}:" . $e->getMessage());
        }
    }

    printfnq("DONE.\n");
}

function initNoticeReshare()
{
    if (common_config('fix', 'upgrade_initNoticeReshare') <= 1) {
        printfnq(sprintf("Skipping %s, fixed by previous upgrade.\n", __METHOD__));
        return;
    }

    printfnq("Ensuring all reshares have the correct verb and object-type...");

    $notice = new Notice();
    $notice->whereAdd('repeat_of is not null');
    $notice->whereAdd('(verb != "'.ActivityVerb::SHARE.'" OR object_type != "'.ActivityObject::ACTIVITY.'")');

    if ($notice->find()) {
        while ($notice->fetch()) {
            try {
                $orig = Notice::getKV('id', $notice->id);
                $notice->verb = ActivityVerb::SHARE;
                $notice->object_type = ActivityObject::ACTIVITY;
                $notice->update($orig);
            } catch (Exception $e) {
                printfv("Error updating verb and object_type for {$notice->id}:" . $e->getMessage());
            }
        }
    }

    // This is something we should only have to do once unless introducing new, bad code.
    if (DEBUG) {
        printfnq(sprintf('Storing in config that we have done %s', __METHOD__));
    }
    common_config_set('fix', 'upgrade_initNoticeReshare', 1);

    printfnq("DONE.\n");
}

function initSubscriptionURI()
{
    printfnq("Ensuring all subscriptions have a URI...");

    $sub = new Subscription();
    $sub->whereAdd('uri IS NULL');

    if ($sub->find()) {
        while ($sub->fetch()) {
            try {
                $sub->decache();
                $sub->query(sprintf(
                    'UPDATE subscription '.
                    'SET uri = "%s" '.
                    'WHERE subscriber = %d '.
                      'AND subscribed = %d',
                    $sub->escape(Subscription::newUri($sub->getSubscriber(), $sub->getSubscribed(), $sub->created)),
                    $sub->subscriber,
                    $sub->subscribed
                ));
            } catch (Exception $e) {
                common_log(LOG_ERR, "Error updated subscription URI: " . $e->getMessage());
            }
        }
    }

    printfnq("DONE.\n");
}

function initGroupMemberURI()
{
    printfnq("Ensuring all group memberships have a URI...");

    $mem = new Group_member();
    $mem->whereAdd('uri IS NULL');

    if ($mem->find()) {
        while ($mem->fetch()) {
            try {
                $mem->decache();
                $mem->query(sprintf(
                    'UPDATE group_member '.
                    'SET uri = "%s" '.
                    'WHERE profile_id = %d ' .
                      'AND group_id = %d',
                    Group_member::newUri(Profile::getByID($mem->profile_id), User_group::getByID($mem->group_id), $mem->created),
                    $mem->profile_id,
                    $mem->group_id
                ));
            } catch (Exception $e) {
                common_log(LOG_ERR, "Error updated membership URI: " . $e->getMessage());
            }
        }
    }

    printfnq("DONE.\n");
}

function initProfileLists()
{
    printfnq("Ensuring all profile tags have a corresponding list...");

    $ptag = new Profile_tag();
    $ptag->selectAdd();
    $ptag->selectAdd('tagger, tag, COUNT(*) AS tagged_count');
    $ptag->whereAdd('NOT EXISTS (SELECT tagger, tagged FROM profile_list '.
                    'WHERE profile_tag.tagger = profile_list.tagger '.
                    'AND profile_tag.tag = profile_list.tag)');
    $ptag->groupBy('tagger, tag');
    $ptag->orderBy('tagger, tag');

    if ($ptag->find()) {
        while ($ptag->fetch()) {
            $plist = new Profile_list();

            $plist->tagger   = $ptag->tagger;
            $plist->tag      = $ptag->tag;
            $plist->private  = 0;
            $plist->created  = common_sql_now();
            $plist->modified = $plist->created;
            $plist->mainpage = common_local_url(
                'showprofiletag',
                ['tagger' => $plist->getTagger()->nickname,
                 'tag'    => $plist->tag]
            );
            ;

            $plist->tagged_count     = $ptag->tagged_count;
            $plist->subscriber_count = 0;

            $plist->insert();

            $orig = clone($plist);
            // After insert since it uses auto-generated ID
            $plist->uri = common_local_url(
                'profiletagbyid',
                ['id'        => $plist->id,
                 'tagger_id' => $plist->tagger]
            );

            $plist->update($orig);
        }
    }

    printfnq("DONE.\n");
}

/*
 * Added as we now store interpretd width and height in File table.
 */
function fixupFileGeometry()
{
    printfnq("Ensuring width and height is set for supported local File objects...");

    $file = new File();
    $file->whereAdd('filename IS NOT NULL');    // local files
    $file->whereAdd('width IS NULL OR width = 0');

    if ($file->find()) {
        while ($file->fetch()) {
            if (DEBUG) {
                printfnq(sprintf('Found file without width: %s\n', _ve($file->getFilename())));
            }

            // Set file geometrical properties if available
            try {
                $image = ImageFile::fromFileObject($file);
            } catch (ServerException $e) {
                // We couldn't make out an image from the file.
                if (DEBUG) {
                    printfnq(sprintf('Could not make an image out of the file.\n'));
                }
                continue;
            }
            $orig = clone($file);
            $file->width = $image->width;
            $file->height = $image->height;
            if (DEBUG) {
                printfnq(sprintf('Setting image file and with to %sx%s.\n', $file->width, $file->height));
            }
            $file->update($orig);

            // FIXME: Do this more automagically inside ImageFile or so.
            if ($image->getPath() != $file->getPath()) {
                if (DEBUG) {
                    printfnq(sprintf('Deleting the temporarily stored ImageFile.\n'));
                }
                $image->unlink();
            }
            unset($image);
        }
    }

    printfnq("DONE.\n");
}

/*
 * File_thumbnail objects for local Files store their own filenames in the database.
 */
function deleteLocalFileThumbnailsWithoutFilename()
{
    printfnq("Removing all local File_thumbnail entries without filename property...");

    $file = new File();
    $file->whereAdd('filename IS NOT NULL');    // local files

    if ($file->find()) {
        // Looping through local File entries
        while ($file->fetch()) {
            $thumbs = new File_thumbnail();
            $thumbs->file_id = $file->id;
            $thumbs->whereAdd('filename IS NULL OR filename = ""');
            // Checking if there were any File_thumbnail entries without filename
            if (!$thumbs->find()) {
                continue;
            }
            // deleting incomplete entry to allow regeneration
            while ($thumbs->fetch()) {
                $thumbs->delete();
            }
        }
    }

    printfnq("DONE.\n");
}

/*
 * Delete File_thumbnail entries where the referenced file does not exist.
 */
function deleteMissingLocalFileThumbnails()
{
    printfnq("Removing all local File_thumbnail entries without existing files...");

    $thumbs = new File_thumbnail();
    $thumbs->whereAdd('filename IS NOT NULL AND filename != ""');
    // Checking if there were any File_thumbnail entries without filename
    if ($thumbs->find()) {
        while ($thumbs->fetch()) {
            try {
                $thumbs->getPath();
            } catch (FileNotFoundException $e) {
                $thumbs->delete();
            }
        }
    }

    printfnq("DONE.\n");
}

/*
 * Files are now stored with their hash, so let's generate for previously uploaded files.
 */
function setFilehashOnLocalFiles()
{
    printfnq('Ensuring all local files have the filehash field set...');

    $file = new File();
    $file->whereAdd('filename IS NOT NULL AND filename != ""');        // local files
    $file->whereAdd('filehash IS NULL', 'AND');     // without filehash value

    if ($file->find()) {
        while ($file->fetch()) {
            try {
                $orig = clone($file);
                $file->filehash = hash_file(File::FILEHASH_ALG, $file->getPath());
                $file->update($orig);
            } catch (FileNotFoundException $e) {
                echo "\n    WARNING: file ID {$file->id} does not exist on path '{$e->path}'. If there is no file system error, run: php scripts/clean_file_table.php";
            }
        }
    }

    printfnq("DONE.\n");
}

function fixupFileThumbnailUrlhash()
{
    printfnq("Setting urlhash for File_thumbnail entries: ");

    $thumb = new File_thumbnail();
    $thumb->query('UPDATE '.$thumb->escapedTableName().' SET urlhash=SHA2(url, 256) WHERE'.
                    ' url IS NOT NULL AND'. // find all entries with a url value
                    ' url != "" AND'.       // precaution against non-null empty strings
                    ' urlhash IS NULL');    // but don't touch those we've already calculated

    printfnq("DONE.\n");
}

function migrateProfilePrefs()
{
    printfnq("Finding and possibly migrating Profile_prefs entries: ");

    $prefs = [];   // ['qvitter' => ['cover_photo'=>'profile_banner_url', ...], ...]
    Event::handle('GetProfilePrefsMigrations', [&$prefs]);

    foreach ($prefs as $namespace=>$mods) {
        echo "$namespace... ";
        assert(is_array($mods));
        $p = new Profile_prefs();
        $p->namespace = $namespace;
        // find all entries in all modified topics given in this namespace
        $p->whereAddIn('topic', array_keys($mods), $p->columnType('topic'));
        $p->find();
        while ($p->fetch()) {
            // for each entry, update 'topic' to the new key value
            $orig = clone($p);
            $p->topic = $mods[$p->topic];
            $p->updateWithKeys($orig);
        }
    }

    printfnq("DONE.\n");
}

main();
