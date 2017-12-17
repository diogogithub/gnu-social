<?php

if (!defined('GNUSOCIAL')) { exit(1); }

class SensitiveContentSettingsAction extends SettingsAction
{
	function title()
	{
		return _m('Sensitive content settings');
	}

	function getInstructions()
	{
		return _m('Set preferences for display of "sensitive" content');
	}

	function showContent()
	{

		$user = $this->scoped->getUser();

		$this->elementStart('form', array('method' => 'post',
			'id' => 'sensitivecontent',
			'class' => 'form_settings',
			'action' => common_local_url('sensitivecontentsettings')));

		$this->elementStart('fieldset');
		$this->hidden('token', common_session_token());
		$this->elementStart('ul', 'form_data');

		$this->elementStart('li');
			$this->checkbox('hidesensitive', _('Hide attachments in posts hashtagged #NSFW'),
				($this->arg('hidesensitive')) ?
				$this->boolean('hidesensitive') : $this->scoped->getPref('MoonMan','hide_sensitive',0));
		$this->elementEnd('li');


		$this->elementEnd('ul');
		$this->submit('save', _m('BUTTON','Save'));

		$this->elementEnd('fieldset');
		$this->elementEnd('form');
	}

	function doPost()
	{
		$hidesensitive = $this->booleanintstring('hidesensitive');
		$this->scoped->setPref('MoonMan','hide_sensitive', $hidesensitive);
		return _('Settings saved.');
	}
}
