# Ultimate Unit Test Generator (UUTG)

This is a standalone application for generating the boilerplate for unit tests for PHP unit

## Story
I like writing unit tests for the code I write. But I don't like writing the same thing over and over again.  
So I created this application that does (should do / will hopefully do) the boilerplate for me.


## What it does.  

It generates a class which extends `PHPUnit\Framework\TestCase` and contains
 - a `setUp` method where all class dependencies and public method parameters are mocked.
 - empty methods for each public method in the class where you need to add your tests.

## Example

(follow the inline comments in the generated code)
For this class and the default profile (see profiles section lower)

```
<?php

declare(strict_types=1);

namespace Dummy;

class SomeClass
{
    /**
     * @var \Dummy\MemberOne
     */
    private $memberOne;
    /**
     * @var \Dummy\MmeberTwo
     */
    private $member2;

    /**
     * @param MemberOne $memberOne
     * @param MmeberTwo $member2
     */
    public function __construct(MemberOne $memberOne, MmeberTwo $member2)
    {
        $this->memberOne = $memberOne;
        $this->member2 = $member2;
    }

    public function doSomething(\Other\Path\SomeClass $param)
    {
        //...method code here
        $this->doSomePrivateAction($param);
    }

    public function doSomethingElse()
    {
        $this->doSomething();
    }

    private function doSomePrivateAction(\Dummy\SomeOtherClass $param)
    {

    }
}

```


it will generate this unit test class

```
<?php

declare(strict_types=1);

namespace Dummy\Test\Unit; // COMMENT: it will build the namespace based on the original class name and the namespace strategy (see profiles below)

use Dummy\MemberOne; //COMMENT:it will extract the used class names
use Dummy\MmeberTwo;
use Dummy\SomeClass;
use Dummy\SomeOtherClass;
use Other\Path\SomeClass as SomeClassPath; //COMMENT: it will avoid conflicts in case there are 2 classes in different namespaces
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SomeClassTest extends TestCase
{
    /**
     * @var MemberOne | MockObject //COMMENT: extract all used memmbers at the top of the test. you can configure it via profiles to make them strong typed or not
     */
    private MemberOne $memberOne;
    /**
     * @var MmeberTwo | MockObject
     */
    private MmeberTwo $member2;
    /**
     * @var SomeClassPath | MockObject
     */
    private SomeClassPath $param;
    /**
     * @var SomeOtherClass | MockObject
     */
    private SomeOtherClass $paramSomeOtherClass;
    /**
     * @var SomeClass
     */
    private SomeClass $someClass;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        //COMMENT:mock all needed instances and instantiate the class being tested
        $this->memberOne = $this->createMock(MemberOne::class);
        $this->member2 = $this->createMock(MmeberTwo::class);
        $this->param = $this->createMock(SomeClassPath::class);
        $this->paramSomeOtherClass = $this->createMock(SomeOtherClass::class);
        $this->someClass = new SomeClass($this->memberOne, $this->member2);
    }

    /**
     * //COMMENT: generate one test method for each public method that strictly belongs to the class being tested
     * //COMMENT: it adds the list of  methods being covered by this test (public/private/protected)
     * @covers \Dummy\SomeClass::doSomething
     * @covers \Dummy\SomeClass::doSomePrivateAction
     * @covers \Dummy\SomeClass::__construct
     */
    public function testDoSomething()
    {
        //COMMENT: generates a stub for your test
        $this->someClass->doSomething($this->param);
    }

    /**
     * @covers \Dummy\SomeClass::doSomethingElse
     * @covers \Dummy\SomeClass::doSomething
     * @covers \Dummy\SomeClass::__construct
     */
    public function testDoSomethingElse()
    {
        $this->someClass->doSomethingElse();
    }
}

```

## How to configure it

You can create your own profile either in the core/profile folder, or in the custom/profile folder (recommended).
A profile is basically a php file with the following structure:
(You can define multiple profiles in the same file, but the recommended approach is to keep them separate).
Let's name the profile: "dummy"

```
<?php
return [
    'dummy' => [ //<-- the profile name. you can make it the same as the file name
        'extends' => 'default', //<-- you can extend a different existing profule and modify only what you need to.
        //this is the absolute path to a file on your project that includes the autoloader
        //example for magento "bootstrap.php", example for symfony "vendor/autoload.php"
        'autoload_path' => '/path/to/autoloader/from/your/app.php'
        //this defines the way the namespace of the test class will be generated based on the base class name
        //in this case, the name of the class will be split by backslash and the words Test and Unit will be inserted at position 2 and 3 in the array
        //Example \Dummy\SomeClass will generated the test class name `Dummy\Test\Unit\SomeClass` 
        'namespace_strategy' => [
            2 => 'Test',
            3 => 'Unit'
        ],
        //this is a list of classes that will be added in the "use" section of the generated test class all the time
        'default_uses' => [
            'PHPUnit\Framework\TestCase',
            'PHPUnit\Framework\MockObject\MockObject'
        ],
        //this text will be added at the top of the generated output. Usefull if you want to add licence text or anyrhing else
        'header' => [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            ''
        ],
        //this is the list of methods that are marked as non testable. It will not generate a test method for this.
        'non_testable' => [
            '__construct'
        ],
        //this marks if the member vars in the generated class will be strong typed or not.
        'strong_type' => true,
        //a list of default types that cannot be mocked. In this case, everytime a string is used in the original class, an empty string will be used as parameter in the test class.
        'non_mockable' => [
            'string' => '""',
            'array' => '[]',
            'int' => '1',
            'bool' => 'true',
            'float' =>  '1.00',
            'callable' => 'function () {
            }',
            '\Closure' => 'function () {
            }'
        ],
        //optional list of types to be replaced when mocked.
        //For example if your class uses as depencency an instance of class / interface `SomeClassName` the actuall mock will be done for `OherClassName`
        'replacements => [
            'SomeClassName' => 'OherClassName',
        ],
        //optional. in case you need extra processing before genrating a test class, you can list here the classes that are going to handle that.
        //each class must implement \Uutg\Rule\RuleInterface. 
        //and the method `process` receives as parameter the instance of the Builder class that handles the test class generation
        //you can create these classes in the "custom" folder as you wish
        'rule_set' => [
            '\Some\ClassName',
            `\SomeOtherClassName,`
        ],
        //this is optional. All test classes are generated via a template. by default is 'templates/test.phtml' but you can create your own and reference them in your profile
        "template" => 'test.phtml',
        ///you can add here anythig else you might need and use them in your custom code byt calling Profile::getData('path/to/config/value/here')
    ],
];

```


## How to use it.

 - in the browser by calling `index.php?class=ClassName\Goes\Here,OtherClassHere&profile=profileName`;
   - class - mandatory:  represents the class or classes for which you generate the tests comma separated
   - profile - optional: the profule name used to generate the test classes. It defaults to "default".

 - cli `php index.php --class=Class\\NameHere [--profile=profileName]`
   - parameters ar the same as above
   - Don't forget to double backslash your classes.
 
## FAQs and comments

1. "But TDD says you should write the test first and then the class. You are doing it the other way around.". Yes you are right.
2. "Will this make me better at unit testing?". No. It will only make you faster.
3. "This application does not have unit tests.". Right again.
