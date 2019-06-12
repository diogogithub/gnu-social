<?php

if (!defined('GNUSOCIAL')) { exit(1); }

class SubscriptionListItem extends ProfileListItem
{
    /** Owner of this list */
    var $owner = null;

    // FIXME: TagSubs plugin sends a TagSub here, but should send a Profile and handle TagSub specifics itself?
    function __construct($target, $owner, HTMLOutputter $action)
    {
        if ($owner instanceof Profile) {
            parent::__construct($target, $action, $owner);
        } else {
            parent::__construct($target, $action);
        }

        $this->owner = $owner;
    }

    function showProfile()
    {
        $this->startProfile();
        $this->showAvatar($this->profile);
        $this->showNickname();
        $this->showFullName();
        $this->showLocation();
        $this->showHomepage();
        $this->showBio();
        // Relevant portion!
        $this->showTags();
        if ($this->isOwn()) {
            $this->showOwnerControls();
        }
        $this->endProfile();
    }

    function showOwnerControls()
    {
        // pass
    }

    function isOwn()
    {
        $user = common_current_user();
        return (!empty($user) && ($this->owner->id == $user->id));
    }
}
