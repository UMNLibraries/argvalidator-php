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
    $class = '\UmnLib\Core\Tests\ArgValidatorFoo';
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
      array('foo' => array('instanceof' => '\UmnLib\Core\Tests\ArgValidatorFoo'), 'bar' => array('is' => 'string'))
    );        
  }

  public function testDefault()
  {
    $validatedArgs = ArgValidator::validate(
      array('foo' => 1),
      array(
        'foo' => array('is' => 'int', 'default' => 23),
        'bar' => array('is' => 'string', 'default' => 'baz'),
      )
    );        
    $this->assertEquals(array('foo' => 1, 'bar' => 'baz'), $validatedArgs);
  }

  public function testBuilder()
  {
    $class = '\UmnLib\Core\Tests\ArgValidatorFoo';
    $object = new \stdClass();
    $fooSpec = array('is' => 'object', 'builder' => function () use($class) { return new $class(); });
    $giantString = 'fee fye foe fum';
    $giantArray = explode(' ', $giantString);

    $validatedArgs = ArgValidator::validate(
      array('object' => $object, 'bar' => 'baz'),
      array(
        // No foo in the args, so ArgValidator should use the builder to define it:
        'foo' => $fooSpec,

        // If the param is defined with a value in the args, ArgValidator should ignore the builder:
        'object' => $fooSpec,
        'bar' => array('is' => 'string', 'builder' => function () { return 'none'; }),

        // Various ways of defining the builder. If the builder is a scalar, it must be callable.
        // If the builder is an array, either the entire array must be callable, or the first element
        // must be callable. In the latter case, ArgValidator will pass any remaining elements in the 
        // builder array as parameters to the callable.
        'giant' => array('is' => 'array', 'builder' => array('explode', ' ', $giantString)),
        'nowScalar' => array('is' => 'string', 'builder' => $class . '::now'),
        'nowArray' => array('is' => 'string', 'builder' => array($class, 'now')),

        // Equivalent to: 'builder' => array($class . '::join', 'fee', 'fye', 'foe', 'fum')
        'joinScalar' => array('is' => 'string', 'builder' => array_merge(array($class . '::join'), $giantArray)),

        // Equivalent to: 'builder' => array(array(new $class(), 'join'), 'fee', 'fye', 'foe', 'fum')
        'joinArray' => array('is' => 'string', 'builder' => array_merge(array(array(new $class(), 'join')), $giantArray)),
      )
    ); 

    $nowExpected = $class::now();
    $joinExpected = 'fee fye foe fum';

    $this->assertTrue(is_object($validatedArgs['object']));
    $this->assertTrue($validatedArgs['object'] instanceof \stdClass);
    $this->assertTrue(is_object($validatedArgs['foo']));
    $this->assertTrue($validatedArgs['foo'] instanceof $class);
    $this->assertEquals('baz', $validatedArgs['bar']);
    $this->assertEquals($giantArray, $validatedArgs['giant']);
    $this->assertEquals($nowExpected, $validatedArgs['nowScalar']);
    $this->assertEquals($nowExpected, $validatedArgs['nowArray']);
    $this->assertEquals($joinExpected, $validatedArgs['joinScalar']);
    $this->assertEquals($joinExpected, $validatedArgs['joinArray']);
  }
}

class ArgValidatorFoo
{
  public function now()
  {
    return date('Ymd');
  }

  public function join()
  {
    return implode(' ', func_get_args());
  }
}

