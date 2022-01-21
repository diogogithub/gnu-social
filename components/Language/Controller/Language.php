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

namespace Component\Language\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Form\FormFields;
use Component\Language\Entity\ActorLanguage;
use Component\Language\Entity\Language as LangEntity;
use Functional as F;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

class Language extends Controller
{
    /**
     * @throws NoLoggedInUser
     * @throws RedirectException
     * @throws ServerException
     */
    public function settings(Request $request): FormInterface
    {
        $user = Common::ensureLoggedIn();
        // TODO Add support missing settings

        $form = Form::create([
            FormFields::language($user->getActor(), context_actor: null, label: _m('Languages'), help: _m('The languages you understand, so you can see primarily content in those'), multiple: true, required: false, use_short_display: false),
            ['save_languages', SubmitType::class, ['label' => _m('Proceed to order selected languages')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (!\is_null($data['languages'])) {
                $selected_langs = DB::findBy('language', ['locale' => $data['languages']]);
                $existing_langs = DB::dql(
                    'select l from language l join actor_language al with l.id = al.language_id where al.actor_id = :actor_id',
                    ['actor_id' => $user->getId()],
                );

                $new_langs      = array_udiff($selected_langs, $existing_langs, fn ($l, $r) => $l->getId() <=> $r->getId());
                $removing_langs = array_udiff($existing_langs, $selected_langs, fn ($l, $r) => $l->getId() <=> $r->getId());
                foreach ($new_langs as $l) {
                    DB::persist(ActorLanguage::create(['actor_id' => $user->getId(), 'language_id' => $l->getId(), 'ordering' => 0]));
                }

                if (!empty($removing_langs)) {
                    $actor_langs_to_remove = DB::findBy('actor_language', ['actor_id' => $user->getId(), 'language_id' => F\map($removing_langs, fn ($l) => $l->getId())]);
                    foreach ($actor_langs_to_remove as $lang) {
                        DB::remove($lang);
                    }
                }

                Cache::delete(ActorLanguage::cacheKeys($user)['actor-langs']);
                ActorLanguage::normalizeOrdering($user); // In case the user doesn't submit the other page
                DB::flush();
                unset($data['languages']);

                throw new RedirectException('settings_sort_languages', ['_fragment' => null]); // TODO doesn't clear fragment
            }
        }
        return $form;
    }

    /**
     * Controller for defining the ordering of a users' languages
     *
     * @throws NoLoggedInUser
     * @throws RedirectException
     * @throws ServerException
     */
    public function sortLanguages(Request $request): array
    {
        $user = Common::ensureLoggedIn();

        $langs = DB::dql('select l.locale, l.long_display, al.ordering from language l join actor_language al with l.id = al.language_id where al.actor_id = :id order by al.ordering ASC', ['id' => $user->getId()]);

        $form_entries = [];
        foreach ($langs as $l) {
            $form_entries[] = [$l['locale'], IntegerType::class, ['label' => _m($l['long_display']), 'data' => $l['ordering']]];
        }

        $form_entries[] = ['save_language_order', SubmitType::class, []];
        $form_entries[] = ['go_back', SubmitType::class, ['label' => _m('Return to settings page')]];
        $form           = Form::create($form_entries);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $button */
            $button  = $form->get('go_back');
            $go_back = $button->isClicked();
            $data    = $form->getData();
            asort($data); // Sort by the order value
            $data = array_keys($data); // This keeps the order and gives us a unique number for each
            foreach ($data as $order => $locale) {
                $lang       = LangEntity::getByLocale($locale);
                $actor_lang = DB::getReference('actor_language', ['actor_id' => $user->getId(), 'language_id' => $lang->getId()]);
                $actor_lang->setOrdering($order + 1);
            }
            DB::flush();
            if (!$go_back) {
                // Stay on same page, but force update and prevent resubmission
                throw new RedirectException('settings_sort_languages');
            } else {
                throw new RedirectException('settings', ['open' => 'account', '_fragment' => 'save_account_info_languages']);
            }
        }

        return [
            '_template' => 'language/sort.html.twig',
            'form'      => $form->createView(),
        ];
    }
}
