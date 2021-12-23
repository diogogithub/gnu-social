<?php

declare(strict_types = 1);

namespace Component\Tag\Form;

use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity as E;
use App\Util\Form\ArrayTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

abstract class SelfTagsForm
{
    /**
     * @param E\ActorTag[]|E\ActorTagBlock[]|E\NoteTagBlock[] $tags
     *
     * @return array [Form (add), ?Form (existing)]
     */
    public static function handleTags(
        Request $request,
        array $tags,
        callable $handle_new,
        callable $handle_existing,
        string $remove_label,
        string $add_label,
    ): array {
        $form_definition = [];
        foreach ($tags as $tag) {
            $canon             = $tag->getCanonical();
            $form_definition[] = ["{$canon}:old-tag", TextType::class, ['data' => '#' . $tag->getTag(), 'label' => ' ', 'disabled' => true]];
            $form_definition[] = ["{$canon}:toggle-canon", SubmitType::class, ['attr' => ['data' => $tag->getUseCanonical()], 'label' => $tag->getUseCanonical() ? _m('Set non-canonical') : _m('Set canonical')]];
            $form_definition[] = [$existing_form_name = "{$canon}:remove", SubmitType::class, ['label' => $remove_label]];
        }

        $existing_form = !empty($form_definition) ? Form::create($form_definition) : null;

        $add_form = Form::create([
            ['new-tags', TextType::class, ['label' => ' ', 'data' => [], 'required' => false, 'help' => _m('Tags for yourself (letters, numbers, -, ., and _), comma- or space-separated.'), 'transformer' => ArrayTransformer::class]],
            ['new-tags-use-canon', CheckboxType::class, ['label' => _m('Use canonical'), 'help' => _m('Assume this tag is the same as similar tags'), 'required' => false, 'data' => true]],
            [$add_form_name = 'new-tags-add', SubmitType::class, ['label' => $add_label]],
        ]);

        if ($request->getMethod() === 'POST' && $request->request->has($add_form_name)) {
            $add_form->handleRequest($request);
            if ($add_form->isSubmitted() && $add_form->isValid()) {
                $handle_new($add_form);
            }
        }

        if (!\is_null($existing_form) && $request->getMethod() === 'POST' && $request->request->has($existing_form_name ?? '')) {
            $existing_form->handleRequest($request);
            if ($existing_form->isSubmitted() && $existing_form->isValid()) {
                $handle_existing($existing_form, $form_definition);
            }
        }

        return [$add_form, $existing_form];
    }
}
