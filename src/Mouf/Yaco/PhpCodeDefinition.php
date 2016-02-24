<?php
declare(strict_types=1);

namespace Mouf\Yaco;
use TheCodingMachine\Yaco\Definition\DumpableInterface;
use TheCodingMachine\Yaco\Definition\InlineEntry;

/**
 * This class represents a parameter or instance declared via PHP code.
 */
class PhpCodeDefinition implements DumpableInterface
{
    /**
     * The identifier of the instance in the container.
     *
     * @var string
     */
    private $identifier;

    /**
     * The value of the parameter.
     * It is expected to be a scalar or an array (or more generally anything that can be `var_export`ed).
     *
     * @var mixed
     */
    private $phpCode;

    /**
     * Constructs an instance definition.
     *
     * @param string|null $identifier The identifier of the entry in the container. Can be null if the entry is anonymous (declared inline in other instances)
     * @param string      $phpCode      The PHP Code.
     */
    public function __construct($identifier, string $phpCode)
    {
        $this->identifier = $identifier;
        $this->phpCode = $phpCode;
    }

    /**
     * Returns the identifier of the instance.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the value of the parameter.
     *
     * @return mixed
     */
    public function getPhpCode()
    {
        return $this->phpCode;
    }

    /**
     * Returns an InlineEntryInterface object representing the PHP code necessary to generate
     * the container entry.
     *
     * @param string $containerVariable The name of the variable that allows access to the container instance. For instance: "$container", or "$this->container"
     * @param array  $usedVariables     An array of variables that are already used and that should not be used when generating this code.
     *
     * @return InlineEntryInterface
     */
    public function toPhpCode($containerVariable, array $usedVariables = array())
    {
        $code = sprintf('(function($container) { %s; })(%s)', $this->phpCode, $containerVariable);
        return new InlineEntry($code, null, $usedVariables, false);
    }
}
