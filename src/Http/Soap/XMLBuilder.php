<?php

declare(strict_types=1);

namespace Santosdave\Sabre\Http\Soap;

use InvalidArgumentException;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * XMLBuilder class for generating SOAP XML requests for Sabre Web Services
 */
class XMLBuilder
{
    private const DEFAULT_DOMAIN = 'DEFAULT';
    private const SUPPORTED_VERSIONS = ['3.0.0', '3.1.0', '3.2.0', '3.3.0'];

    private const NAMESPACES = [
        'soap-env' => 'http://schemas.xmlsoap.org/soap/envelope/',
        'eb' => 'http://www.ebxml.org/namespaces/messageHeader',
        'wsse' => 'http://schemas.xmlsoap.org/ws/2002/12/secext',
        'session' => 'http://www.opentravel.org/OTA/2002/11',
        'token' => 'http://webservices.sabre.com'
    ];
    
    /** @var array<string, string> */
    private array $namespaces = [
        'SessionCreateRQ' => 'http://www.opentravel.org/OTA/2002/11',
        'OTA_AirAvailRQ' => 'http://webservices.sabre.com/sabreXML/2011/10',
        'BargainFinderMaxRQ' => 'http://www.opentravel.org/OTA/2003/05',
        'EnhancedAirBookRQ' => 'http://services.sabre.com/sp/eab/v3_10',
        'PassengerDetailsRQ' => 'http://services.sabre.com/sp/pd/v3_4',
    ];

    private string $action = '';
    private string $token = '';
    private array $payload = [];
    private string $version = '';
    private string $pcc = '';
    private ?DateTimeInterface $timestamp = null;

