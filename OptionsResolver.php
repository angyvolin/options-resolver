<?php

namespace Tequila\OptionsResolver;

use Tequila\OptionsResolver\Exception\InvalidOptionsException;
use Tequila\OptionsResolver\Exception\LogicException;
use Tequila\OptionsResolver\Exception\MissingOptionsException;
use Tequila\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * Validates options and merges them with default values.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Tobias Schultze <http://tobion.de>
 * @author Petr Buchyn <petrbuchyn@gmail.com>
 */
class OptionsResolver
{
    /**
     * The names of all defined options.
     *
     * @var array
     */
    private $defined = [];

    /**
     * The default option values.
     *
     * @var array
     */
    private $defaults = [];

    /**
     * The names of required options.
     *
     * @var array
     */
    private $required = [];

    /**
     * A list of accepted values for each option.
     *
     * @var array
     */
    private $allowedValues = [];

    /**
     * A list of accepted types for each option.
     *
     * @var array
     */
    private $allowedTypes = [];

    /**
     * An array of closures that get called for option after option is resolved.
     * Value, returned from the normalizer, will become the option value.
     *
     * Format of this array is as follows: ['optionName' => $closure]
     *
     * Normalizer closure will accept option value as first argument, and option name as second argument.
     *
     * @var array
     */
    private $normalizers = [];

