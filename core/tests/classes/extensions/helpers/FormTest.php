<?php
/**
 * KumbiaPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Test
 * @package    Form
 *
 * @copyright  Copyright (c) 2005 - 2023 KumbiaPHP Team (http://www.kumbiaphp.com)
 * @license    https://github.com/KumbiaPHP/KumbiaPHP/blob/master/LICENSE   New BSD License
 */

use \Mockery as m;

defined('APP_CHARSET') || define('APP_CHARSET', 'UTF-8');

/**
 * @category Test
 * @package  Form
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class FormTest extends PHPUnit\Framework\TestCase
{
    /** @var \Mockery\MockInterface */
    protected $viewMock;

    protected function setUp(): void
    {
        $_POST = [];
        // View is not autoloaded in the test environment; mock it for all tests.
        // Default behaviour: getVar() returns null (no model data).
        $this->viewMock = m::mock('alias:View');
        $this->viewMock->shouldReceive('getVar')->andReturnNull()->byDefault();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        m::close();
    }

    // =========================================================================
    // selectValue
    // =========================================================================

    public function selectValueProvider(): array
    {
        $obj       = new stdClass();
        $obj->id   = 42;
        $objXss    = new stdClass();
        $objXss->id = '<script>';

        return [
            'scalar key'          => ['plain string',  'key1',      'id', 'key1'],
            'integer key'         => ['value',          3,           'id', '3'],
            'object with id prop' => [$obj,            'ignored',   'id', '42'],
            'xss in scalar key'   => ['value',          '<script>',  'id', '&lt;script&gt;'],
            'xss in object prop'  => [$objXss,         'key',       'id', '&lt;script&gt;'],
        ];
    }

    /** @dataProvider selectValueProvider */
    public function testSelectValue($item, $key, string $id, string $expected): void
    {
        $this->assertSame($expected, Form::selectValue($item, $key, $id));
    }

    // =========================================================================
    // selectedValue
    // =========================================================================

    public function testSelectedValueMatchString(): void
    {
        $this->assertSame('selected="selected"', Form::selectedValue('foo', 'foo'));
    }

    public function testSelectedValueNoMatch(): void
    {
        $this->assertSame('', Form::selectedValue('foo', 'bar'));
    }

    public function testSelectedValueArrayMatch(): void
    {
        $this->assertSame('selected="selected"', Form::selectedValue(['a', 'b', 'c'], 'b'));
    }

    public function testSelectedValueArrayNoMatch(): void
    {
        $this->assertSame('', Form::selectedValue(['a', 'b'], 'c'));
    }

    public function testSelectedValueUsesStrictComparison(): void
    {
        // Must not match loose-equal pairs
        $this->assertSame('', Form::selectedValue('0', false));
        $this->assertSame('', Form::selectedValue(1, '1'));
        $this->assertSame('', Form::selectedValue('', null));
    }

    // =========================================================================
    // selectShow
    // =========================================================================

    public function testSelectShowString(): void
    {
        $this->assertSame('Hello', Form::selectShow('Hello', ''));
    }

    public function testSelectShowInteger(): void
    {
        $this->assertSame('42', Form::selectShow(42, ''));
    }

    public function testSelectShowObjectWithProperty(): void
    {
        $obj        = new stdClass();
        $obj->label = 'World';
        $this->assertSame('World', Form::selectShow($obj, 'label'));
    }

    public function testSelectShowObjectWithToStringAndEmptyShow(): void
    {
        // When $show is empty the code calls (string)$item, so the object needs __toString
        $obj = new class {
            public function __toString(): string
            {
                return 'rendered';
            }
        };
        $this->assertSame('rendered', Form::selectShow($obj, ''));
    }

    public function testSelectShowEscapesHtml(): void
    {
        $this->assertSame('&lt;b&gt;bold&lt;/b&gt;', Form::selectShow('<b>bold</b>', ''));
    }

    // =========================================================================
    // button
    // =========================================================================

    public function testButtonDefaultType(): void
    {
        $html = Form::button('Click');
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('>Click</button>', $html);
    }

    public function testButtonSubmitType(): void
    {
        $html = Form::button('Go', '', 'submit');
        $this->assertStringContainsString('type="submit"', $html);
    }

    public function testButtonResetType(): void
    {
        $html = Form::button('Clear', '', 'reset');
        $this->assertStringContainsString('type="reset"', $html);
    }

    public function testButtonWithValue(): void
    {
        $html = Form::button('Save', '', 'button', 'save_val');
        $this->assertStringContainsString('value="save_val"', $html);
    }

    public function testButtonOmitsValueAttributeWhenNull(): void
    {
        $html = Form::button('Click');
        $this->assertStringNotContainsString('value=', $html);
    }

    public function testButtonWithAttrsString(): void
    {
        $html = Form::button('Click', 'class="btn btn-primary"');
        $this->assertStringContainsString('class="btn btn-primary"', $html);
    }

    public function testButtonWithAttrsArray(): void
    {
        $html = Form::button('Click', ['class' => 'btn', 'id' => 'myBtn']);
        $this->assertStringContainsString('class="btn"', $html);
        $this->assertStringContainsString('id="myBtn"', $html);
    }

    // =========================================================================
    // label
    // =========================================================================

    public function testLabel(): void
    {
        $this->assertSame('<label for="user_name" >Username</label>', Form::label('Username', 'user_name'));
    }

    public function testLabelWithAttrs(): void
    {
        $html = Form::label('Email', 'email', ['class' => 'required']);
        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('class="required"', $html);
        $this->assertStringContainsString('>Email</label>', $html);
    }

    // =========================================================================
    // submit / reset
    // =========================================================================

    public function testSubmit(): void
    {
        $html = Form::submit('Send');
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('>Send</button>', $html);
    }

    public function testReset(): void
    {
        $html = Form::reset('Clear');
        $this->assertStringContainsString('type="reset"', $html);
        $this->assertStringContainsString('>Clear</button>', $html);
    }

    // =========================================================================
    // open / openMultipart / close
    // =========================================================================

    public function testOpenWithExplicitAction(): void
    {
        $html = Form::open('users/create');
        $this->assertStringContainsString('action="' . PUBLIC_PATH . 'users/create"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringStartsWith('<form', $html);
    }

    public function testOpenWithCustomMethod(): void
    {
        $html = Form::open('users/search', 'get');
        $this->assertStringContainsString('method="get"', $html);
    }

    public function testOpenWithoutActionUsesRouterRoute(): void
    {
        $routerMock = m::mock('alias:Router');
        $routerMock->shouldReceive('get')->with('route')->andReturn('/users/index');

        $html = Form::open();
        $this->assertStringContainsString('action="', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testOpenWithAttrsArray(): void
    {
        $html = Form::open('contact', 'post', ['id' => 'myForm', 'class' => 'form-inline']);
        $this->assertStringContainsString('id="myForm"', $html);
        $this->assertStringContainsString('class="form-inline"', $html);
    }

    public function testOpenMultipartSetsEnctype(): void
    {
        $html = Form::openMultipart('upload');
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testOpenMultipartWithAttrsArray(): void
    {
        $html = Form::openMultipart('upload', ['class' => 'upload-form']);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('class="upload-form"', $html);
    }

    public function testCloseReturnsTag(): void
    {
        $this->assertSame('</form>', Form::close());
    }

    public function testCloseResetsMultipartFlag(): void
    {
        Form::openMultipart('upload');
        Form::close();

        // After close(), file() must warn again via Flash::error()
        $flashMock = m::mock('alias:Flash');
        $flashMock->shouldReceive('error')->once()->with(m::type('string'));

        $html = Form::file('document');
        $this->assertStringContainsString('type="file"', $html);
    }

    // =========================================================================
    // text
    // =========================================================================

    public function testTextBasic(): void
    {
        $html = Form::text('username');
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('id="username"', $html);
    }

    public function testTextWithExplicitValue(): void
    {
        $html = Form::text('username', '', 'john_doe');
        $this->assertStringContainsString('value="john_doe"', $html);
    }

    public function testTextFromPost(): void
    {
        $_POST['username'] = 'from_post';
        $html = Form::text('username');
        $this->assertStringContainsString('value="from_post"', $html);
    }

    public function testTextXssInExplicitValue(): void
    {
        $html = Form::text('name', '', '<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testTextXssInPost(): void
    {
        $_POST['name'] = '<img src=x onerror=alert(1)>';
        $html = Form::text('name');
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testTextFormDotFieldPattern(): void
    {
        $html = Form::text('user.name');
        $this->assertStringContainsString('id="user_name"', $html);
        $this->assertStringContainsString('name="user[name]"', $html);
    }

    // =========================================================================
    // hidden
    // =========================================================================

    public function testHiddenWithValue(): void
    {
        $html = Form::hidden('token', '', 'abc123');
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="token"', $html);
        $this->assertStringContainsString('value="abc123"', $html);
    }

    public function testHiddenFromPost(): void
    {
        $_POST['token'] = 'post_token';
        $html = Form::hidden('token');
        $this->assertStringContainsString('value="post_token"', $html);
    }

    // =========================================================================
    // password / pass
    // =========================================================================

    public function testPassword(): void
    {
        $html = Form::password('pwd');
        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('name="pwd"', $html);
    }

    public function testPassIsDeprecatedAliasForPassword(): void
    {
        $this->assertSame(Form::password('pwd'), Form::pass('pwd'));
    }

    // =========================================================================
    // HTML5 input types
    // =========================================================================

    public function inputTypeProvider(): array
    {
        return [
            'date'           => ['date',           'date',           'birth_date'],
            'time'           => ['time',           'time',           'start_time'],
            'datetime-local' => ['datetime',       'datetime-local', 'event_at'],
            'number'         => ['number',         'number',         'quantity'],
            'url'            => ['url',            'url',            'website'],
            'email'          => ['email',          'email',          'contact_email'],
        ];
    }

    /** @dataProvider inputTypeProvider */
    public function testHtml5InputTypes(string $method, string $expectedType, string $field): void
    {
        $html = Form::$method($field);
        $this->assertStringContainsString('type="' . $expectedType . '"', $html);
        $this->assertStringContainsString('name="' . $field . '"', $html);
    }

    // =========================================================================
    // textarea
    // =========================================================================

    public function testTextareaBasic(): void
    {
        $html = Form::textarea('body');
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="body"', $html);
        $this->assertStringContainsString('</textarea>', $html);
    }

    public function testTextareaWithValue(): void
    {
        $html = Form::textarea('bio', '', 'Hello world');
        $this->assertStringContainsString('>Hello world</textarea>', $html);
    }

    public function testTextareaFromPost(): void
    {
        $_POST['bio'] = 'My bio text';
        $html = Form::textarea('bio');
        $this->assertStringContainsString('>My bio text</textarea>', $html);
    }

    public function testTextareaEscapesXss(): void
    {
        $html = Form::textarea('comment', '', '</textarea><script>xss</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;', $html);
    }

    // =========================================================================
    // check
    // =========================================================================

    public function testCheckUncheckedByDefault(): void
    {
        $html = Form::check('agree', '1');
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testCheckCheckedByArgument(): void
    {
        $html = Form::check('agree', '1', '', true);
        $this->assertStringContainsString('checked="checked"', $html);
    }

    public function testCheckCheckedFromPost(): void
    {
        $_POST['agree'] = '1';
        $html = Form::check('agree', '1');
        $this->assertStringContainsString('checked="checked"', $html);
    }

    public function testCheckUncheckedWhenPostValueDiffers(): void
    {
        $_POST['agree'] = '0';
        $html = Form::check('agree', '1');
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testCheckFormDotFieldPattern(): void
    {
        $html = Form::check('user.newsletter', '1');
        $this->assertStringContainsString('id="user_newsletter"', $html);
        $this->assertStringContainsString('name="user[newsletter]"', $html);
    }

    // =========================================================================
    // radio
    // =========================================================================

    public function testRadioUncheckedByDefault(): void
    {
        $html = Form::radio('color', 'red');
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('value="red"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testRadioCheckedByArgument(): void
    {
        $html = Form::radio('color', 'red', '', true);
        $this->assertStringContainsString('checked="checked"', $html);
    }

    public function testRadioIdIncrementsPerField(): void
    {
        $html1 = Form::radio('size', 'S');
        $html2 = Form::radio('size', 'M');
        $html3 = Form::radio('size', 'L');

        $this->assertStringContainsString('id="size0"', $html1);
        $this->assertStringContainsString('id="size1"', $html2);
        $this->assertStringContainsString('id="size2"', $html3);
    }

    public function testRadioCounterIsIndependentPerFieldName(): void
    {
        $colorFirst  = Form::radio('color', 'red');
        $sizeFirst   = Form::radio('size', 'S');
        $colorSecond = Form::radio('color', 'blue');

        $this->assertStringContainsString('id="color0"', $colorFirst);
        $this->assertStringContainsString('id="size0"', $sizeFirst);
        $this->assertStringContainsString('id="color1"', $colorSecond);
    }

    // =========================================================================
    // select
    // =========================================================================

    public function testSelectBasic(): void
    {
        $html = Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive']);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('id="status"', $html);
        $this->assertStringContainsString('<option value="active"', $html);
        $this->assertStringContainsString('>Active</option>', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testSelectWithBlankOption(): void
    {
        $html = Form::select('role', ['admin' => 'Admin'], '', null, 'Select role');
        $this->assertStringContainsString('<option value="">Select role</option>', $html);
    }

    public function testSelectWithBlankOptionEscapesHtml(): void
    {
        $html = Form::select('role', [], '', null, '<Choose>');
        $this->assertStringContainsString('<option value="">&lt;Choose&gt;</option>', $html);
    }

    public function testSelectPreselectsMatchingValue(): void
    {
        $html = Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive'], '', 'inactive');
        $this->assertMatchesRegularExpression('/<option value="inactive"[^>]*selected="selected"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="active"[^>]*selected="selected"/', $html);
    }

    public function testSelectMultipleSelected(): void
    {
        $html = Form::select('tags', ['php' => 'PHP', 'js' => 'JS', 'css' => 'CSS'], '', ['php', 'css']);
        $this->assertMatchesRegularExpression('/<option value="php"[^>]*selected="selected"/', $html);
        $this->assertMatchesRegularExpression('/<option value="css"[^>]*selected="selected"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="js"[^>]*selected="selected"/', $html);
    }

    public function testSelectWithObjectData(): void
    {
        $obj1         = new stdClass();
        $obj1->id     = 1;
        $obj1->label  = 'First';
        $obj2         = new stdClass();
        $obj2->id     = 2;
        $obj2->label  = 'Second';

        $html = Form::select('item', [$obj1, $obj2], '', null, '', 'id', 'label');
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('>First</option>', $html);
    }

    public function testSelectEscapesXssInOptionsAndValues(): void
    {
        $html = Form::select('field', ['<script>' => '<b>bold</b>']);
        // Value attribute must be escaped
        $this->assertStringNotContainsString('value="<script>"', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        // Display text must be escaped
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }

    public function testSelectFromPostPreselects(): void
    {
        $_POST['status'] = 'inactive';
        $html = Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive']);
        $this->assertMatchesRegularExpression('/<option value="inactive"[^>]*selected="selected"/', $html);
    }

    // =========================================================================
    // file
    // =========================================================================

    public function testFileRequiresMultipartForm(): void
    {
        $flashMock = m::mock('alias:Flash');
        $flashMock->shouldReceive('error')->once()->with(m::type('string'));

        $html = Form::file('document');
        $this->assertStringContainsString('type="file"', $html);
    }

    public function testFileOutputWithOpenMultipart(): void
    {
        Form::openMultipart('upload');
        $html = Form::file('avatar');
        Form::close();

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('name="avatar"', $html);
        $this->assertStringContainsString('id="avatar"', $html);
    }

    public function testFileWithAttrs(): void
    {
        Form::openMultipart('upload');
        $html = Form::file('photo', ['accept' => 'image/*']);
        Form::close();

        $this->assertStringContainsString('accept="image/*"', $html);
    }

    // =========================================================================
    // submitImage
    // =========================================================================

    public function testSubmitImage(): void
    {
        $html = Form::submitImage('btn.png');
        $this->assertStringContainsString('type="image"', $html);
        $this->assertStringContainsString('src="' . PUBLIC_PATH . 'img/btn.png"', $html);
    }

    // =========================================================================
    // Autoload from View model
    // =========================================================================

    public function testAutoloadScalarValueFromViewModel(): void
    {
        $this->viewMock->shouldReceive('getVar')
            ->with('title')
            ->andReturn('My Title');

        $html = Form::text('title');
        $this->assertStringContainsString('value="My Title"', $html);
    }

    public function testAutoloadNestedValueFromViewModelArray(): void
    {
        $this->viewMock->shouldReceive('getVar')
            ->with('user')
            ->andReturn(['name' => 'Jane']);

        $html = Form::text('user.name');
        $this->assertStringContainsString('value="Jane"', $html);
        $this->assertStringContainsString('id="user_name"', $html);
        $this->assertStringContainsString('name="user[name]"', $html);
    }

    public function testAutoloadNestedValueFromViewModelObject(): void
    {
        $obj       = new stdClass();
        $obj->name = 'John';
        $this->viewMock->shouldReceive('getVar')->with('user')->andReturn($obj);

        $html = Form::text('user.name');
        $this->assertStringContainsString('value="John"', $html);
    }

    public function testAutoloadEscapesXssFromViewModel(): void
    {
        $this->viewMock->shouldReceive('getVar')
            ->with('user')
            ->andReturn(['name' => '<script>alert(1)</script>']);

        $html = Form::text('user.name');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testPostDataOverridesViewModelValue(): void
    {
        $this->viewMock->shouldReceive('getVar')
            ->with('user')
            ->andReturn(['name' => 'Model value']);

        $_POST['user'] = ['name' => 'Post value'];
        $html = Form::text('user.name');
        $this->assertStringContainsString('value="Post value"', $html);
        $this->assertStringNotContainsString('Model value', $html);
    }
}
