Tequila OptionsResolver
=========================

This lib is the result of rewriting the [Symfony OptionsResolver Component](https://github.com/symfony/options-resolver).
The `OptionsResolver` class has been moved to the Tequila namespace in order
to avoid the conflicts with the original component, since the original API of
Symfony's `OptionsResolver` class is not supported there.

The main goal of rewriting Symfony's component was to make simpler and faster
`OptionsResolver`. 
Here's a short list of differences between `Tequila\OptionsResolver` and original Symfony's `OptionsResolver`:
- `Tequila\OptionsResolver` removes support of lazy options and closures, passed to 
`setAllowedTypes()` and `setAllowedValues()`. By doing so, `Tequila\OptionsResolver` does not need anymore
 to clone itself every time `resolve()` is called.
- By removing support of lazy options (closures, passed to `setDefault()`), `Tequila\OptionsResolver` becomes simple
enough to know exactly, what value is set as default for any option (if set). Therefore, there are two new methods: 
`getDefault()` and `removeDefault()`.
- `Tequila\OptionsResolver` also changes the normalizers functionality. 
Symfony's `OptionsResolver` normalizer closures received `Options` instance as first argument, 
and option value as second. `Tequila\OptionsResolver` changes signature of normalizer closures.
First argument of a normalizer closure is an option value, and second argument is an option name:

```php
$normalizer = function($optionValue, $optionName) {
    echo sprintf('Normalizing option "%s" with value "%s"', $optionName, $optionValue);
    
    return $optionValue;
};
$resolver->setNormalizer('foo', $normalizer);
$resolver->setNormalizer('bar', $normalizer);

$resolver->resolve(['foo' => 1, 'bar' => 2]);
// This will output:
// Normalizing option "foo" with value "1"
// Normalizing option "bar" with value "2"
```

You can see that normalizer closures no longer support `Options` instance. In fact, this interface 
has been removed at all. By not passing options to normalizers, `OptionsResolver` does not need to worry about
locks and cyclic dependencies between options. But that also means that you cannot normalize option 
depending on other options values. If you want to do so, just extend `Tequila\OptionsResolver` 
and do this in `resolve()` method like so:

```php
class MyResolver extends \Tequila\OptionsResolver
{
    public function resolve(array $options = [])
    {
        $options = parent::resolve($options);
        
        if (true === $options['secured']) {
            $options['schema'] = 'https';
        }
        
        return $options;
    }
}
```

Value, returned by the normalizer, is not validated against rules, set for the option. This value returned from
`resolve()` as is.
- Since `Tequila\OptionsResolver` does not implement `Options` interface anymore, 
it does not support `\ArrayAccess` and `\Countable` methods.

This library is used in [Tequila MongoDB PHP Library](https://github.com/tequila/mongodb-php-lib).

