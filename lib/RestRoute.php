<?php

class RestRoute
{
    protected string $route;
    protected array $methods;
    protected string $callback;
    protected array $arguments;
    protected array $args;
    private array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    /**
     * @throws rex_exception
     */
    public function __construct(array $args)
    {
        $this->args = $args;

        $this->setRoute();
        $this->setMethods();
        $this->setCallback();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return trim($this->route, '/');
    }

    /**
     * @return void
     * @throws rex_exception
     */
    private function setRoute(): void
    {
        if (!isset($this->args['route'])) {
            throw new rex_exception('Route must be defined');
        }

        $this->route = $this->args['route'];
    }

    /**
     * @return void
     * @throws rex_exception
     */
    private function setMethods(): void
    {
        if (!isset($this->args['methods']) || empty($this->args['methods'])) {
            throw new rex_exception('At least one method must be defined');
        }

        $this->validateMethods();
        $this->methods = $this->args['methods'];
    }

    /**
     * @return void
     * @throws rex_exception
     */
    private function validateMethods(): void
    {
        foreach ($this->args['methods'] as $method) {
            if (in_array($method, $this->allowedMethods, true)) {
                continue;
            }

            throw new rex_exception(sprintf('Method "%s" not allowed!', $method));
        }
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function validateRequestMethod(): void
    {
        $method = filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_STRING);

        if (!in_array($method, $this->methods, true)) {
            $this->sendError(sprintf('Method "%s" not allowed!', $method), rex_response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @return void
     * @throws rex_exception
     */
    private function setCallback(): void
    {
        if (!isset($this->args['callback']) && $this->args['callback'] !== '') {
            throw new rex_exception('A callback must be defined');
        }

        $this->validateCallback();
        $this->callback = $this->args['callback'];
    }

    /**
     * @return void
     * @throws rex_exception
     */
    private function validateCallback(): void
    {
        if (!is_callable($this->args['callback'])) {
            throw new rex_exception(sprintf('Callback "%s" is not callable!', $this->args['callback']));
        }
    }

    /**
     * @return void
     * @throws rex_exception
     */
    public function executeCallback(): void
    {
        call_user_func($this->callback, $this);
    }

    /**
     * @param array $content
     * @param string $statusCode
     * @return void
     * @throws JsonException
     */
    public function sendContent(array $content, string $statusCode = rex_response::HTTP_OK) {
        rex_response::cleanOutputBuffers();
        rex_response::sendContentType('application/json');
        rex_response::setStatus($statusCode);
        rex_response::sendContent(json_encode($content, JSON_THROW_ON_ERROR));
        exit();
    }

    /**
     * @param string $message
     * @param string $statusCode
     * @return void
     * @throws JsonException
     */
    private function sendError(string $message, string $statusCode): void
    {
        $response = [
            'message' => $message,
            'status' => $statusCode,
        ];

        rex_response::cleanOutputBuffers();
        rex_response::sendContentType('application/json');
        rex_response::setStatus($statusCode);
        rex_response::sendContent(json_encode($response, JSON_THROW_ON_ERROR));
        exit();
    }
}
