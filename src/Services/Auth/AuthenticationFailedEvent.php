<?php



namespace Santosdave\SabreWrapper\Events\Auth;

class AuthenticationFailedEvent

{

    private string $type;

    private string $message;

    public function __construct(string $type, string $message)
    {
        $this->type = $type;

        $this->message = $message;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}