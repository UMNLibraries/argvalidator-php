#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'ArgValidator.php';

//error_reporting( E_STRICT );

class ArgValidatorTest extends UnitTestCase
{
    public function test_required()
    {
        $validated_args = ArgValidator::validate(
            array('foo' => 1,),
            array('foo' => array('is' => 'int',), 'bar' => array('is' => 'string', 'required' => false,),)
        );        
        $this->assertEqual($validated_args, array('foo' => 1,));

        $validated_args = ArgValidator::validate(
            array(),
            array('foo' => array('required' => false,), 'bar' => array('required' => false,),)
        );        
        // TODO: Is there a less verbose test method than assertEqual for this?
        $this->assertEqual($validated_args, array());

        $this->expectException();
        ArgValidator::validate(
            array('foo' => 1,),
            array('foo' => array('is' => 'int',), 'bar' => array('is' => 'string',),)
        );        
    }

    function test_positional_args()
    {
        $original_args = array(1, 'baz');

        $validation_specs = array(array('is' => 'int',), array('is' => 'string',));

        $validated_args = ArgValidator::validate(
            $original_args,
            $validation_specs
        );        
        $this->assertEqual($validated_args, $original_args);

        $this->expectException();
        ArgValidator::validate(
            array(1), // missing second arg
            $validation_specs
        );        
    }

    function test_single_arg()
    {
        $original_arg = 'foo';

        $validation_spec = array('is' => 'string');

        $validated_args = ArgValidator::validate(
            $original_arg,
            $validation_spec
        );        
        $this->assertEqual($validated_args, array($original_arg));
    }

    public function test_is()
    {
        $original_args = array('foo' => 1, 'bar' => 'baz',);

        // Another reason this class is helpful: PHP doesn't allow 'string'
        // or 'int' type hinting. WTF?
        $validation_specs = array(
            'foo' => array('is' => 'int',),
            'bar' => array('is' => 'string',),
        );

        $validated_args = ArgValidator::validate(
            $original_args, $validation_specs
        );
        $this->assertEqual($validated_args, $original_args);

        $this->expectException();
        ArgValidator::validate(
            array('foo' => 'manchu', 'bar' => 'baz',),
            $validation_specs
        );        
    }

    public function test_regex()
    {
        $original_args = array('foo' => 1, 'bar' => 'fye',);

        $validation_specs = array(
            'foo' => array('is' => 'int',),
            'bar' => array('is' => 'string', 'regex' => '/^(fee|fye|foe|fum)$/i',),
        );

        $validated_args = ArgValidator::validate($original_args, $validation_specs);        
        $this->assertEqual($validated_args, $original_args);

        $this->expectException();
        ArgValidator::validate(
            array('foo' => 1, 'bar' => 'bell',),
            $validation_specs
        );        
    }

    public function test_instanceof()
    {
        $class = 'Foo';
        $foo = new $class();

        $validation_specs = 
            array('foo' => array('instanceof' => $class,), 'bar' => array('is' => 'string',),);

        $validated_args = ArgValidator::validate(
            array('foo' => $foo, 'bar' => 'baz',),
            $validation_specs
        );        
        // Apparently can't use a quoted string in place of $class. Must be a literal or a string var.
        $this->assertTrue($validated_args['foo'] instanceof $class);

        $this->expectException();
        ArgValidator::validate(
            array('foo' => 'manchu', 'bar' => 'baz',),
            $validation_specs
        );        
    }

    public function test_default()
    {
        $validated_args = ArgValidator::validate(
            array('foo' => 1,),
            array('foo' => array('is' => 'int',), 'bar' => array('is' => 'string', 'default' => 'baz'),)
        );        
        $this->assertEqual($validated_args, array('foo' => 1, 'bar' => 'baz'));
    }

    public function test_default_function()
    {
        $class = 'Foo';
        $validated_args = ArgValidator::validate(
            array('bar' => 'baz',),
            array(
                'foo' => array('instanceof' => $class, 'default_function' => create_function('', 'return new Foo();')),
                'bar' => array('is' => 'string', 'default' => 'baz'),
            )
        ); 
        $this->assertTrue($validated_args['foo'] instanceof $class);
    }

    function test_default_user_function()
    {
        $class = 'Foo';
        $validated_args = ArgValidator::validate(
            array(),
            array(
                'date' => array('is' => 'string', 'default_user_function' => array('Foo','now'),),
            )
        ); 
        $this->assertEqual($validated_args['date'], date('Ymd'));
    }

} // end class ArgValidatorTest

class Foo
{
    public function now()
    {
        return date('Ymd');
    }
}