    private static $typeAliases = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
    ];

    /**
     * @param string $option
     * @return mixed
     */
    public function getDefault($option)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        if ($this->hasDefault($option)) {
            return $this->defaults[$option];
        }

        throw new LogicException(sprintf('Option "%s" does not have default value.', $option));
    }

    /**
     * @param string $option
     * @return $this
     */
    public function removeDefault($option)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        unset($this->defaults[$option]);

        return $this;
    }

    /**
     * Sets the default value of a given option.
     *
     * @param string $option The name of the option
     * @param mixed  $value  The default value of the option
     *
     * @return $this
     */
    public function setDefault($option, $value)
    {
        $this->defaults[$option] = $value;
        $this->defined[$option] = true;

        return $this;
    }

    /**
     * Sets a list of default values.
     *
     * @param array $defaults The default values to set
     *
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        foreach ($defaults as $option => $value) {
            $this->setDefault($option, $value);
        }

        return $this;
    }

    /**
     * Returns whether a default value is set for an option.
     *
     * Returns true if {@link setDefault()} was called for this option.
     * An option is also considered set if it was set to null.
     *
     * @param string $option The option name
     *
     * @return bool Whether a default value is set
     */
    public function hasDefault($option)
    {
        return array_key_exists($option, $this->defaults);
    }

    /**
     * Marks one or more options as required.
     *
     * @param string|string[] $optionNames One or more option names
     *
     * @return $this
     */
    public function setRequired($optionNames)
    {
        foreach ((array) $optionNames as $option) {
            $this->defined[$option] = true;
            $this->required[$option] = true;
        }

        return $this;
    }

    /**
     * Returns whether an option is required.
     *
     * An option is required if it was passed to {@link setRequired()}.
     *
     * @param string $option The name of the option
     *
     * @return bool Whether the option is required
     */
    public function isRequired($option)
    {
        return isset($this->required[$option]);
    }

    /**
     * Returns the names of all required options.
     *
     * @return string[] The names of the required options
     *
     * @see isRequired()
     */
    public function getRequiredOptions()
    {
        return array_keys($this->required);
    }

    /**
     * Returns whether an option is missing a default value.
     *
     * An option is missing if it was passed to {@link setRequired()}, but not
     * to {@link setDefault()}. This option must be passed explicitly to
     * {@link resolve()}, otherwise an exception will be thrown.
     *
     * @param string $option The name of the option
     *
     * @return bool Whether the option is missing
     */
    public function isMissing($option)
    {
        return isset($this->required[$option]) && !array_key_exists($option, $this->defaults);
    }

    /**
     * Returns the names of all options missing a default value.
     *
     * @return string[] The names of the missing options
     *
     * @see isMissing()
     */
    public function getMissingOptions()
    {
        return array_keys(array_diff_key($this->required, $this->defaults));
    }

    /**
     * Defines a valid option name.
     *
     * Defines an option name without setting a default value. The option will
     * be accepted when passed to {@link resolve()}. When not passed, the
     * option will not be included in the resolved options.
     *
     * @param string|string[] $optionNames One or more option names
     *
     * @return $this
     */
    public function setDefined($optionNames)
    {
        foreach ((array) $optionNames as $option) {
            $this->defined[$option] = true;
        }

        return $this;
    }

    /**
     * Returns whether an option is defined.
     *
     * Returns true for any option passed to {@link setDefault()},
     * {@link setRequired()} or {@link setDefined()}.
     *
     * @param string $option The option name
     *
     * @return bool Whether the option is defined
     */
    public function isDefined($option)
    {
        return isset($this->defined[$option]);
    }

    /**
     * Returns the names of all defined options.
     *
     * @return string[] The names of the defined options
     *
     * @see isDefined()
     */
    public function getDefinedOptions()
    {
        return array_keys($this->defined);
    }

    /**
     * Sets allowed values for an option.
     *
     * Instead of passing values, you may also pass a closures with the
     * following signature:
     *
     *     function ($value) {
     *         // return true or false
     *     }
     *
     * The closure receives the value as argument and should return true to
     * accept the value and false to reject the value.
     *
     * @param string $option        The option name
     * @param mixed  $allowedValues One or more acceptable values/closures
     *
     * @return $this
     *
     * @throws UndefinedOptionsException If the option is undefined
     */
    public function setAllowedValues($option, $allowedValues)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        $this->allowedValues[$option] = is_array($allowedValues) ? $allowedValues : [$allowedValues];

        return $this;
    }

    /**
     * Adds allowed values for an option.
     *
     * The values are merged with the allowed values defined previously.
     *
     * Instead of passing values, you may also pass a closures with the
     * following signature:
     *
     *     function ($value) {
     *         // return true or false
     *     }
     *
     * The closure receives the value as argument and should return true to
     * accept the value and false to reject the value.
     *
     * @param string $option        The option name
     * @param mixed  $allowedValues One or more acceptable values/closures
     *
     * @return $this
     *
     * @throws UndefinedOptionsException If the option is undefined
     */
    public function addAllowedValues($option, $allowedValues)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        if (!is_array($allowedValues)) {
            $allowedValues = [$allowedValues];
        }

        if (!isset($this->allowedValues[$option])) {
            $this->allowedValues[$option] = $allowedValues;
        } else {
            $this->allowedValues[$option] = array_merge($this->allowedValues[$option], $allowedValues);
        }

        return $this;
    }

    /**
     * Sets allowed types for an option.
     *
     * Any type for which a corresponding is_<type>() function exists is
     * acceptable. Additionally, fully-qualified class or interface names may
     * be passed.
     *
     * @param string          $option       The option name
     * @param string|string[] $allowedTypes One or more accepted types
     *
     * @return $this
     *
     * @throws UndefinedOptionsException If the option is undefined
     */
    public function setAllowedTypes($option, $allowedTypes)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        $this->allowedTypes[$option] = (array)$allowedTypes;

        return $this;
    }

    /**
     * Adds allowed types for an option.
     *
     * The types are merged with the allowed types defined previously.
     *
     * Any type for which a corresponding is_<type>() function exists is
     * acceptable. Additionally, fully-qualified class or interface names may
     * be passed.
     *
     * @param string          $option       The option name
     * @param string|string[] $allowedTypes One or more accepted types
     *
     * @return $this
     *
     * @throws UndefinedOptionsException If the option is undefined
     */
    public function addAllowedTypes($option, $allowedTypes)
    {
        if (!$this->isDefined($option)) {
            throw $this->undefinedOptionException($option);
        }

        if (!isset($this->allowedTypes[$option])) {
            $this->allowedTypes[$option] = (array)$allowedTypes;
        } else {
            $this->allowedTypes[$option] = array_merge($this->allowedTypes[$option], (array)$allowedTypes);
        }

        return $this;
    }

    /**
     * Removes the option with the given name.
     *
     * Undefined options are ignored.
     *
     * @param string|string[] $optionNames One or more option names
     *
     * @return $this
     */
    public function remove($optionNames)
    {
        foreach ((array)$optionNames as $option) {
            unset($this->defined[$option], $this->defaults[$option], $this->required[$option]);
            unset($this->allowedTypes[$option], $this->allowedValues[$option]);
        }

        return $this;
    }

    /**
     * Removes all options.
     *
     * @return $this
     */
    public function clear()
    {
        $this->defined = [];
        $this->defaults = [];
        $this->required = [];
        $this->allowedTypes = [];
        $this->allowedValues = [];

        return $this;
    }

    /**
     * Merges options with the default values stored in the container and
     * validates them.
     *
     * Exceptions are thrown if:
     *
     *  - Undefined options are passed;
     *  - Required options are missing;
     *  - Options have invalid types;
     *  - Options have invalid values.
     *
     * @param array $options A map of option names to values
     *
     * @return array The merged and validated options
     *
     * @throws UndefinedOptionsException If an option name is undefined
     * @throws InvalidOptionsException   If an option doesn't fulfill the
     *                                   specified validation rules
     * @throws MissingOptionsException   If a required option is missing
     */
    public function resolve(array $options = [])
    {
        // Make sure that no unknown options are passed
        $diff = array_diff_key($options, $this->defined);

        if (count($diff) > 0) {
            ksort($this->defined);
            ksort($diff);

            throw new UndefinedOptionsException(sprintf(
                (count($diff) > 1 ? 'The options "%s" do not exist.' : 'The option "%s" does not exist.').' Defined options are: "%s".',
                implode('", "', array_keys($diff)),
                implode('", "', array_keys($this->defined))
            ));
        }

        $resolved = $this->defaults;

        // Override options set by the user
        foreach ($options as $option => $value) {
            $resolved[$option] = $value;
        }

        // Check whether any required option is missing
        $diff = array_diff_key($this->required, $resolved);

        if (count($diff) > 0) {
            ksort($diff);

            throw new MissingOptionsException(sprintf(
                count($diff) > 1 ? 'The required options "%s" are missing.' : 'The required option "%s" is missing.',
                implode('", "', array_keys($diff))
            ));
        }

        // Now process the individual options. Use offsetGet(), which resolves
        // the option itself and any options that the option depends on
        foreach ($resolved as $option => $value) {
            $this->ensureOptionValueHasValidType($option, $value);
            $this->ensureOptionValueIsAllowed($option, $value);

            // apply normalizer to option
            if (isset($this->resolvedOptionNormalizers[$option])) {
                $normalizer = $this->resolvedOptionNormalizers[$option];
                $value = $normalizer($value, $option);
                $resolved[$option] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Sets the normalizer, that will get applied to option after it has been resolved, but before
     * options are returned from {@link resolve()}
     *
     * @param string $option
     * @param \Closure $normalizer
     * @return $this
     */
    public function setNormalizer($option, \Closure $normalizer)
    {
        $this->resolvedOptionNormalizers[$option] = $normalizer;

        return $this;
    }

    /**
     * @param string $option
     * @param mixed $value
     * @void
     *
     * @throws InvalidOptionsException is option value has a wrong type, specified in {@link setAllowedTypes()} method.
     */
    private function ensureOptionValueHasValidType($option, $value)
    {
        if (!isset($this->allowedTypes[$option])) {
            return;
        }

        foreach ($this->allowedTypes[$option] as $type) {
            $type = isset(self::$typeAliases[$type]) ? self::$typeAliases[$type] : $type;

            if (function_exists($isFunction = 'is_'.$type)) {
                if ($isFunction($value)) {
                    return;
                }

                continue;
            }

            if ($value instanceof $type) {
                return;
            }
        }

        throw new InvalidOptionsException(
            sprintf(
                'The option "%s" is expected to be of type "%s", but is of type "%s".',
                $option,
                implode('" or "', $this->allowedTypes[$option]),
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    /**
     * @param string $option
     * @param mixed $value
     * @void
     * @throws InvalidOptionsException if option value is not allowed.
     * Allowed values are specified in {@link setAllowedValues()} method.
     */
    private function ensureOptionValueIsAllowed($option, $value)
    {
        // Validate the value of the resolved option
        if (!isset($this->allowedValues[$option])) {
            return;
        }

        foreach ($this->allowedValues[$option] as $allowedValue) {
            if ($value === $allowedValue) {
                return;
            }
        }

        throw new InvalidOptionsException(
            sprintf(
                'The option "%s" with value %s is not allowed.',
                $option,
                $this->formatValue($value)
            )
        );
    }

    /**
     * Returns a string representation of the value.
     *
     * This method returns the equivalent PHP tokens for most scalar types
     * (i.e. "false" for false, "1" for 1 etc.). Strings are always wrapped
     * in double quotes (").
     *
     * @param mixed $value The value to format as string
     *
     * @return string The string representation of the passed value
     */
    private function formatValue($value)
    {
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_string($value)) {
            return '"'.$value.'"';
        }

        if (is_resource($value)) {
            return 'resource';
        }

        if (null === $value) {
            return 'null';
        }

        if (false === $value) {
            return 'false';
        }

        if (true === $value) {
            return 'true';
        }

        return (string)$value;
    }

    private function undefinedOptionException($option)
    {
        return new UndefinedOptionsException(
            sprintf(
                'The option "%s" does not exist. Defined options are: "%s".',
                $option,
                implode('", "', array_keys($this->defined))
            )
        );
    }
}
