<?php

declare(strict_types = 1);

// {{{ License
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
// }}}
/**
 * Collections for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Collection\Util;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Actor;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

trait MetaCollectionTrait
{
    //protected string $slug        = 'collection';
    //protected string $plural_slug = 'collections';

    /**
     * create a collection owned by Actor $owner.
     *
     * @param Actor  $owner The collection's owner
     * @param array  $vars  Page vars sent by AppendRightPanelBlock event
     * @param string $name  Collection's name
     */
    abstract protected function createCollection(Actor $owner, array $vars, string $name);
    /**
     * remove item from collections.
     *
     * @param Actor $owner       Current user
     * @param array $vars        Page vars sent by AppendRightPanelBlock event
     * @param array $items       Array of collections's ids to remove the current item from
     * @param array $collections List of ids of collections owned by $owner
     */
    abstract protected function removeItem(Actor $owner, array $vars, array $items, array $collections);
    /**
     * add item to collections.
     *
     * @param Actor $owner       Current user
     * @param array $vars        Page vars sent by AppendRightPanelBlock event
     * @param array $items       Array of collections's ids to add the current item to
     * @param array $collections List of ids of collections owned by $owner
     */
    abstract protected function addItem(Actor $owner, array $vars, array $items, array $collections);

    /**
     * Check the route to determine whether the widget should be added
     */
    abstract protected function shouldAddToRightPanel(Actor $user, $vars, Request $request): bool;
    /**
     * Get array of collections's owned by $actor
     *
     * @param Actor  $owner    Collection's owner
     * @param ?array $vars     Page vars sent by AppendRightPanelBlock event
     * @param bool   $ids_only if true, the function must return only the primary key or each collections
     */
    abstract protected function getCollectionsBy(Actor $owner, ?array $vars = null, bool $ids_only = false): array;

    /**
     * Append Collections widget to the right panel.
     * It's compose of two forms: one to select collections to add
     * the current item to, and another to create a new collection.
     */
    public function onAppendRightPanelBlock(Request $request, $vars, &$res): bool
    {
        $user = Common::actor();
        if (\is_null($user)) {
            return Event::next;
        }
        if (!$this->shouldAddToRightPanel($user, $vars, $request)) {
            return Event::next;
        }
        $collections = $this->getCollectionsBy($user);

        // form: add to collection
        $choices = [];
        foreach ($collections as $col) {
            $choices[$col->getName()] = $col->getId();
        }

        $collections = array_map(fn ($x) => $x->getId(), $collections);

        $already_selected = $this->getCollectionsBy($user, $vars, true);
        $add_form         = Form::create([
            ['collections', ChoiceType::class, [
                'choices'     => $choices,
                'multiple'    => true,
                'required'    => false,
                'choice_attr' => function ($id) use ($already_selected) {
                    if (\in_array($id, $already_selected)) {
                        return ['selected' => 'selected'];
                    }
                    return [];
                },
            ]],
            ['add', SubmitType::class, [
                'label' => _m('Add to ' . $this->plural_slug),
                'attr'  => [
                    'title' => _m('Add to ' . $this->plural_slug),
                ],
            ]],
        ]);
        $add_form->handleRequest($request);
        if ($add_form->isSubmitted() && $add_form->isValid()) {
            $selected = $add_form->getData()['collections'];
            $removed  = array_filter($already_selected, fn ($x) => !\in_array($x, $selected));
            $added    = array_filter($selected, fn ($x) => !\in_array($x, $already_selected));
            if (\count($removed) > 0) {
                $this->removeItem($user, $vars, $removed, $collections);
            }
            if (\count($added) > 0) {
                $this->addItem($user, $vars, $added, $collections);
            }
            DB::flush();
            throw new RedirectException();
        }

        // form: add to new collection
        $create_form = Form::create([
            ['name', TextType::class, [
                'label' => _m('Add to a new ' . $this->slug),
                'attr'  => [
                    'placeholder' => _m('New ' . $this->slug . ' name'),
                    'required'    => 'required',
                ],
                'data' => '',
            ]],
            ['create', SubmitType::class, [
                'label' => _m('Create a new ' . $this->slug),
                'attr'  => [
                    'title' => _m('Create a new ' . $this->slug),
                ],
            ]],
        ]);
        $create_form->handleRequest($request);
        if ($create_form->isSubmitted() && $create_form->isValid()) {
            $name = $create_form->getData()['name'];
            $this->createCollection($user, $vars, $name);
            DB::flush();
            throw new RedirectException();
        }

        $res[] = Formatting::twigRenderFile(
            'collection/widget_add_to.html.twig',
            [
                'ctitle'          => _m('Add to ' . $this->plural_slug),
                'user'            => $user,
                'has_collections' => \count($collections) > 0,
                'add_form'        => $add_form->createView(),
                'create_form'     => $create_form->createView(),
            ],
        );
        return Event::next;
    }

    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'components/Collection/assets/css/widget.css';
        $styles[] = 'components/Collection/assets/css/pages.css';
        return Event::next;
    }
}
