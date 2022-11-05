<?php
/**
 * @author Luc-Olivier Noel
 */
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Http;
use Exception;
use JsonException;
use RuntimeException;

use Rockndonuts\Hackqc\Logger;

/**
 * To use:
 * $client = new Client();
 * @client->send(Client::POST, '/servers/create', ['name'=>'a', 'informations'=>'b']);
 */
class Client
{
    /* Verbs */
    public const POST      = "POST";
    public const GET       = "GET";
    public const DELETE    = "DELETE";

    public const AUTHENTICATION_KEY = "Bearer";

    protected ?string $baseUrl = "";
    private ?array $params = null;
    private array $headers = ['Content-type' => 'application/json'];
    private mixed $query;
    private ?string $token = "";

    protected bool $isAuthenticatedRequest = false;

    public function __construct(?string $baseUrl = "")
    {
        $this->baseUrl = $baseUrl;
        $this->query = curl_init();
        $this->init();
    }

    /**
     * Adds an authorization header
     * @param string $token The auth token
     * @param string $type
     * @return Client
     */
    public function authenticate(string $token, $type = self::AUTHENTICATION_KEY): Client
    {
        return $this;
    }

    /**
     * Sends a request
     * @param string|null $method The request's verb
     * @param string|null $resource The url to query
     * @param array|null $params An array of arguments to send with the query
     * @param string|null $body
     * @return string|bool
     * @throws Exception
     */
    public function send(?string $method = null,
                        ?string $resource = null,
                        ?array $params = [],
                        ?string $body = null): string|bool
    {
        curl_setopt($this->query, CURLOPT_HTTPHEADER, $this->headers());
        curl_setopt($this->query, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->query, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->query, CURLOPT_USERAGENT, "PHP NA - 1.0.11");
        $paramsString = http_build_query($params);
        $suffix = "";

        switch ($method) {
            case self::GET:
                if (!empty($paramsString)) {
                    $suffix = "?".$paramsString;
                }
                // To send raw body data we need to actually POST the request BUT send a 'CUSTOMREQUEST' as a GET
                if (!empty($body)) {
                    curl_setopt($this->query, CURLOPT_POSTFIELDS, $body);
                    curl_setopt($this->query, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($this->query, CURLOPT_POST, true);

                }
                break;
            case self::POST:
                $paramsString = json_encode($params, JSON_THROW_ON_ERROR);

                curl_setopt($this->query, CURLOPT_POST, true);
                curl_setopt($this->query, CURLOPT_POSTFIELDS, $paramsString);
                break;
            case self::DELETE:
                break;
        }

        curl_setopt($this->query, CURLOPT_URL, $this->baseUrl . $resource . $suffix);

        $response = curl_exec($this->query);

        if ($response === false) {
            $exception = new Exception('Curl error :: '. curl_error($this->query) . ' -- '. curl_errno($this->query));
            Logger::log($exception->getMessage());
            curl_close($this->query);
            throw $exception;
        }

        $this->endQuery();

        return $response;
    }

    /**
     * Closes and creates a new query
     * @return void
     */
    private function endQuery(): void
    {
        curl_close($this->query);
    }

    /**
     * Alias for send() with POST method
     * @param string $url The url to post to
     * @param array|null $params The parameters to pass to the query
     * @return
     * @throws Exception
     */
    public function post(string $url, ?array $params = []): bool|string
    {
        return $this->send(self::POST, $url, $params);
    }

    /**
     * Alias for send() with GET method
     * @param string $url The url to post to
     * @param array|null $params The parameters to pass to the query
     * @return string|bool
     * @throws Exception
     */
    public function get(string $url, ?array $params = []): string|bool
    {
        return $this->send(self::GET, $url, $params);
    }

    /**
     * Adds a header to the request
     * @param string $name The name of the header
     * @param mixed $value The value of the header
     * @return Client
     *@throws RuntimeException|Exception If you try to add an Authorization header, use addAuthorization instead
     */
    public function addHeader(string $name, mixed $value): static
    {
        if (strtolower($name) === 'authorization') {
            throw new RuntimeException('Cannot add Authorization header.');
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Returns the headers array
     * @return array
     */
    private function headers(): array
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name.': '.$value;
        }

        return $headers;
    }

    protected function getUrl($endpoint): string
    {
        if (!constant('static::ENDPOINT')) {
            return $endpoint;
        }

        return static::ENDPOINT . $endpoint;
    }

    /**
     * @throws JsonException
     */
    protected function json($data)
    {
        return json_decode($data, false, 512, JSON_THROW_ON_ERROR);
    }

    protected function init(): void
    {
        $this->authenticate($this->token);
    }

    /**
     * Replaces current Content-type header with application/x-www-form-urlencoded
     * @return void
     * @throws Exception
     */
    protected function addFormHeader(): void
    {
        $this->addHeader('Content-type', 'application/x-www-form-urlencoded');
    }

    /**
     * Removes a header from the list
     * @param string $name
     * @return bool
     */
    protected function removeHeader(string $name): bool
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }

        return true;
    }
}
