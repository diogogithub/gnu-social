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
 * Adds a setting to allow a user to hide #NSFW-hashtagged notices behind a
 * blocker image until clicked.
 *
 * @package   GNUsocial
 * @author    MoonMan
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class SensitiveContentPlugin
 * Handles the main UI stuff
 *
 * @package   GNUsocial
 * @author    MoonMan
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SensitiveContentPlugin extends Plugin
{
    public $blockerimage = 'img/blocker.png';
    public $hideforvisitors = false;
    const PLUGIN_VERSION = '0.1.0';

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Sensitive Content',
            'version' => self::PLUGIN_VERSION,
            'author' => 'MoonMan',
            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/SensitiveContent/',
            'description' => _m('Mark, hide/show sensitive notices like on Twitter.'),
        ];
        return true;
    }

    /**
     * What blocker image to use?
     *
     * @return string
     */
    public function getBlockerImage(): string
    {
        return Plugin::staticPath('SensitiveContent', '') . $this->blockerimage;
    }

    /**
     * @param $profile
     * @return bool
     */
    public function shouldHide($profile): bool
    {
        if (isset($profile) && $profile instanceof Profile) {
            return $this->getHideSensitive($profile);
        }
        return $this->hideforvisitors;
    }

    public function getHideSensitive(Profile $profile): bool
    {
        $c = Cache::instance();

        // if (!empty($c)) {
        //     $hidesensitive = $c->get(Cache::key('profile:hide_sensitive:'.$profile->id));
        //     if (is_numeric($hidesensitive)) {
        //         return (bool) $hidesensitive;
        //     } else {
        //         return false;
        //     }
        // }

        $hidesensitive = $profile->getPref('MoonMan', 'hide_sensitive', '0');

        if (!empty($c)) {
            //not using it yet.
            $c->set(Cache::key('profile:hide_sensitive:' . $profile->id), $hidesensitive);
        }

        //common_log(LOG_DEBUG, "SENSITIVECONTENT hidesensitive? id " . $profile->id . " value " . (bool) $hidesensitive );

        if (is_null($hidesensitive)) {
            return false;
        }
        if (is_numeric($hidesensitive)) {
            return (bool)$hidesensitive;
        }
        return false;
    }

    /* GNU social EVENTS */

    public function onNoticeSimpleStatusArray($notice, &$twitter_status, $scoped)
    {
        $twitter_status['tags'] = $notice->getTags();
    }

    public function onTwitterUserArray($profile, &$twitter_user, $scoped)
    {
        if ($scoped instanceof Profile && $scoped->sameAs($profile)) {
            $twitter_user['hide_sensitive'] = $this->getHideSensitive($scoped);
        }
    }

    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect('settings/sensitivecontent',
            ['action' => 'sensitivecontentsettings']);
    }

    public function onEndAccountSettingsNav($action)
    {
        $action->menuItem(common_local_url('sensitivecontentsettings'),
            _m('MENU', 'Sensitive Content'),
            _m('Settings for display of sensitive content.'));

        return true;
    }

    public function onEndShowStyles(Action $action)
    {
        $blocker = $this->getBlockerImage();

        $styles = <<<EOB

/* default no show */
html .tagcontainer > footer > .attachments > .inline-attachment > .attachment-wrapper > .sensitive-blocker {
    display: none;
}

html[data-hidesensitive='true'] .tagcontainer.data-tag-nsfw > footer > .attachments > .inline-attachment > .attachment-wrapper > .sensitive-blocker {
display: block;
/* hugh, two magic numbers below, sorry :( */
width: 98%;
height: 90%;
position: absolute;
/* z-index: 100; */
/* background-color: #d4baba; */
background-color: black;
background-image: url({$blocker});
background-repeat: no-repeat;
background-position: center center;
background-size: contain;
transition: opacity 1s ease-in-out;
}

html[data-hidesensitive='true'] .tagcontainer.data-tag-nsfw > footer > .attachments > .inline-attachment > .attachment-wrapper > .sensitive-blocker.reveal {
    opacity: 0;
}

EOB;

        $action->style($styles);
    }

    public function onStartShowAttachmentRepresentation($out, $file)
    {
        $classes = 'sensitive-blocker'; //'sensitive-blocker';
        $thumbnail = null;
        try {
            $thumbnail = $file->getThumbnail();
        } catch (Exception $e) {
            $thumbnail = null;
        }
        // $thumb_width_css = $thumbnail ? $thumbnail->width . 'px' : '100%';
        // $thumb_height_css = $thumbnail ? $thumbnail->height . 'px' : '100%';

        $out->elementStart('div', [
            'class' => 'attachment-wrapper',
            /* 'style' => "height: {$thumb_height_css}; width: {$thumb_width_css};", */
        ]); // needs height of thumb
        $out->elementStart('div', [
            'class' => $classes,
            'onclick' => 'toggleSpoiler(event)',
            /* 'style' => "height: {$thumb_height_css}; width: {$thumb_width_css};", */
        ]);
        $out->raw('&nbsp;');
        $out->elementEnd('div');
    }

    public function onEndShowAttachmentRepresentation($out, $file)
    {
        $out->elementEnd('div');
    }

    public function onEndShowScripts(Action $action)
    {
        $profile = $action->getScoped();
        $hidesensitive = $this->shouldHide($profile);
        $hidesensitive_string = $hidesensitive ? 'true' : 'false';

        $inline = <<<EOB

window.hidesensitive = {$hidesensitive_string};

function toggleSpoiler(evt) {
    if (window.hidesensitive) evt.target.classList.toggle('reveal');
}
EOB;
        $action->inlineScript($inline);
    }

    public function onEndOpenNoticeListItemElement(NoticeListItem $nli)
    {
        $rawtags = $nli->getNotice()->getTags();
        $classes = 'tagcontainer';

        foreach ($rawtags as $tag) {
            $classes = $classes . ' data-tag-' . $tag;
        }

        $nli->elementStart('span', ['class' => $classes]);
        //$nli->elementEnd('span');
    }

    public function onStartCloseNoticeListItemElement(NoticeListItem $nli)
    {
        $nli->elementEnd('span');
    }

    public function onStartHtmlElement($action, &$attrs)
    {
        $profile = Profile::current();
        $hidesensitive = $this->shouldHide($profile);

        $attrs = array_merge($attrs,
            ['data-hidesensitive' => ($hidesensitive ? 'true' : 'false')]
        );
    }

    /* Qvitter's EVENTS */

    public function onQvitterEndShowHeadElements(Action $action)
    {
        $blocker = $this->getBlockerImage();
        common_log(LOG_DEBUG, 'SENSITIVECONTENT ' . $blocker);

        $styles = <<<EOB

.sensitive-blocker {
  display: none;
}

div.stream-item.notice.sensitive-notice .sensitive-blocker {
display: block;
width: 100%;
height: 100%;
position: absolute;
z-index: 100;
background-color: #d4baba;
background-image: url({$blocker});
background-repeat: no-repeat;
background-position: center center;
background-size: contain;
transition: opacity 1s ease-in-out;
}

.sensitive-blocker:hover {
  opacity: .5;
}

div.stream-item.notice.expanded.sensitive-notice .sensitive-blocker {
display: none;
background-color: transparent;
background-image: none;
}

EOB;

        $action->style($styles);
    }

    public function onQvitterEndShowScripts(Action $action)
    {
        $action->script(Plugin::staticPath('SensitiveContent', '') . 'js/sensitivecontent.js');
    }
}
