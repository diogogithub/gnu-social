# Attachments, Files and Thumbnails
An attachment in GNU social can represent both file or an url.

## Files

### Storage
Not every information should be stored in the database. Large blobs of data usually
find their space in storage. The two most common file abstractions you will find in
GNU social are `App\Util\TemporaryFile` and
`Symfony\Component\HttpFoundation\File\UploadedFile`.

The `UploadedFile` comes from Symfony and you'll find it when
working with forms that have file upload as inputs. The
`TemporaryFile` is how GNU social handles/represents any file that isn't
in a permanent state, i.e., not yet ready to be moved to storage.

So, as with the URL, the `Attachment` entity won't store
the information, only link to it.

#### Example
Here's how the `ImageEncoder` plugin creates a temporary file to manipulate an
image in a transaction fashion before committing its changes:

```php
// TemporaryFile handles deleting the file if some error occurs
$temp = new TemporaryFile(['prefix' => 'image', 'suffix' => $extension]);

$image  = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
$width  = Common::clamp($image->width, 0, Common::config('attachments', 'max_width'));
$height = Common::clamp($image->height, 0, Common::config('attachments', 'max_height'));
$image  = $image->crop(0, 0, $width, $height);
$image->writeToFile($temp->getRealPath());

// Replace original file with the sanitized one
$temp->commit($file->getRealPath());
```

Note how we:
1. created a temporary file `$temp`,
2. then write the in-memory `$image` manipulation of `$file` to storage in `$temp`
3. and only then commit the changes in `$temp` to `$file`'s location.

If anything failed in 2 we would risk corrupting the input `$file`. In this case,
for performance's sake, most of the manipulation happens in memory. But it's
obvious that `TemporaryFile` can also be very useful for eventual in-storage
manipulations.

## Return a file via HTTP
Okay, it's fun that you can save files. But it isn't very
useful if you can't show the amazing changes or files you
generated to the client. For that, GNU social has
`App\Core\GSFile`.

### Example

```php
public function avatar_view(Request $request, int $gsactor_id)
{
    $res = \Component\Avatar\Avatar::getAvatarFileInfo($gsactor_id);
    return M::sendFile($res['file_path'], $res['mimetype'], $res['title']);
}
```

Simple enough.

### Storing a reference in database
Finally, you need a way to refer to previous files.
GNU social calls that representation of `App\Entity\Attachment`.
If a note refers to an `Attachment` then you can link them
using the entity `AttachmentToNote`. 

> **Important:** Unless your plugin has its own file space,
> the core hashes the files and reuses `Attachment`s.
> Therefore, if you're deleting a file, you must ensure
> it is not being used somewhere you're not considering
> (like a note). You should only delete the file
> from storage when there are no notes linked with it in
> AttachmentToNote.

Call the functions `Attachment::validateAndStoreFileAsAttachment`
and `Attachment::validateAndStoreURLAsAttachment`.

## Thumbnails

Both files and urls can have an `AttachmentThumbnail`.
You can have an `AttachmentThumbnail` for every `Attachment`.
You can only have an `AttachmentThumbnail` if you have an
attachment first.
Read a plugin such as `ImageEncoder` to understand how thumbnails
can be generated from files. And `Embed` to understand how to generate
them from URLs.

The controller asking for them is the `App\Controller\Attachment::attachment_thumbnail` with
a call to `App\Entity\AttachmentThumbnail::getOrCreate()`.

## Trade-offs between decoupling and complexity

This kind of questions are deepened in our [wiki](https://agile.gnusocial.rocks/doku.php?id=attachment).
Despite that, in this case it is relevant enough to walk
a little through in the documentation. You'll note that
the Attachment entity has fairly specific fields such
as `remote_url` and `width`. Maybe for an Attachment
you could use the width field for the cover image of a
song, or not and just leave it null. And for a song
preview you could use width for duration and leave `height`
as null. The point is, we could have the entities
ImageAttachment and an ImageAttachmentThumbnail being
created by the ImageEncoder plugin and move these
specificities to the plugin. But the end code would
require far more database requests, become heavier,
and become harder to read. And maybe we're wasting a
bit more space (maybe!). but it's far from significant.