<?php

namespace Santosdave\Sabre\Contracts;

interface SabreAuthenticatable
{
    public function getToken(): string;
    public function refreshToken(): void;
    public function isTokenExpired(): bool;
    public function getAuthorizationHeader(): string;
}