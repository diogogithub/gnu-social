<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class SensitiveContentPlugin extends Plugin
{
	const PLUGIN_VERSION = '0.0.1';

	function onPluginVersion(array &$versions)
	{
		$versions[] = array('name' => 'Sensitive Content',
			'version' => self::PLUGIN_VERSION,
			'author' => 'MoonMan',
			'homepage' => 'https://gitgud.io/ShitposterClub/SensitiveContent/',
			'description' =>
				_m('Mark, hide/show sensitive notices like on Twitter.'));
		return true;
	}

	static function settings($setting)
	{
		$settings['blockerimage'] = Plugin::staticPath('SensitiveContent', '').'img/blocker.png';

		$configphpsettings = common_config('site','sensitivecontent') ?: array();
 		foreach($configphpsettings as $configphpsetting=>$value) {
			$settings[$configphpsetting] = $value;
		}

		if(isset($settings[$setting])) {
			return $settings[$setting];
		}
		else FALSE;
	}

	function onNoticeSimpleStatusArray($notice, &$twitter_status, $scoped)
	{
		$twitter_status['tags'] = $notice->getTags();
	}

	function onTwitterUserArray($profile, &$twitter_user, $scoped)
	{
		if ($scoped instanceof Profile  && $scoped->sameAs($profile)) {
			$twitter_user['hide_sensitive'] = $this->getHideSensitive($scoped);
		}
	}

	public function onRouterInitialized(URLMapper $m)
	{
		$m->connect('settings/sensitivecontent',
			array('action' => 'sensitivecontentsettings'));
	}


	function onEndAccountSettingsNav($action)
	{
		$action->menuItem(common_local_url('sensitivecontentsettings'),
			_m('MENU', 'Sensitive Content'),
			_m('Settings for display of sensitive content.'));

		return true;
	}


	public function onQvitterEndShowHeadElements(Action $action)
	{
		$blocker = static::settings('blockerimage');
		common_log( LOG_DEBUG, "SENSITIVECONTENT " . $blocker );


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
background-image: url($blocker);
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

	function onQvitterEndShowScripts(Action $action)
	{
		$action->script( Plugin::staticPath('SensitiveContent', '').'js/sensitivecontent.js' );
	}

	function onEndShowStyles(Action $action)
	{
		$blocker = static::settings('blockerimage');

		$styles =  <<<EOB

/* default no show */
html .tagcontainer > footer > .attachments > .inline-attachment > .attachment-wrapper > .sensitive-blocker {
	display: none;
}

html[data-hidesensitive='true'] .tagcontainer.data-tag-nsfw > footer > .attachments > .inline-attachment > .attachment-wrapper > .sensitive-blocker {
display: block;
width: 100%;
height: 100%;
position: absolute;
z-index: 100;
/*background-color: #d4baba;*/
background-color: black;
background-image: url($blocker);
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

	function onStartShowAttachmentRepresentation($out, $file)
	{
		$profile = Profile::current();

                if (!is_null($profile) && $profile instanceof Profile)
                {
                        $hidesensitive = $this->getHideSensitive($profile);
                }
                else
                {
                        $hidesensitive = false;
                }


		$classes = "sensitive-blocker"; //'sensitive-blocker';

		$out->elementStart('div', array(
			'class'=>'attachment-wrapper',
			'style'=>'height: ' . $file->getThumbnail()->height . 'px; width: ' . $file->getThumbnail()->width . 'px;'
		)); /*needs height of thumb*/
		$out->elementStart('div', array(
			'class'=>$classes,
			'onclick'=>'toggleSpoiler(event)',
			'style'=>'height: ' . $file->getThumbnail()->height . 'px; width: ' . $file->getThumbnail()->width . 'px;'
		));
		$out->raw('&nbsp;');
		$out->elementEnd('div');
	}

	function onEndShowAttachmentRepresentation($out, $file)
	{
		$out->elementEnd('div');
	}

	function onEndShowScripts(Action $action)
	{
		$profile = $action->getScoped();
		if (!is_null($profile) && $profile instanceof Profile)
		{
			$hidesensitive = $this->getHideSensitive($profile) ? "true" : "false";
		}
		else
		{
			$hidesensitive = "false";
		}

		$inline = <<<EOB

window.hidesensitive = $hidesensitive ;

function toggleSpoiler(evt) {
	if (window.hidesensitive) evt.target.classList.toggle('reveal');
}
EOB;
		$action->inlineScript($inline);
	}

	function onEndOpenNoticeListItemElement(NoticeListItem $nli)
	{
		$rawtags = $nli->getNotice()->getTags();
		$classes = "tagcontainer";

		foreach($rawtags as $tag)
		{
			$classes = $classes . ' data-tag-' . $tag;
                }


		$nli->elementStart('span', array('class' => $classes));
		//$nli->elementEnd('span');
	}

	function onStartCloseNoticeListItemElement(NoticeListItem $nli)
	{
		$nli->elementEnd('span');
	}

	function onStartHtmlElement($action, &$attrs) {
		$profile = Profile::current();

                if (!is_null($profile) && $profile instanceof Profile)
                {
                        $hidesensitive = $this->getHideSensitive($profile);
                }
                else
                {
                        $hidesensitive = false;
                }


		$attrs = array_merge($attrs, 
			array('data-hidesensitive' => ($hidesensitive ? "true" : "false"))
		);
	}


	function getHideSensitive($profile) {
		$c = Cache::instance();

		/*
		if (!empty($c)) {
			$hidesensitive = $c->get(Cache::key('profile:hide_sensitive:'.$profile->id));
			if (is_numeric($hidesensitive)) {
				return (boolean) $hidesensitive;
			}
			else return FALSE;
		}
		*/

		$hidesensitive = $profile->getPref('MoonMan', 'hide_sensitive', '0');

		if (!empty($c)) {
			//not using it yet.
			$c->set(Cache::key('profile:hide_sensitive:'.$profile->id), $hidesensitive);
		}

		//common_log(LOG_DEBUG, "SENSITIVECONTENT hidesensitive? id " . $profile->id . " value " . (boolean)$hidesensitive );

		if (is_null($hidesensitive)) {
			return FALSE;
		} else
		if (is_numeric($hidesensitive)) {
			return (boolean) $hidesensitive;
		}
		else return FALSE;
	}

}
