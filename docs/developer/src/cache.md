Cache
=====

In the Database chapter you've learned how
GNU social allows you to store data in the
database. Depending on your server's
specification, the database can be a bottleneck. 
To mitigate that, you can make use of an in-memory
data structure storage to [cache](https://en.wikipedia.org/wiki/Cache_(computing))
previous database requests. Using it is a great way of
making GNU social run quicker. GNU social supports many
adapters to different storages.

Although different cache adapters provide
different functionalities that could be nice
to take advantage of, we had to limit our cache
interface to the basic avaiable in all of them.
I.e., _store_ and _delete_ operations.

Store
-----

```php
/**
 * Get the cached avatar file info associated with the given Actor id
 *
 * Returns the avatar file's hash, mimetype, title and path.
 * Ensures exactly one cached value exists
 */
public static function getAvatarFileInfo(int $gsactor_id): array
{
    try {
        $res = GSFile::error(NoAvatarException::class,
            $gsactor_id,
            Cache::get("avatar-file-info-{$gsactor_id}",
                function () use ($gsactor_id) {
                    return DB::dql('select f.file_hash, f.mimetype, f.title ' .
                        'from Component\Attachment\Entity\Attachment f ' .
                        'join App\Entity\Avatar a with f.id = a.attachment_id ' .
                        'where a.gsactor_id = :gsactor_id',
                        ['gsactor_id' => $gsactor_id]);
                }));
        $res['file_path'] = \App\Entity\Avatar::getFilePathStatic($res['file_hash']);
        return $res;
    } catch (Exception $e) {
        $filepath = INSTALLDIR . '/public/assets/default-avatar.svg';
        return ['file_path' => $filepath, 'mimetype' => 'image/svg+xml', 'title' => null];
    }
}
```

Delete
------

```php
Cache::delete('avatar-file-info-' . $gsactor_id);
```