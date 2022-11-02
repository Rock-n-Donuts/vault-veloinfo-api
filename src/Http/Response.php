<?php

namespace Rockndonuts\Hackqc\Http;

use JsonSerializable;

class Response
{
    public string $contentType;

    public function __construct(
        public readonly array $data,
        public readonly int $httpCode
    )
    {

    }

    /**
     * @param string $contentType
     * @return void
     */
    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return mixed
     * @throws \JsonException
     */
    public function send(): void
    {
        header('Content-type: application/json');

        http_response_code($this->httpCode);
        try {
            echo json_encode($this->data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            http_response_code(500);
            echo json_encode(['error'], JSON_THROW_ON_ERROR);
        }
        die;
    }
}