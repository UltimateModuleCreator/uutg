# Ultimate Unit Test Generator (UUTG)

This is a standalone application for generating the boilerplate for unit tests for PHP unit

## Story
I like writing unit tests for the code I write. But I don't like writing the same thing over and over again.  
So I created this application that does (should do / will hopefully do) the boilerplate for me.


## What it does.  

It generates a class which extends `PHPUnit\Framework\TestCase` and contains
 - a `setUp` method where all class dependencies and public method parameters are mocked.
 - empty methods for each public method in the class where you need to add your tests.
 
## How to use it.
Before using it you need to connect the app to your application for which you create unit tests.  
This can be done in `index.php`. The variable `$externalAppBootstrap` should contain the file path of a boostrap file from your application or any file that has the autoloader configured so all classes can be loaded.  
Example, for magento you can put the path to the `bootstrap.php` file.  
For Symfony include `vendor/autoload.php`.  

You can run this in the browser: `index.php?class=Class\Name\Here`;  
This will show you in the browser the output of the unit test class for the class `Class\Name\Here`.  

You can run this fia cli `php index.php Class\Name\Here`. This will output the code on the screen, but you can output it in a file with ` > filename`.

## How to configure it

The application comes with a config/config.php fill that contains an array with different config values used in the application

 - `namespace_strategy`. The app determines the name of the test class from the name of the class being tested. This is where you configure the differences.
 Example
```php
'namespace_strategy' => [
        2 => 'Test',
        3 => 'Unit'
    ],

```
means that the original class name will be split by backslash and in the resuting array, on position 2, the word `Test` will be introduced and on position 3, the word `Unit`
so original class name `Some\Class\Name\Here` will generated `Some\Class\Test\Unit\Name\HereTest`.  

 - `default_uses` is a list of classes that will automatically be added in the `use` section of the generated class
 - `header` is an array of lines that will appear at the top of the generated class code. Useful if you want to get some licence text in there.
 - `non_testable` a list of method names for which tests will not be created
 - `non_mockable` a list of types that will not be mocked but will have a default value. Usually basic types.


## Known issues

 - If you have to dependency classes that have the same basename, you will get conflicts which you need to solve manually.  
Example

```php
<?php

class A 
{
    public function __construct(\Something\Factory $something, \Else\Factory $else) 
    {
        ....
    }
}
```

WIll generate the following piece of code

```php
<?php

...

use Something\Factory;
use Else\Factory;
...
```

 - callables or closures are mocked as regular classes.  
 
## FAQs and comments

1. "But TDD says you should write the test first and then the class. You are doing it the other way around.". Yes you are right.
2. "Will this make me better at unit testing?". No. It will only make you faster.
3. "This application does not have unit tests.". Right again.
