<?php

namespace Eloquent\Composer\NpmBridge\Exception;

use Exception;

/**
 * The NPM command failed.
 */
final class NpmCommandFailedException extends Exception
{
    /**
     * Construct a new NPM command failed exception.
     *
     * @param string         $command The executed command.
     * @param Exception|null $cause   The cause, if available.
     */
    public function __construct($command, $output, Exception $cause = null)
    {
        $this->command = $command;

        parent::__construct(
            "Execution of `$command` failed with the following output:\n$output",
            0,
            $cause
        );
    }

    /**
     * Get the executed command.
     *
     * @return string The command.
     */
    public function command()
    {
        return $this->command;
    }

    private $command;
}
