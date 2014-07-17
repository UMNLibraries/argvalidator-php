<?php

namespace UmnLib\Core\Tests;

use UmnLib\Core\ArgValidator;

class ArgValidatorTest extends \PHPUnit_Framework_TestCase
{
  public function testRequired()
  {
    $validatedArgs = ArgValidator::validate(
      array('foo' => 1),
      array('foo' => array('is' => 'int'), 'bar' => array('is' => 'string', 'required' => false))
    );        
    $this->assertEquals(array('foo' => 1), $validatedArgs);

    $validatedArgs = ArgValidator::validate(
      array(),
      array('foo' => array('required' => false), 'bar' => array('required' => false))
    );        
    // TODO: Is there a less verbose test method than assertEquals for this? Maybe assertEmpty...
    $this->assertEquals(array(), $validatedArgs);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testRequiredException()
  {
    ArgValidator::validate(
      array('foo' => 1), // missing second parameter
      array('foo' => array('is' => 'int'), 'bar' => array('is' => 'string'))
    );        
  }

  function testPositionalArgs()
  {
    $originalArgs = array(1, 'baz');
    $validationSpecs = array(array('is' => 'int'), array('is' => 'string'));

    $validatedArgs = ArgValidator::validate(
      $originalArgs,
      $validationSpecs
    );        
    $this->assertEquals($originalArgs, $validatedArgs);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  function testPositionalArgsException()
  {
    ArgValidator::validate(
      array(1), // missing second arg
      array(array('is' => 'int'), array('is' => 'string'))
    );        
  }

  function testSingleArg()
  {
    $originalArg = 'foo';
    $validationSpec = array('is' => 'string');

    $validatedArgs = ArgValidator::validate(
      $originalArg,
      $validationSpec
    );        
    $this->assertEquals(array($originalArg), $validatedArgs);
  }

  public function testIs()
  {
    $originalArgs = array('foo' => 1, 'bar' => 'baz');

    // Another reason this class is helpful: PHP doesn't allow 'string'
    // or 'int' type hinting. WTF?
    $validationSpecs = array(
      'foo' => array('is' => 'int'),
      'bar' => array('is' => 'string'),
    );

    $validatedArgs = ArgValidator::validate(
      $originalArgs, $validationSpecs
    );
    $this->assertEquals($originalArgs, $validatedArgs);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testIsException()
  {
    ArgValidator::validate(
      array('foo' => 'manchu', 'bar' => 'baz'),
      array(
        'foo' => array('is' => 'int'),
        'bar' => array('is' => 'string'),
      )
    );        
  }

  public function testRegex()
  {
    $originalArgs = array('foo' => 1, 'bar' => 'fye');

    $validationSpecs = array(
      'foo' => array('is' => 'int'),
      'bar' => array('is' => 'string', 'regex' => '/^(fee|fye|foe|fum)$/i'),
    );

    $validatedArgs = ArgValidator::validate($originalArgs, $validationSpecs);        
    $this->assertEquals($originalArgs, $validatedArgs);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testRegexException()
  {
    ArgValidator::validate(
      array('foo' => 1, 'bar' => 'bell'),
      array(
        'foo' => array('is' => 'int'),
        'bar' => array('is' => 'string', 'regex' => '/^(fee|fye|foe|fum)$/i'),
      )
    );        
  }

  public function testInstanceof()
  {
    $class = '\\UmnLib\\Core\\Tests\\Foo';
    $foo = new $class();

    $validationSpecs = 
      array('foo' => array('instanceof' => $class), 'bar' => array('is' => 'string'));

    $validatedArgs = ArgValidator::validate(
      array('foo' => $foo, 'bar' => 'baz'),
      $validationSpecs
    );        
    // Apparently can't use a quoted string in place of $class. Must be a literal or a string var.
    $this->assertTrue($validatedArgs['foo'] instanceof $class);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testInstanceofException()
  {
    ArgValidator::validate(
      array('foo' => 'manchu', 'bar' => 'baz'),
      array('foo' => array('instanceof' => '\\UmnLib\\Core\\Tests\\Foo'), 'bar' => array('is' => 'string'))
    );        
  }

  public function testDefault()
  {
    $validatedArgs = ArgValidator::validate(
      array('foo' => 1,),
      array('foo' => array('is' => 'int'), 'bar' => array('is' => 'string', 'default' => 'baz'))
    );        
    $this->assertEquals(array('foo' => 1, 'bar' => 'baz'), $validatedArgs);
  }

  public function testDefaultFunction()
  {
    $class = '\\UmnLib\\Core\\Tests\\Foo';
    $validatedArgs = ArgValidator::validate(
      array('bar' => 'baz'),
      array(
        'foo' => array('instanceof' => $class, 'default_function' => function () { return new \UmnLib\Core\Tests\Foo(); }),
        'bar' => array('is' => 'string', 'default' => 'baz'),
      )
    ); 
    $this->assertTrue($validatedArgs['foo'] instanceof $class);
  }

  function testDefaultUserFunction()
  {
    $class = 'Foo';
    $validatedArgs = ArgValidator::validate(
      array(),
      array(
        'date' => array('is' => 'string', 'default_user_function' => array('\\UmnLib\\Core\\Tests\\Foo','now')),
      )
    ); 
    $this->assertEquals(date('Ymd'), $validatedArgs['date']);
  }

}

class Foo
{
  public function now()
  {
    return date('Ymd');
  }
}

