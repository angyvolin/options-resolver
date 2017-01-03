Tequila OptionsResolver
=========================

This lib is the result of rewriting the [Symfony OptionsResolver Component](https://github.com/symfony/options-resolver).
The `OptionsResolver` class has been moved to the Tequila namespace in order
to avoid the conflicts with the original component, since the original API of
Symfony's `OptionsResolver` class is not supported there.

The main goal of rewriting Symfony's component was to make simpler and faster
`OptionsResolver`. 
Here's a short list of differences between `Tequila\OptionsResolver` and original Symfony's `OptionsResolver`:
- `Tequila\OptionsResolver` removes support of the normalizers, lazy options and closures, passed to 
`setAllowedTypes()` and `setAllowedValues()`. By doing so, `Tequila\OptionsResolver` does not need anymore
 to clone itself every time `resolve()` is called.
- By removing support of lazy options (closures, passed to `setDefault()`), `Tequila\OptionsResolver` becomes simple
enough to know exactly, what value is set as default for any option (if set). Therefore, there are two new methods: 
`getDefault()` and `removeDefault()`.
- There also a way to normalize option after it has been resolved: `setResolvedOptionNormalizer()`. 
Unlike old normalizers, closures, passed to the `setResolvedOptionNormalizer()` does not receive all options.
Also, value, returned by the normalizer, is not validated. 
That means that "new" normalizers is just post-resolve processors. You can validate user input by some rules, but then 
translate option, passed by the user, to a different type, that is not valid for user input.
- `Tequila\OptionsResolver` does not implement `Options` interface anymore, and `Options` interface itself is deleted.
That means that `Tequila\OptionsResolver` does not support `\ArrayAccess` and `\Countable` methods.

This library is used in [Tequila MongoDB PHP Library](https://github.com/tequila/mongodb-php-lib).

