TheFreeNetwork allows GNU social to handle more than one Federation protocol simultaneously.

Making this possible essentially consists in not allowing duplication of remote profiles in the profile table,
and to ensure that each profile is handled by one and only one federation protocol at a time.

Each newly added federation protocol **must** support all the already supported functionalities by the other federation
protocols, otherwise this module will be moving between FullFeaturedFederationProtocolProfile and LackyFederationProtocolProfile all the time.

You **must** feed this Module with the list of preferences for each federation protocol, more on that in the following section.

Settings
========
You can change these settings in `config.php` with `$config['TheFreeNetworkModule'][{setting name}] = {new setting value};`.

Default values in parenthesis.

protocols (`['ActivityPub' => 'Activitypub_profile', 'OStatus' => 'Ostatus_profile']`):

This array follows the following structure:

`['{PluginName}' => '{Actor_representation_class_name}'`, the latter is the class used to represent the remote profile in
the context of the federation plugin.

N.B.: Higher Priority/Preference is given to the first entry. I.e., the default value gives preference to ActivityPub over OStatus.

Scripts
=======

`./fix_duplicates.php` : Run this script if you have duplicated profiles due to federation issues.
For example, if both ActivityPub and OStatus created different profile entries for the same remote actor. That's something
that shouldn't thank to this module, but if something goes very wrong, there's this script.
