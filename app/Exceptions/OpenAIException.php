<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpenAIException extends Exception
{
    /** @var array<string, mixed> */
    protected array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function report(): void
    {
        Log::channel('openai')->error($this->getMessage(), [
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
