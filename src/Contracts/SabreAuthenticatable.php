<?php

namespace Santosdave\SabreWrapper\Contracts;

interface SabreAuthenticatable
{
    public function getToken(): string;
    public function refreshToken(): void;
    public function isTokenExpired(): bool;
    public function getAuthorizationHeader(): string;
}
