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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SomeClass::class)] //COMMENT: for PHPUnit 20+
class SomeClassTest extends TestCase
{
    /**
     * @var MemberOne | MockObject //COMMENT: extract all used memmbers at the top of the test. you can configure it via profiles to make them strong typed or not
     */
    private MemberOne|MockObject $memberOne;
    /**
     * @var MmeberTwo | MockObject
     */
    private MmeberTwo|MockObject $member2;
    /**
     * @var SomeClassPath | MockObject
     */
    private SomeClassPath|MockObject $param;
    /**
     * @var SomeOtherClass | MockObject
     */
    private SomeOtherClass|MockObject $paramSomeOtherClass;
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
    //COMMENT ... or for PHPUnit 10+
    #[Test]
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
    //COMMENT ... or for PHPUnit 10+
    #[Test]
    public function testDoSomethingElse()
    {
        $this->someClass->doSomethingElse();
    }
}

```

## How to configure it

You can create your own configuration file starting from uutg.php.dist and use it instead of the default one. 
Each element in the configuration file is explained inside uutg.php.dist


## How to use it.

if you installed this via composer you can run it via cli  
`php ./vendor/bin/uutg --class="Class\\NameHere" [--config=path/to/config/file]`
   - class is the name of the class you want to generate a test for. 
   - config is the path to the config file for generating the test. if empty, the default uutg.php.dist will be used

if you installed it "manually" you can run it similar to the composer version `php ./uutg --class="Class\\NameHere" [--config=path/to/config/file]` 
but you have to make sure your class can be autoloaded but the script.
## FAQs and comments

1. "But TDD says you should write the test first and then the class. You are doing it the other way around.". Yes you are right.
2. "Will this make me better at unit testing?". No. It will only make you faster.
3. "This application does not have unit tests.". Right again.
