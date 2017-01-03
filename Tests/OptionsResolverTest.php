<?php

namespace Tequila\OptionsResolver\Exception;

use Tequila\OptionsResolver\OptionsResolver;

class OptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionsResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->resolver = new OptionsResolver();
    }

    ////////////////////////////////////////////////////////////////////////////
    // resolve()
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The option "foo" does not exist. Defined options are: "a", "z".
     */
    public function testResolveFailsIfNonExistingOption()
    {
        $this->resolver->setDefault('z', '1');
        $this->resolver->setDefault('a', '2');

        $this->resolver->resolve(['foo' => 'bar']);
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     * @expectedExceptionMessage The options "baz", "foo", "ping" do not exist. Defined options are: "a", "z".
     */
    public function testResolveFailsIfMultipleNonExistingOptions()
    {
        $this->resolver->setDefault('z', '1');
        $this->resolver->setDefault('a', '2');

        $this->resolver->resolve(['ping' => 'pong', 'foo' => 'bar', 'baz' => 'bam']);
    }

    ////////////////////////////////////////////////////////////////////////////
    // setDefault()/hasDefault()
    ////////////////////////////////////////////////////////////////////////////

    public function testSetDefaultReturnsThis()
    {
        $this->assertSame($this->resolver, $this->resolver->setDefault('foo', 'bar'));
    }

    public function testSetDefault()
    {
        $this->resolver->setDefault('one', '1');
        $this->resolver->setDefault('two', '20');

        $this->assertEquals([
            'one' => '1',
            'two' => '20',
        ], $this->resolver->resolve());
    }

    public function testHasDefault()
    {
        $this->assertFalse($this->resolver->hasDefault('foo'));
        $this->resolver->setDefault('foo', 42);
        $this->assertTrue($this->resolver->hasDefault('foo'));
    }

    public function testHasDefaultWithNullValue()
    {
        $this->assertFalse($this->resolver->hasDefault('foo'));
        $this->resolver->setDefault('foo', null);
        $this->assertTrue($this->resolver->hasDefault('foo'));
    }

    ////////////////////////////////////////////////////////////////////////////
    // setRequired()/isRequired()/getRequiredOptions()
    ////////////////////////////////////////////////////////////////////////////

    public function testSetRequiredReturnsThis()
    {
        $this->assertSame($this->resolver, $this->resolver->setRequired('foo'));
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\MissingOptionsException
     */
    public function testResolveFailsIfRequiredOptionMissing()
    {
        $this->resolver->setRequired('foo');

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfRequiredOptionSet()
    {
        $this->resolver->setRequired('foo');
        $this->resolver->setDefault('foo', 'bar');

        $this->assertNotEmpty($this->resolver->resolve());
    }

    public function testResolveSucceedsIfRequiredOptionPassed()
    {
        $this->resolver->setRequired('foo');

        $this->assertNotEmpty($this->resolver->resolve(['foo' => 'bar']));
    }

    public function testIsRequired()
    {
        $this->assertFalse($this->resolver->isRequired('foo'));
        $this->resolver->setRequired('foo');
        $this->assertTrue($this->resolver->isRequired('foo'));
    }

    public function testRequiredIfSetBefore()
    {
        $this->assertFalse($this->resolver->isRequired('foo'));

        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setRequired('foo');

        $this->assertTrue($this->resolver->isRequired('foo'));
    }

    public function testStillRequiredAfterSet()
    {
        $this->assertFalse($this->resolver->isRequired('foo'));

        $this->resolver->setRequired('foo');
        $this->resolver->setDefault('foo', 'bar');

        $this->assertTrue($this->resolver->isRequired('foo'));
    }

    public function testIsNotRequiredAfterRemove()
    {
        $this->assertFalse($this->resolver->isRequired('foo'));
        $this->resolver->setRequired('foo');
        $this->resolver->remove('foo');
        $this->assertFalse($this->resolver->isRequired('foo'));
    }

    public function testIsNotRequiredAfterClear()
    {
        $this->assertFalse($this->resolver->isRequired('foo'));
        $this->resolver->setRequired('foo');
        $this->resolver->clear();
        $this->assertFalse($this->resolver->isRequired('foo'));
    }

    public function testGetRequiredOptions()
    {
        $this->resolver->setRequired(['foo', 'bar']);
        $this->resolver->setDefault('bam', 'baz');
        $this->resolver->setDefault('foo', 'boo');

        $this->assertSame(['foo', 'bar'], $this->resolver->getRequiredOptions());
    }

    ////////////////////////////////////////////////////////////////////////////
    // isMissing()/getMissingOptions()
    ////////////////////////////////////////////////////////////////////////////

    public function testIsMissingIfNotSet()
    {
        $this->assertFalse($this->resolver->isMissing('foo'));
        $this->resolver->setRequired('foo');
        $this->assertTrue($this->resolver->isMissing('foo'));
    }

    public function testIsNotMissingIfSet()
    {
        $this->resolver->setDefault('foo', 'bar');

        $this->assertFalse($this->resolver->isMissing('foo'));
        $this->resolver->setRequired('foo');
        $this->assertFalse($this->resolver->isMissing('foo'));
    }

    public function testIsNotMissingAfterRemove()
    {
        $this->resolver->setRequired('foo');
        $this->resolver->remove('foo');
        $this->assertFalse($this->resolver->isMissing('foo'));
    }

    public function testIsNotMissingAfterClear()
    {
        $this->resolver->setRequired('foo');
        $this->resolver->clear();
        $this->assertFalse($this->resolver->isRequired('foo'));
    }

    public function testGetMissingOptions()
    {
        $this->resolver->setRequired(['foo', 'bar']);
        $this->resolver->setDefault('bam', 'baz');
        $this->resolver->setDefault('foo', 'boo');

        $this->assertSame(['bar'], $this->resolver->getMissingOptions());
    }

    ////////////////////////////////////////////////////////////////////////////
    // setDefined()/isDefined()/getDefinedOptions()
    ////////////////////////////////////////////////////////////////////////////

    public function testDefinedOptionsNotIncludedInResolvedOptions()
    {
        $this->resolver->setDefined('foo');

        $this->assertSame([], $this->resolver->resolve());
    }

    public function testDefinedOptionsIncludedIfDefaultSetBefore()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setDefined('foo');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testDefinedOptionsIncludedIfDefaultSetAfter()
    {
        $this->resolver->setDefined('foo');
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testDefinedOptionsIncludedIfPassedToResolve()
    {
        $this->resolver->setDefined('foo');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve(['foo' => 'bar']));
    }

    public function testIsDefined()
    {
        $this->assertFalse($this->resolver->isDefined('foo'));
        $this->resolver->setDefined('foo');
        $this->assertTrue($this->resolver->isDefined('foo'));
    }

    public function testRequiredOptionsAreDefined()
    {
        $this->assertFalse($this->resolver->isDefined('foo'));
        $this->resolver->setRequired('foo');
        $this->assertTrue($this->resolver->isDefined('foo'));
    }

    public function testSetOptionsAreDefined()
    {
        $this->assertFalse($this->resolver->isDefined('foo'));
        $this->resolver->setDefault('foo', 'bar');
        $this->assertTrue($this->resolver->isDefined('foo'));
    }

    public function testGetDefinedOptions()
    {
        $this->resolver->setDefined(['foo', 'bar']);
        $this->resolver->setDefault('baz', 'bam');
        $this->resolver->setRequired('boo');

        $this->assertSame(['foo', 'bar', 'baz', 'boo'], $this->resolver->getDefinedOptions());
    }

    public function testRemovedOptionsAreNotDefined()
    {
        $this->assertFalse($this->resolver->isDefined('foo'));
        $this->resolver->setDefined('foo');
        $this->assertTrue($this->resolver->isDefined('foo'));
        $this->resolver->remove('foo');
        $this->assertFalse($this->resolver->isDefined('foo'));
    }

    public function testClearedOptionsAreNotDefined()
    {
        $this->assertFalse($this->resolver->isDefined('foo'));
        $this->resolver->setDefined('foo');
        $this->assertTrue($this->resolver->isDefined('foo'));
        $this->resolver->clear();
        $this->assertFalse($this->resolver->isDefined('foo'));
    }

    ////////////////////////////////////////////////////////////////////////////
    // setAllowedTypes()
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testSetAllowedTypesFailsIfUnknownOption()
    {
        $this->resolver->setAllowedTypes('foo', 'string');
    }

    /**
     * @dataProvider provideInvalidTypes
     */
    public function testResolveFailsIfInvalidType($actualType, $allowedType, $exceptionMessage)
    {
        $this->resolver->setDefined('option');
        $this->resolver->setAllowedTypes('option', $allowedType);
        $this->setExpectedException('Tequila\OptionsResolver\Exception\InvalidOptionsException', $exceptionMessage);
        $this->resolver->resolve(['option' => $actualType]);
    }

    public function provideInvalidTypes()
    {
        return [
            [true, 'string', 'The option "option" is expected to be of type "string", but is of type "boolean".'],
            [false, 'string', 'The option "option" is expected to be of type "string", but is of type "boolean".'],
            [fopen(__FILE__, 'r'), 'string', 'The option "option" is expected to be of type "string", but is of type "resource".'],
            [[], 'string', 'The option "option" is expected to be of type "string", but is of type "array".'],
            [new OptionsResolver(), 'string', 'The option "option" is expected to be of type "string", but is of type "Tequila\OptionsResolver\OptionsResolver".'],
            [42, 'string', 'The option "option" is expected to be of type "string", but is of type "integer".'],
            [null, 'string', 'The option "option" is expected to be of type "string", but is of type "NULL".'],
            ['bar', '\stdClass', 'The option "option" is expected to be of type "\stdClass", but is of type "string".'],
        ];
    }

    public function testResolveSucceedsIfValidType()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedTypes('foo', 'string');

        $this->assertNotEmpty($this->resolver->resolve());
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "foo" is expected to be of type "string" or "bool", but is of type "integer".
     */
    public function testResolveFailsIfInvalidTypeMultiple()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->setAllowedTypes('foo', ['string', 'bool']);

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidTypeMultiple()
    {
        $this->resolver->setDefault('foo', true);
        $this->resolver->setAllowedTypes('foo', ['string', 'bool']);

        $this->assertNotEmpty($this->resolver->resolve());
    }

    public function testResolveSucceedsIfInstanceOfClass()
    {
        $this->resolver->setDefault('foo', new \stdClass());
        $this->resolver->setAllowedTypes('foo', '\stdClass');

        $this->assertNotEmpty($this->resolver->resolve());
    }

    ////////////////////////////////////////////////////////////////////////////
    // addAllowedTypes()
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testAddAllowedTypesFailsIfUnknownOption()
    {
        $this->resolver->addAllowedTypes('foo', 'string');
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfInvalidAddedType()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->addAllowedTypes('foo', 'string');

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidAddedType()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->addAllowedTypes('foo', 'string');

        $this->assertNotEmpty($this->resolver->resolve());
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfInvalidAddedTypeMultiple()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->addAllowedTypes('foo', ['string', 'bool']);

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidAddedTypeMultiple()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->addAllowedTypes('foo', ['string', 'bool']);

        $this->assertNotEmpty($this->resolver->resolve());
    }

    public function testAddAllowedTypesDoesNotOverwrite()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedTypes('foo', 'string');
        $this->resolver->addAllowedTypes('foo', 'bool');

        $this->resolver->setDefault('foo', 'bar');

        $this->assertNotEmpty($this->resolver->resolve());
    }

    public function testAddAllowedTypesDoesNotOverwrite2()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedTypes('foo', 'string');
        $this->resolver->addAllowedTypes('foo', 'bool');

        $this->resolver->setDefault('foo', false);

        $this->assertNotEmpty($this->resolver->resolve());
    }

    ////////////////////////////////////////////////////////////////////////////
    // setAllowedValues()
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testSetAllowedValuesFailsIfUnknownOption()
    {
        $this->resolver->setAllowedValues('foo', 'bar');
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "foo" with value 42 is not allowed.
     */
    public function testResolveFailsIfInvalidValue()
    {
        $this->resolver->setDefined('foo');
        $this->resolver->setAllowedValues('foo', 'bar');

        $this->resolver->resolve(['foo' => 42]);
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "foo" with value null is not allowed.
     */
    public function testResolveFailsIfInvalidValueIsNull()
    {
        $this->resolver->setDefault('foo', null);
        $this->resolver->setAllowedValues('foo', 'bar');

        $this->resolver->resolve();
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfInvalidValueStrict()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->setAllowedValues('foo', '42');

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidValue()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedValues('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testResolveSucceedsIfValidValueIsNull()
    {
        $this->resolver->setDefault('foo', null);
        $this->resolver->setAllowedValues('foo', null);

        $this->assertEquals(['foo' => null], $this->resolver->resolve());
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "foo" with value 42 is not allowed.
     */
    public function testResolveFailsIfInvalidValueMultiple()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->setAllowedValues('foo', ['bar', false, null]);

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidValueMultiple()
    {
        $this->resolver->setDefault('foo', 'baz');
        $this->resolver->setAllowedValues('foo', ['bar', 'baz']);

        $this->assertEquals(['foo' => 'baz'], $this->resolver->resolve());
    }

    ////////////////////////////////////////////////////////////////////////////
    // addAllowedValues()
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testAddAllowedValuesFailsIfUnknownOption()
    {
        $this->resolver->addAllowedValues('foo', 'bar');
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfInvalidAddedValue()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->addAllowedValues('foo', 'bar');

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidAddedValue()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->addAllowedValues('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testResolveSucceedsIfValidAddedValueIsNull()
    {
        $this->resolver->setDefault('foo', null);
        $this->resolver->addAllowedValues('foo', null);

        $this->assertEquals(['foo' => null], $this->resolver->resolve());
    }

    /**
     * @expectedException \Tequila\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfInvalidAddedValueMultiple()
    {
        $this->resolver->setDefault('foo', 42);
        $this->resolver->addAllowedValues('foo', ['bar', 'baz']);

        $this->resolver->resolve();
    }

    public function testResolveSucceedsIfValidAddedValueMultiple()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->addAllowedValues('foo', ['bar', 'baz']);

        $this->assertEquals(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testAddAllowedValuesDoesNotOverwrite()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedValues('foo', 'bar');
        $this->resolver->addAllowedValues('foo', 'baz');

        $this->assertEquals(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testAddAllowedValuesDoesNotOverwrite2()
    {
        $this->resolver->setDefault('foo', 'baz');
        $this->resolver->setAllowedValues('foo', 'bar');
        $this->resolver->addAllowedValues('foo', 'baz');

        $this->assertEquals(['foo' => 'baz'], $this->resolver->resolve());
    }

    ////////////////////////////////////////////////////////////////////////////
    // setDefaults()
    ////////////////////////////////////////////////////////////////////////////

    public function testSetDefaultsReturnsThis()
    {
        $this->assertSame($this->resolver, $this->resolver->setDefaults(['foo', 'bar']));
    }

    public function testSetDefaults()
    {
        $this->resolver->setDefault('one', '1');
        $this->resolver->setDefault('two', 'bar');

        $this->resolver->setDefaults([
            'two' => '2',
            'three' => '3',
        ]);

        $this->assertEquals([
            'one' => '1',
            'two' => '2',
            'three' => '3',
        ], $this->resolver->resolve());
    }

    ////////////////////////////////////////////////////////////////////////////
    // remove()
    ////////////////////////////////////////////////////////////////////////////

    public function testRemoveReturnsThis()
    {
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame($this->resolver, $this->resolver->remove('foo'));
    }

    public function testRemoveSingleOption()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setDefault('baz', 'boo');
        $this->resolver->remove('foo');

        $this->assertSame(['baz' => 'boo'], $this->resolver->resolve());
    }

    public function testRemoveMultipleOptions()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setDefault('baz', 'boo');
        $this->resolver->setDefault('doo', 'dam');

        $this->resolver->remove(['foo', 'doo']);

        $this->assertSame(['baz' => 'boo'], $this->resolver->resolve());
    }

    public function testRemoveAllowedTypes()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedTypes('foo', 'int');
        $this->resolver->remove('foo');
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testRemoveAllowedValues()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedValues('foo', ['baz', 'boo']);
        $this->resolver->remove('foo');
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testRemoveUnknownOptionIgnored()
    {
        $this->assertNotNull($this->resolver->remove('foo'));
    }

    ////////////////////////////////////////////////////////////////////////////
    // clear()
    ////////////////////////////////////////////////////////////////////////////

    public function testClearReturnsThis()
    {
        $this->assertSame($this->resolver, $this->resolver->clear());
    }

    public function testClearRemovesAllOptions()
    {
        $this->resolver->setDefault('one', 1);
        $this->resolver->setDefault('two', 2);

        $this->resolver->clear();

        $this->assertEmpty($this->resolver->resolve());
    }

    public function testClearAllowedTypes()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedTypes('foo', 'int');
        $this->resolver->clear();
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }

    public function testClearAllowedValues()
    {
        $this->resolver->setDefault('foo', 'bar');
        $this->resolver->setAllowedValues('foo', 'baz');
        $this->resolver->clear();
        $this->resolver->setDefault('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $this->resolver->resolve());
    }
}
