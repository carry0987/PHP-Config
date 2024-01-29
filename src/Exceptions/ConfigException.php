<?php
namespace carry0987\Config\Exceptions;

class ConfigException extends \PDOException
{
    public ?array $errorInfo;

    // Override constructor to pass error information
    public function __construct(string $message, mixed $code = 0, ?array $errorInfo = [])
    {
        parent::__construct($message, (int) $code);
        $this->errorInfo = $errorInfo;
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function getErrorInfo()
    {
        return $this->errorInfo;
    }
}
