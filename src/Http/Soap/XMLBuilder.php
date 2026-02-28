<?php

declare(strict_types=1);

namespace Santosdave\SabreWrapper\Http\Soap;

use InvalidArgumentException;
use DateTimeInterface;
use DateTimeImmutable;
use DOMDocument;

/**
 * XMLBuilder class for generating SOAP XML requests for Sabre Web Services
 * Handles both Session and Stateless Token authentication with Client ID support
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
    private array $actionNamespaces = [
        'SessionCreateRQ' => 'http://www.opentravel.org/OTA/2002/11',
        'TokenCreateRQ' => 'http://webservices.sabre.com',
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
     * Set the SOAP action for the request
     */
    public function setAction(string $action): self
    {
        if (empty($action)) {
            throw new InvalidArgumentException('Action cannot be empty');
        }

        $this->action = $action;
        return $this;
    }

    /**
     * Set the security token (for non-session requests)
     */
    public function setToken(string $token): self
    {
        if (empty(trim($token))) {
            throw new InvalidArgumentException('Token cannot be empty');
        }

        $this->token = $token;
        return $this;
    }

    /**
     * Set the PCC (Pseudo City Code)
     */
    public function setPcc(string $pcc): self
    {
        if (!preg_match('/^[A-Z0-9]{3,4}$/', $pcc)) {
            throw new InvalidArgumentException('Invalid PCC format. Must be 3-4 alphanumeric characters.');
        }

        $this->pcc = $pcc;
        return $this;
    }

    /**
     * Set the version for the request
     */
    public function setVersion(string $version): self
    {
        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported version: %s. Supported versions: %s',
                    $version,
                    implode(', ', self::SUPPORTED_VERSIONS)
                )
            );
        }

        $this->version = $version;
        return $this;
    }

    /**
     * Set the payload data for the request
     */
    public function setPayload(array $payload): self
    {
        $this->validatePayload($payload);
        $this->payload = $payload;
        return $this;
    }

    /**
     * Set custom timestamp (mainly for testing)
     */
    public function setTimestamp(DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Add a namespace mapping for custom actions
     */
    public function addNamespace(string $action, string $namespace): self
    {
        if (empty($action) || empty($namespace)) {
            throw new InvalidArgumentException('Both action and namespace must be non-empty strings');
        }

        $this->actionNamespaces[$action] = $namespace;
        return $this;
    }

    /**
     * Build authentication request (session or stateless)
     */
    public function buildAuthenticationRequest(string $type, array $credentials): string
    {
        switch ($type) {
            case 'session':
                return $this->buildSessionCreateRequest($credentials);
            case 'stateless':
                return $this->buildTokenCreateRequest($credentials);
            default:
                throw new InvalidArgumentException("Invalid authentication type: {$type}");
        }
    }

    /**
     * Build Session Create Request with Client ID support
     */
    public function buildSessionCreateRequest(array $credentials): string
    {
        $this->validateSessionCredentials($credentials);

        $messageId = $this->generateMessageId();
        $timestamp = $this->getTimestamp();
        $conversationId = $this->generateConversationId();

        // Build XML as array first, then join to ensure no extra whitespace
        $xmlParts = [];
        $xmlParts[] = '<?xml version="1.0" encoding="UTF-8"?>';
$xmlParts[] = '<ns:Envelope xmlns:ns="' . self::NAMESPACES['soap-env'] . '">';
    $xmlParts[] = ' <ns:Header>';
        $xmlParts[] = $this->buildMessageHeader($conversationId, $messageId, $timestamp, 'SessionCreateRQ');
        $xmlParts[] = $this->buildSecurityHeader($credentials);
        $xmlParts[] = ' </ns:Header>';
    $xmlParts[] = ' <ns:Body>';
        $xmlParts[] = '
        <SessionCreateRQ xmlns="' . $this->getNamespaceForAction('SessionCreateRQ') . '" />';
        $xmlParts[] = '
    </ns:Body>';
    $xmlParts[] = '</ns:Envelope>';

// Join without extra newlines and trim any whitespace
$xml = implode("\n", $xmlParts);
return trim($xml);
}

/**
* Build Token Create Request with Client ID support
*/
public function buildTokenCreateRequest(array $credentials): string
{
$this->validateSessionCredentials($credentials);

$messageId = $this->generateMessageId();
$timestamp = $this->getTimestamp();
$conversationId = $this->generateConversationId();

// Build XML as array first, then join to ensure no extra whitespace
$xmlParts = [];
$xmlParts[] = '
<?xml version="1.0" encoding="UTF-8"?>';
$xmlParts[] = '<ns:Envelope xmlns:ns="' . self::NAMESPACES['soap-env'] . '">';
    $xmlParts[] = ' <ns:Header>';
        $xmlParts[] = $this->buildMessageHeader($conversationId, $messageId, $timestamp, 'TokenCreateRQ');
        $xmlParts[] = $this->buildSecurityHeader($credentials);
        $xmlParts[] = ' </ns:Header>';
    $xmlParts[] = ' <ns:Body>';
        $xmlParts[] = '
        <TokenCreateRQ xmlns="' . $this->getNamespaceForAction('TokenCreateRQ') . '" />';
        $xmlParts[] = '
    </ns:Body>';
    $xmlParts[] = '</ns:Envelope>';

// Join without extra newlines and trim any whitespace
$xml = implode("\n", $xmlParts);
return trim($xml);
}

/**
* Build general SOAP request with token authentication
*/
public function build(): string
{
$this->validateRequiredFields();

$messageId = $this->generateMessageId();
$timestamp = $this->getTimestamp();
$conversationId = $this->generateConversationId();

$xmlParts = [];
$xmlParts[] = '
<?xml version="1.0" encoding="UTF-8"?>';
$xmlParts[] = '<ns:Envelope xmlns:ns="' . self::NAMESPACES['soap-env'] . '">';
    $xmlParts[] = ' <ns:Header>';
        $xmlParts[] = $this->buildMessageHeader($conversationId, $messageId, $timestamp, $this->action);
        $xmlParts[] = $this->buildTokenSecurityHeader();
        $xmlParts[] = ' </ns:Header>';
    $xmlParts[] = ' <ns:Body>';
        $xmlParts[] = $this->buildBody();
        $xmlParts[] = ' </ns:Body>';
    $xmlParts[] = '</ns:Envelope>';

return trim(implode("\n", $xmlParts));
}

/**
* Build SOAP message header
*/
private function buildMessageHeader(string $conversationId, string $messageId, string $timestamp, string $action):
string
{
$xmlParts = [];
$xmlParts[] = ' <eb:MessageHeader xmlns:eb="' . self::NAMESPACES['eb'] . '" eb:version="1.0" ns:mustUnderstand="1">';
    $xmlParts[] = ' <eb:From>';
        $xmlParts[] = ' <eb:PartyId eb:type="URI">Agency</eb:PartyId>';
        $xmlParts[] = ' </eb:From>';
    $xmlParts[] = ' <eb:To>';
        $xmlParts[] = ' <eb:PartyId eb:type="URI">Sabre_API</eb:PartyId>';
        $xmlParts[] = ' </eb:To>';
    $xmlParts[] = ' <eb:ConversationId>' . htmlspecialchars($conversationId) . '</eb:ConversationId>';
    $xmlParts[] = ' <eb:Service eb:type="sabreXML">Session</eb:Service>';
    $xmlParts[] = ' <eb:Action>' . htmlspecialchars($action) . '</eb:Action>';
    $xmlParts[] = ' <eb:MessageData>';
        $xmlParts[] = ' <eb:MessageId>' . htmlspecialchars($messageId) . '</eb:MessageId>';
        $xmlParts[] = ' <eb:Timestamp>' . htmlspecialchars($timestamp) . '</eb:Timestamp>';
        $xmlParts[] = ' </eb:MessageData>';
    $xmlParts[] = ' </eb:MessageHeader>';

return implode("\n", $xmlParts);
}

/**
* Build security header with username/password/client credentials
*/
private function buildSecurityHeader(array $credentials): string
{
$xmlParts = [];
$xmlParts[] = ' <wsse:Security xmlns:wsse="' . self::NAMESPACES['wsse'] . '">';
    $xmlParts[] = ' <wsse:UsernameToken>';
        $xmlParts[] = ' <wsse:Username>' . htmlspecialchars($credentials['username']) . '</wsse:Username>';
        $xmlParts[] = ' <wsse:Password>' . htmlspecialchars($credentials['password']) . '</wsse:Password>';
        $xmlParts[] = ' <wsse:Organization>' . htmlspecialchars($credentials['pcc']) . '</wsse:Organization>';
        $xmlParts[] = ' <wsse:Domain>' . htmlspecialchars($credentials['domain'] ?? self::DEFAULT_DOMAIN) . '
        </wsse:Domain>';

        // Add Client ID and Client Secret if provided (mandatory for Sabre compliance)
        if (!empty($credentials['clientId'])) {
        $xmlParts[] = ' <wsse:ClientId>' . htmlspecialchars($credentials['clientId']) . '</wsse:ClientId>';
        }

        if (!empty($credentials['clientSecret'])) {
        $xmlParts[] = ' <wsse:ClientSecret>' . htmlspecialchars($credentials['clientSecret']) . '</wsse:ClientSecret>';
        }

        $xmlParts[] = ' </wsse:UsernameToken>';
    $xmlParts[] = ' </wsse:Security>';

return implode("\n", $xmlParts);
}

/**
* Build security header with binary security token
*/
private function buildTokenSecurityHeader(): string
{
$xmlParts = [];
$xmlParts[] = ' <wsse:Security xmlns:wsse="' . self::NAMESPACES['wsse'] . '">';
    $xmlParts[] = ' <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">' .
        htmlspecialchars($this->token) . '</wsse:BinarySecurityToken>';
    $xmlParts[] = ' </wsse:Security>';

return implode("\n", $xmlParts);
}

/**
* Build OAP body
*/
private function buildBody(): string
{
$payload = $this->payload;

// Remove authentication related fields from payload
unset(
$payload['username'],
$payload['password'],
$payload['organization'],
$payload['domain'],
$payload['clientId'],
$payload['clientSecret']
);

$actionNode = $this->action;

if (!empty($this->version)) {
$actionNode .= sprintf(' Version="%s"', $this->version);
}

$namespace = $this->getNamespaceForAction($this->action);
if ($namespace) {
$actionNode .= sprintf(' xmlns="%s"', $namespace);
}

$content = empty($payload) ? '' : $this->arrayToXmlString($payload);

return sprintf(' <%s>%s</%s>' . "\n", $actionNode, $content, $this->action);
    }

    /**
     * Convert array to XML string
     */
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
                $content = htmlspecialchars((string) $value);
            }

            $xml .= $this->createXmlElement($key, $content, $attributes);
        }

        return $xml;
    }

    /**
     * Handle array values in XML conversion
     */
    private function handleArrayValue(array $value, string $key): string
    {
        if ($this->isSequentialArray($value)) {
            return implode('', array_map(
                fn($item) => $this->createXmlElement($key, $this->arrayToXmlString($item)),
                $value
            ));
        }

        return $this->arrayToXmlString($value);
    }

    /**
     * Check if array is sequential (numeric keys starting from 0)
     */
    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Create XML element with optional attributes
     */
    private function createXmlElement(string $key, string $content, string $attributes = ''): string
    {
        $attributeString = $attributes ? ' ' . $attributes : '';
        return sprintf('<%s%s>%s</%s>', $key, $attributeString, $content, $key);
    }

    /**
     * Extract attributes from array (keys starting with @)
     */
    private function extractAttributes(array $array, string $key): string
    {
        $attributes = [];
        $attributeKey = '@' . $key;

        if (isset($array[$attributeKey]) && is_array($array[$attributeKey])) {
            foreach ($array[$attributeKey] as $attrName => $attrValue) {
                $attributes[] = sprintf('%s="%s"', $attrName, htmlspecialchars((string) $attrValue));
            }
        }

        return implode(' ', $attributes);
    }

    /**
     * Check if key should be skipped (attribute keys)
     */
    private function shouldSkipKey(string $key): bool
    {
        return str_starts_with($key, '@');
    }

    /**
     * Validation Methods
     */
    private function validateRequiredFields(): void
    {
        if (empty($this->action)) {
            throw new InvalidArgumentException('Action is required');
        }

        if (empty($this->token)) {
            throw new InvalidArgumentException('Token is required for non-authentication requests');
        }
    }

    private function validateSessionCredentials(array $credentials): void
    {
        $required = ['username', 'password', 'pcc'];

        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate Client ID is provided (mandatory for Sabre)
        if (empty($credentials['clientId']) || empty($credentials['clientSecret'])) {
            throw new InvalidArgumentException('Client ID and Client Secret are mandatory for Sabre authentication');
        }
    }

    private function validatePayload(array $payload): void
    {
        array_walk_recursive($payload, function ($value) {
            if (is_object($value)) {
                throw new InvalidArgumentException('Objects are not allowed in payload');
            }
        });
    }

    /**
     * Helper Methods
     */
    private function generateConversationId(): string
    {
        return date('Y.m.d') . '.DevStudio';
    }

    private function generateMessageId(): string
    {
        return sprintf('mid:%s@sabre.com', bin2hex(random_bytes(16)));
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
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

    private function getNamespaceForAction(string $action): ?string
    {
        return $this->actionNamespaces[$action] ?? null;
    }

    private function getNamespace(string $key): string
    {
        return self::NAMESPACES[$key] ?? '';
    }

    /**
     * Format XML with proper indentation
     */
    private function formatXml(string $xml): string
    {
        // Instead of using DOMDocument (which can add unwanted content),
        // just return the clean XML we built
        return trim($xml);

        /* OLD PROBLEMATIC CODE:
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    
    if (!$dom->loadXML($xml, LIBXML_NOCDATA)) {
        throw new InvalidArgumentException('Invalid XML generated');
    }
    
    return $dom->saveXML() ?: $xml;
    */
    }
    private function formatXmlSafe(string $xml): string
    {
        try {
            // Ensure no leading/trailing whitespace
            $xml = trim($xml);

            // Only format if XML is valid and we really need pretty printing
            if (strpos($xml, '<?xml') === 0) {
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;

                // Suppress warnings during load
                $oldSetting = libxml_use_internal_errors(true);
                $loaded = $dom->loadXML($xml, LIBXML_NOCDATA);
                libxml_use_internal_errors($oldSetting);

                if ($loaded) {
                    $formatted = $dom->saveXML();
                    if ($formatted && strpos($formatted, '<?xml') === 0) {
                        return $formatted;
                    }
                }
            }

            // Fallback to original if formatting fails
            return $xml;
        } catch (\Exception $e) {
            // Return original XML if any error occurs
            return $xml;
        }
    }

    public function validateXml(string $xml): array
    {
        $xml = trim($xml);

        // Check for XML declaration at start
        if (strpos($xml, '<?xml') !== 0) {
            return [
                'valid' => false,
                'error' => 'XML declaration not at start',
                'first_chars' => substr($xml, 0, 50)
            ];
        }

        // Check for extra content before XML declaration
        if (preg_match('/^\s+<\?xml/', $xml)) {
            return [
                'valid' => false,
                'error' => 'Whitespace before XML declaration',
                'first_chars' => substr($xml, 0, 50)
            ];
        }

        // Try to parse XML
        $oldSetting = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($oldSetting);

        if (!$loaded) {
            return [
                'valid' => false,
                'error' => 'XML parsing failed',
                'libxml_errors' => array_map(fn($err) => $err->message, $errors)
            ];
        }

        return [
            'valid' => true,
            'error' => null
        ];
    }
}