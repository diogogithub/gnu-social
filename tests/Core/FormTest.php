<?php

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

namespace App\Tests\Core;

use App\Core\DB\DB;
use App\Core\Form;
use App\Entity\GSActor;
use App\Util\Form\ArrayTransformer;
use App\Util\GNUsocialTestCase;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form as SymfForm;
use Symfony\Component\HttpFoundation\Request;

class FormTest extends GNUsocialTestCase
{
    public function testCreate()
    {
        parent::bootKernel();
        $form = Form::create($form_array = [
            ['content',     TextareaType::class, ['label' => ' ', 'data' => '', 'attr' => ['placeholder' => 'placeholder']]],
            ['array_trans', TextareaType::class, ['data' => ['foo', 'bar'], 'transformer' => ArrayTransformer::class]],
            ['testpost',        SubmitType::class,   ['label' => 'Post']],
        ]);
        static::assertSame(get_class($form), 'Symfony\\Component\\Form\\Form');
        foreach ($form as $name => $f) {
            if ($name == 'testpost') {
                static::assertSame(get_class($f), 'Symfony\Component\Form\SubmitButton');
            } else {
                static::assertSame(get_class($f), 'Symfony\Component\Form\Form');
            }

            $config       = $f->getConfig();
            $form_options = $config->getOptions();

            $form_class = $config->getType()->getInnerType();

            $found = false;
            foreach ($form_array as [$array_name, $array_class, $options]) {
                if ($name === $array_name) {
                    $found = true;
                    static::assertSame(get_class($form_class), $array_class);
                    foreach (['label', 'attr', 'data'] as $field) {
                        if (isset($options[$field])) {
                            static::assertSame($form_options[$field], $options[$field]);
                        }
                    }
                    break;
                }
            }
            static::assertTrue($found);

            static::assertSame(get_class($f->getParent()), 'Symfony\\Component\\Form\\Form');
        }
        static::assertTrue(Form::isRequired($form_array, 'content'));
    }

    /**
     * Test creating a form with default values pulled from an existing object. Can be used in conjunction with `Form::hanlde` to update said object
     */
    public function testCreateUpdateObject()
    {
        $nick = 'form_testing_new_user';
        $user = GSActor::create(['nickname' => $nick, 'normalized_nickname' => $nick]);
        $form = Form::create([
            ['nickname',            TextareaType::class, []],
            ['normalized_nickname', TextareaType::class, []],
            ['testpost',                SubmitType::class,   []],
        ], target: $user);
        $options = $form['nickname']->getConfig()->getOptions();
        static::assertSame($nick, $options['data']);
    }

    public function testHandle()
    {
        parent::bootKernel();
        $data         = ['fullname' => 'Full Name', 'homepage' => 'gnu.org'];
        $mock_request = static::createMock(Request::class);
        $mock_form    = static::createMock(SymfForm::class);
        $mock_form->method('handleRequest');
        $mock_form->method('isSubmitted')->willReturn(true);
        $mock_form->method('isValid')->willReturn(true);
        $mock_form->method('getData')->willReturn($data);
        $ret = Form::handle(form_definition: [/* not normal usage */], request: $mock_request, target: null, extra_args: [], extra_step: null, create_args: [], testing_only_form: $mock_form);
        static::assertSame($data, $ret);

        $user = GSActor::create(['nickname' => 'form_testing_new_user', 'normalized_nickname' => 'form_testing_new_user']);
        DB::persist($user);
        $ret = Form::handle(form_definition: [/* not normal usage */], request: $mock_request, target: $user, extra_args: [], extra_step: null, create_args: [], testing_only_form: $mock_form);
        static::assertSame($mock_form, $ret);
        static::assertSame($data['fullname'], $user->getFullname());
        static::assertSame($data['homepage'], $user->getHomepage());
    }
}