    /**
     * @throws InvalidArgumentException
     */
    public function setAction(string $action): self
    {
        if (!array_key_exists($action, $this->namespaces)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported action: %s. Supported actions: %s', 
                    $action, 
                    implode(', ', array_keys($this->namespaces))
                )
            );
        }
        
        $this->action = $action;
        return $this;
    }

    public function setToken(string $token): self
    {
        if (empty(trim($token))) {
            throw new InvalidArgumentException('Token cannot be empty');
        }
        
        $this->token = $token;
        return $this;
    }

    public function setPcc(string $pcc): self
    {
        if (!preg_match('/^[A-Z0-9]{3,4}$/', $pcc)) {
            throw new InvalidArgumentException('Invalid PCC format');
        }
        
        $this->pcc = $pcc;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setVersion(string $version): self
    {
        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported version: %s. Supported versions: %s',
                    $version,
                    implode(', ', self::SUPPORTED_VERSIONS)
                )
            );
        }
        
        $this->version = $version;
        return $this;
    }

    public function setPayload(array $payload): self
    {
        $this->validatePayload($payload);
        $this->payload = $payload;
        return $this;
    }

    /**
     * Allows setting a custom timestamp for testing purposes
     */
    public function setTimestamp(DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function build(): string
    {
        $this->validateRequiredFields();

        $header = $this->action === 'SessionCreateRQ'
            ? $this->buildSessionCreateHeader()
            : $this->buildHeader();

        return $this->formatXml(<<<XML
            <?xml version="1.0" encoding="UTF-8"?>
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    {$header}
    <soap-env:Body>
        {$this->buildBody()}
    </soap-env:Body>
</soap-env:Envelope>
XML
);
}

private function buildHeader(): string
{
return
<<<XML <soap-env:Header>
    <MessageHeader xmlns="http://www.ebxml.org/namespaces/messageHeader">
        <From>
            <PartyId>Agency</PartyId>
        </From>
        <To>
            <PartyId>Sabre_API</PartyId>
        </To>
        <ConversationId>{$this->generateConversationId()}</ConversationId>
        <Action>{$this->action}</Action>
        <MessageData>
            <MessageId>{$this->generateMessageId()}</MessageId>
            <Timestamp>{$this->getTimestamp()}</Timestamp>
        </MessageData>
    </MessageHeader>
    <Security xmlns="http://schemas.xmlsoap.org/ws/2002/12/secext">
        <BinarySecurityToken>{$this->token}</BinarySecurityToken>
    </Security>
    </soap-env:Header>
    XML;
    }

    private function buildSessionCreateHeader(): string
    {
    return <<<XML <soap-env:Header>
        <MessageHeader xmlns="http://www.ebxml.org/namespaces/messageHeader">
            <From>
                <PartyId>Agency</PartyId>
            </From>
            <To>
                <PartyId>Sabre_API</PartyId>
            </To>
            <ConversationId>{$this->generateConversationId()}</ConversationId>
            <Action>SessionCreateRQ</Action>
        </MessageHeader>
        <Security xmlns="http://schemas.xmlsoap.org/ws/2002/12/secext">
            <UsernameToken>
                <Username>{$this->payload['username']}</Username>
                <Password>{$this->payload['password']}</Password>
                <Organization>{$this->pcc}</Organization>
                <Domain>{$this->getDomain()}</Domain>
            </UsernameToken>
        </Security>
        </soap-env:Header>
        XML;
        }

        private function buildBody(): string
        {
        // Create a copy of payload to avoid modifying the original
        $payload = $this->payload;

        // Remove authentication related fields
        unset(
        $payload['username'],
        $payload['password'],
        $payload['organization'],
        $payload['domain']
        );

        $actionNode = $this->action;
        if (!empty($this->version)) {
        $actionNode .= sprintf(' Version="%s"', $this->version);
        }

        $namespace = $this->getNamespaceForAction();
        if ($namespace) {
        $actionNode .= sprintf(' xmlns="%s"', $namespace);
        }

        return sprintf('<%1$s>%2$s</%1$s>',
            $actionNode,
            $this->arrayToXmlString($payload)
        );
    }

    private function arrayToXmlString(array $array): string
    {
        $xml = '';
        foreach ($array as $key => $value) {
            if ($this->shouldSkipKey($key)) {
                continue;
            }

            $attributes = $this->extractAttributes($array, $key);
            
            if (is_array($value)) {
                $content = $this->handleArrayValue($value, $key);
            } else {
                $content = htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            }

            $xml .= $this->wrapInTags($key, $content, $attributes);
        }
        return $xml;
    }

    private function handleArrayValue(array $value, string $key): string
    {
        // Handle numeric arrays (repeated elements)
        if (array_keys($value) === range(0, count($value) - 1)) {
            return $this->handleNumericArray($value, $key);
        }
        
        // Handle associative arrays
        return $this->arrayToXmlString($value);
    }

    private function handleNumericArray(array $items, string $parentKey): string
    {
        $result = '';
        foreach ($items as $item) {
            if (is_array($item)) {
                $result .= $this->arrayToXmlString($item);
            } else {
                $result .= htmlspecialchars((string)$item, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            }
        }
        return $result;
    }

    private function shouldSkipKey(string $key): bool
    {
        return str_starts_with($key, '@');
    }

    private function extractAttributes(array $array, string $key): string
    {
        $attributes = '';
        if (isset($array["@{$key}"])) {
            foreach ($array["@{$key}"] as $attrKey => $attrValue) {
                $attributes .= sprintf(' %s="%s"',
                    $attrKey,
                    htmlspecialchars((string)$attrValue, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                );
            }
        }
        return $attributes;
    }

    private function wrapInTags(string $key, string $content, string $attributes = ''): string
    {
        return sprintf('<%1$s%2$s>%3$s</%1$s>', $key, $attributes, $content);
    }

    private function validateRequiredFields(): void
    {
        if (empty($this->action)) {
            throw new InvalidArgumentException('Action is required');
        }

        if ($this->action === 'SessionCreateRQ') {
            $this->validateSessionCreatePayload();
        } else {
            $this->validateStandardPayload();
        }
    }

    private function validateSessionCreatePayload(): void
    {
        $required = ['username', 'password'];
        foreach ($required as $field) {
            if (empty($this->payload[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (empty($this->pcc)) {
            throw new InvalidArgumentException('PCC is required for session creation');
        }
    }

    private function validateStandardPayload(): void
    {
        if (empty($this->token)) {
            throw new InvalidArgumentException('Token is required for non-session create requests');
        }
    }

    private function validatePayload(array $payload): void
    {
        array_walk_recursive($payload, function($value) {
            if (is_object($value)) {
                throw new InvalidArgumentException('Objects are not allowed in payload');
            }
        });
    }

    private function generateConversationId(): string
    {
        return date('Y-m-d') . '-' . bin2hex(random_bytes(8));
    }

    private function generateMessageId(): string
    {
        return sprintf('mid:%s@sabre.com', bin2hex(random_bytes(16)));
    }

    private function getTimestamp(): string
    {
        $timestamp = $this->timestamp ?? new DateTimeImmutable();
        return $timestamp->format('Y-m-d\TH:i:s\Z');
    }

    private function getDomain(): string
    {
        return $this->payload['domain'] ?? self::DEFAULT_DOMAIN;
    }

    private function getNamespaceForAction(): ?string
    {
        return $this->namespaces[$this->action] ?? null;
    }

    private function getNamespace(string $key): string 
    {
        return self::NAMESPACES[$key] ?? '';
    }

    private function generateUuid(): string 
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function formatXml(string $xml): string
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml, LIBXML_NOCDATA);
        return $dom->saveXML();
    }

    /**
     * Adds a new namespace mapping for custom actions
     */
    public function addNamespace(string $action, string $namespace): self
    {
        if (empty($action) || empty($namespace)) {
            throw new InvalidArgumentException('Both action and namespace must be non-empty strings');
        }
        
        $this->namespaces[$action] = $namespace;
        return $this;
    }

    public function buildAuthenticationRequest(string $type, array $credentials): string {
        return match ($type) {
            'session' => $this->buildSessionCreateRequest($credentials),
            'stateless' => $this->buildTokenCreateRequest($credentials),
            default => throw new \InvalidArgumentException("Invalid authentication type: {$type}")
        };
    }

    public function buildSessionCreateRequest(array $credentials): string 
    {
        $messageId = $this->generateUuid();
        $timestamp = date('Y-m-d\TH:i:s\Z');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap-env:Envelope xmlns:soap-env="{$this->getNamespace('soap-env')}">
    <soap-env:Header>
        <eb:MessageHeader xmlns:eb="{$this->getNamespace('eb')}">
            <eb:From>
                <eb:PartyId>Agency</eb:PartyId>
            </eb:From>
            <eb:To>
                <eb:PartyId>Sabre_API</eb:PartyId>
            </eb:To>
            <eb:ConversationId>{$this->generateConversationId()}</eb:ConversationId>
            <eb:MessageData>
                <eb:MessageId>{$messageId}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
            <eb:Action>SessionCreateRQ</eb:Action>
        </eb:MessageHeader>
        <wsse:Security xmlns:wsse="{$this->getNamespace('wsse')}">
            <wsse:UsernameToken>
                <wsse:Username>{$credentials['username']}</wsse:Username>
                <wsse:Password>{$credentials['password']}</wsse:Password>
                <Organization>{$credentials['pcc']}</Organization>
                <Domain>{$credentials['domain'] ?? 'DEFAULT'}</Domain>
                <ClientId>{$credentials['client_id']}</ClientId>
                <ClientSecret>{$credentials['client_secret']}</ClientSecret>
            </wsse:UsernameToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <SessionCreateRQ Version="2.0.0" xmlns="{$this->getNamespace('session')}">
            <returnContextID>true</returnContextID>
        </SessionCreateRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;
    }

    public function buildTokenCreateRequest(array $credentials): string 
    {
        $messageId = $this->generateUuid();
        $timestamp = date('Y-m-d\TH:i:s\Z');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap-env:Envelope xmlns:soap-env="{$this->getNamespace('soap-env')}">
    <soap-env:Header>
        <eb:MessageHeader xmlns:eb="{$this->getNamespace('eb')}">
            <eb:From>
                <eb:PartyId>Agency</eb:PartyId>
            </eb:From>
            <eb:To>
                <eb:PartyId>Sabre_API</eb:PartyId>
            </eb:To>
            <eb:ConversationId>{$this->generateConversationId()}</eb:ConversationId>
            <eb:MessageData>
                <eb:MessageId>{$messageId}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
            <eb:Action>TokenCreateRQ</eb:Action>
        </eb:MessageHeader>
        <wsse:Security xmlns:wsse="{$this->getNamespace('wsse')}">
            <wsse:UsernameToken>
                <wsse:Username>{$credentials['username']}</wsse:Username>
                <wsse:Password>{$credentials['password']}</wsse:Password>
                <Organization>{$credentials['pcc']}</Organization>
                <Domain>{$credentials['domain'] ?? 'DEFAULT'}</Domain>
                <ClientId>{$credentials['client_id']}</ClientId>
                <ClientSecret>{$credentials['client_secret']}</ClientSecret>
            </wsse:UsernameToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <TokenCreateRQ Version="2.0.0" xmlns="{$this->getNamespace('token')}"/>
    </soap-env:Body>
</soap-env:Envelope>
XML;
    }
}