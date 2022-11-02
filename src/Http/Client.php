<?php
/**
 * @author Luc-Olivier Noel
 *
 */
declare(strict_types=1);

namespace Rockndonuts\Hackqc\Http;
/**
 * To use:
 * $client = new ApiClient();
 * @client->send(ApiClient::POST, '/servers/create', ['name'=>'a', 'informations'=>'b']);
 */
class Client
{
    /* Verbs */
    public const POST      = "POST";
    public const GET       = "GET";
    public const DELETE    = "DELETE";

    public const AUTHENTICATION_KEY = "Bearer";

    protected $baseUrl = "";
    private $params;
    private $headers = ['Content-type' => 'application/json'];
    private $query;
    private $token = "";

    protected $isAuthenticatedRequest = false;

    public function __construct(?string $baseUrl = "")
    {
        $this->baseUrl = $baseUrl;
        $this->query = curl_init();
        $this->init();
    }

    /**
     * Adds an authorization header
     * @param string $token The auth token
     * @return ApiClient
     */
    public function authenticate(string $token, $type = self::AUTHENTICATION_KEY)
    {
        return $this;
    }

    /**
     * Sends a request
     * @param string $method The request's verb
     * @param string $resource The url to query
     * @param array $params An array of arguments to send with the query
     * @return mixed
     */
    public function send(?string $method = null,
                        ?string $resource = null,
                        ?array $params = [],
                        ?string $body = null)
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
                if ($body && !empty($body)) {
                    curl_setopt($this->query, CURLOPT_POSTFIELDS, $body);
                    curl_setopt($this->query, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($this->query, CURLOPT_POST, true);

                }
                break;
            case self::POST:
                $paramsString = json_encode($params);

                curl_setopt($this->query, CURLOPT_POST, true);
                curl_setopt($this->query, CURLOPT_POSTFIELDS, $paramsString);
                break;
            case self::DELETE:
                break;
        }



        curl_setopt($this->query, CURLOPT_URL, $this->baseUrl . $resource . $suffix);

        $response = curl_exec($this->query);

        if ($response === false) {
            /**
             * @todo Error Logger
             *
             */
            $exception = new \Exception('Curl error :: '. curl_error($this->query) . ' -- '. curl_errno($this->query));
            @error_log($exception->getMessage());
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
     * @throws \Exception
     */
    public function post(string $url, ?array $params = [])
    {
        return $this->send(self::POST, $url, $params);
    }

    /**
     * Alias for send() with GET method
     * @param string $url The url to post to
     * @param array|null $params The parameters to pass to the query
     * @return ApiResponse
     * @throws \Exception
     */
    public function get(string $url, ?array $params = [])
    {
        return $this->send(self::GET, $url, $params);
    }

    /**
     * Adds a header to the request
     * @param string $name The name of the header
     * @param mixed $value The value of the header
     * @throws Exception|\Exception If you try to add an Authorization header, use addAuthorization instead
     * @return ApiClient
     */
    public function addHeader(string $name, $value)
    {
        if (strtolower($name) === 'authorization') {
            throw new \Exception('Cannot add Authorization header.');
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

    protected function getUrl($endpoint)
    {
        return static::ENDPOINT . $endpoint;
    }

    protected function json($data)
    {
        return json_decode($data, false, 512, JSON_THROW_ON_ERROR);
    }

    protected function init()
    {
        $this->authenticate($this->token, static::AUTHENTICATION_KEY);
    }

    /**
     * Replaces current Content-type header with application/x-www-form-urlencoded
     * @return void
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
