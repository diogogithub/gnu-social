Templates
=========

GNU social uses the [Twig template engine](https://twig.symfony.com/).
When you handle a UI-related event, you add your own twig snippets either with
`App\Util\Formatting::twigRenderFile` or `App\Util\Formatting::twigRenderString`.

Example
-------

```php
public function onAppendRightPanelBlock(array $vars, Request $request, array &$res): bool
{
    if ($vars['path'] == 'attachment_show') {
        $related_notes = DB::dql('select n from attachment_to_note an ' .
    'join note n with n.id = an.note_id ' .
    'where an.attachment_id = :attachment_id', ['attachment_id' => $vars['vars']['attachment_id']]);
        $related_tags = DB::dql('select distinct t.tag ' .
    'from attachment_to_note an join note_tag t with an.note_id = t.note_id ' .
    'where an.attachment_id = :attachment_id', ['attachment_id' => $vars['vars']['attachment_id']]);
        $res[] = Formatting::twigRenderFile('attachmentShowRelated/attachmentRelatedNotes.html.twig', ['related_notes' => $related_notes]);
        $res[] = Formatting::twigRenderFile('attachmentShowRelated/attachmentRelatedTags.html.twig', ['related_tags' => $related_tags]);
    }
    return Event::next;
}
```

Regarding using the Twig language, you can refer to
[Twig Documentation](https://twig.symfony.com/doc/3.x/templates.html).
