<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class NodeinfoPlugin extends Plugin
{
    const VERSION = '1.0.1';

    public function onRouterInitialized($m)
    {
        $m->connect(
            '.well-known/nodeinfo',
            array(
                'action' => 'nodeinfojrd'
            )
        );

        $m->connect(
            'main/nodeinfo/2.0',
            array(
                'action' => 'nodeinfo_2_0'
            )
        );

        return true;
    }

    /**
     * Make sure necessary tables are filled out.
     *
     * @return boolean hook true
     */
    public function onCheckSchema()
    {
        // Ensure schema
        $schema = Schema::get();
        $schema->ensureTable('usage_stats', Usage_stats::schemaDef());

        // Ensure default rows
        if (Usage_stats::getKV('type', 'users') == null) {
            $us = new Usage_stats();
            $us->type = 'users';
            $us->insert();
        }

        if (Usage_stats::getKV('type', 'posts') == null) {
            $us = new Usage_stats();
            $us->type = 'posts';
            $us->insert();
        }

        if (Usage_stats::getKV('type', 'comments') == null) {
            $us = new Usage_stats();
            $us->type = 'comments';
            $us->insert();
        }

        return true;
    }

    /**
     * Increment notices/replies counter
     *
     * @return boolean hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartNoticeDistribute($notice)
    {
        assert($notice->id > 0);        // Ignore if not a valid notice

        $profile = $notice->getProfile();

        if (!$profile->isLocal()) {
            return true;
        }

        // Ignore for activity/non-post-verb notices
        if (method_exists('ActivityUtils', 'compareVerbs')) {
            $is_post_verb = ActivityUtils::compareVerbs(
                $notice->verb,
                [ActivityVerb::POST]
            );
        } else {
            $is_post_verb = ($notice->verb == ActivityVerb::POST ? true : false);
        }
        if ($notice->source == 'activity' || !$is_post_verb) {
            return true;
        }

        // Is a reply?
        if ($notice->reply_to) {
            $us = Usage_stats::getKV('type', 'comments');
            $us->count += 1;
            $us->update();
            return true;
        }

        // Is an Announce?
        if ($notice->isRepeat()) {
            return true;
        }

        $us = Usage_stats::getKV('type', 'posts');
        $us->count += 1;
        $us->update();

        // That was it
        return true;
    }

    /**
     * Decrement notices/replies counter
     *
     * @return boolean hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartDeleteOwnNotice($user, $notice)
    {
        $profile = $user->getProfile();

        // Only count local notices
        if (!$profile->isLocal()) {
            return true;
        }

        if ($notice->reply_to) {
            $us = Usage_stats::getKV('type', 'comments');
            $us->count -= 1;
            $us->update();
            return true;
        }

        $us = Usage_stats::getKV('type', 'posts');
        $us->count -= 1;
        $us->update();
        return true;
    }

    /**
     * Increment users counter
     *
     * @return boolean hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndRegistrationTry()
    {
        $us = Usage_stats::getKV('type', 'users');
        $us->count += 1;
        $us->update();
        return true;
    }

    /**
     * Decrement users counter
     *
     * @return boolean hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndDeleteUser()
    {
        $us = Usage_stats::getKV('type', 'users');
        $us->count -= 1;
        $us->update();
        return true;
    }


    public function onPluginVersion(array &$versions)
    {
        $versions[] = ['name' => 'Nodeinfo',
            'version' => self::VERSION,
            'author' => 'chimo',
            'homepage' => 'https://github.com/chimo/gs-nodeinfo',
            'description' => _m('Plugin that presents basic instance information using the NodeInfo standard.')];
        return true;
    }

    public function onEndUpgrade()
    {
        $users = new Usage_stats();
        if ($users->getUserCount() == 0) {
            define('NODEINFO_UPGRADE', true);
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'fix_stats.php';
        }
    }
}
