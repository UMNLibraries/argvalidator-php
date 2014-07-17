<?php

namespace UmnLib\Core;

// TODO: For positional validation, use Reflection to get any variable names and default values from the caller.
// Also, check that the argument types match any types declared for the param in the docblock.
// Also also: use the docblock syntax for nested data structures, e.g. 'Array(string)' or whatever it is.
// For more natural and/or powerful type hinting ideas:
// SPL Type Handling: http://pl.php.net/manual/en/book.spl-types.php
// Type Hinting Class: http://www.php.net/manual/en/language.oop5.typehinting.php#83442 (Other posts in that thread may also be useful.)

class ArgValidator
{
  static function validate($args, $specs)
  {
    // For cases of only one parameter, allow for $args to be a scalar
    // and for only one $spec array, e.g. put them into arrays if they're not already.
    $args = is_scalar($args) ? array($args) : $args;
    $specsValues = array_values($specs);
    $specs = is_array($specsValues[0]) ? $specs : array($specs);

    foreach ($specs as $param => $spec) {
      try {
        $args = self::validateRequired($args, $param, $spec);        
      } catch(Exception $e) {
        $message = $e->getMessage();
        $trace = $e->getTraceAsString();
        throw new \InvalidArgumentException("$message:\n$trace");
      }
      // If there's still no key for the param, assume that it wasn't required:
      if (!array_key_exists($param, $args)) {
        continue;
      } 
      $value = $args[$param];

      foreach ($spec as $key => $constraint) {
        if (in_array($key, array('required','default','default_function','default_user_function'))) {
          continue;
        }
        $method = 'validate' . ucfirst($key);
        try {
          self::$method($param, $value, $constraint);
        } catch(Exception $e) {
          $message = $e->getMessage();
          $trace = $e->getTraceAsString();
          throw new \InvalidArgumentException("$message:\n$trace");
        }
      }
    }
    return $args;
  }

  static function validateRequired($args, $param, $spec)
  {
    // TODO: Add support for default values and optional params.
    // Maybe also for (non-)slurpy lists in validatePos()?
    if (array_key_exists($param, $args)) {
      return $args;
    }
    if (array_key_exists('required', $spec) && $spec['required'] === false) {
      return $args;
    }
    if (array_key_exists('default', $spec)) {
      $args[$param] = $spec['default'];
      return $args;
    }
    if (array_key_exists('default_function', $spec)) {
      $function = $spec['default_function'];
      $args[$param] = $function();
      return $args;
    }
    if (array_key_exists('default_user_function', $spec)) {
      // TODO: Validation of default_user_function array passed in by user?
      // Or maybe just a try-catch with an enhanced error message?
      $args[$param] = call_user_func( $spec['default_user_function'] );
      return $args;
    }
    throw new \InvalidArgumentException("Missing argument for required parameter '$param'");
  }

  static function validateIs($param, $value, $type)
  {
    $function = "is_$type";
    if (!$function($value)) {
      $backtrace = debug_backtrace();
      $callerFrameIndex = 0;
      for ($i = 0; $i < sizeof($backtrace); $i++) {
        $frame = $backtrace[$i];
        if ($frame['class'] == 'ArgValidator' && $frame['function'] == 'validate') {
          $callerFrameIndex = $i + 1;
          break;
        }
      }
      $callerFrame = $backtrace[ $callerFrameIndex ];
      $callersCallerFrame = $backtrace[ $callerFrameIndex + 1 ];
      //throw new Exception("The argument '$value' for parameter '$param' is not of type '$type': \n" . print_r($callerFrame) , print_r($callersCallerFrame));
      throw new \InvalidArgumentException("The argument '$value' for parameter '$param' is not of type '$type'");
    }
  }

  static function validateRegex($param, $value, $regex) {
    if (!preg_match($regex, $value)) {
      throw new \InvalidArgumentException("The argument '$value' for parameter '$param' failed to match regex '$regex'");
    }
  }

  static function validateInstanceof($param, $value, $class)
  {
    if (!$value instanceof $class) {
      throw new \InvalidArgumentException("The argument '$value' for parameter '$param' is not an instance of '$class'");
    }
  }
}
