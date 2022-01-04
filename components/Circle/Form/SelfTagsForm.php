<?php

declare(strict_types = 1);

namespace Component\Circle\Form;

use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Form\ArrayTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

abstract class SelfTagsForm
{
    /**
     * @return array [Form (add), ?Form (existing)]
     */
    public static function handleTags(
        Request $request,
        array $actor_self_tags,
        callable $handle_new,
        callable $handle_existing,
        string $remove_label,
        string $add_label,
    ): array {
        $form_definition = [];
        foreach ($actor_self_tags as $tag) {
            $tag               = $tag->getTag();
            $form_definition[] = ["{$tag}:old-tag", TextType::class, ['data' => $tag, 'label' => ' ', 'disabled' => true]];
            $form_definition[] = [$existing_form_name = "{$tag}:remove", SubmitType::class, ['label' => $remove_label]];
        }

        $existing_form = !empty($form_definition) ? Form::create($form_definition) : null;

        $add_form = Form::create([
            ['new-tags', TextType::class, ['label' => ' ', 'data' => [], 'required' => false, 'help' => _m('Tags for yourself (letters, numbers, -, ., and _), comma- or space-separated.'), 'transformer' => ArrayTransformer::class]],
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
