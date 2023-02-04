<?php

class RestRoute
{
    protected string $route;
    protected array $methods;
    protected mixed $callback;
    protected array $arguments;
    protected array $args;
    protected array $params;
    protected string $permission;
    protected array $validations;
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
        $this->setValidations();
        $this->setPermission();
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
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_ENCODED);

        if (!in_array($method, $this->methods, true)) {
            $this->sendError(sprintf('Method "%s" not allowed!', $method), rex_response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_ENCODED);
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
     */
    public function setPermission(): void
    {
        if (!isset($this->args['permission']) || $this->args['permission'] === '') {
            $this->permission = '';
            return;
        }

        $this->permission = $this->args['permission'];
    }

    /**
     * @return void
     */
    public function setValidations(): void
    {
        if (!isset($this->args['validations']) || empty($this->args['validations'])) {
            $this->validations = [];
            return;
        }

        $this->validations = $this->args['validations'];
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function validatePermission(): void
    {
        if ($this->permission !== '') {
            if (!rex::getUser() || ($this->permission === 'admin' && !rex::getUser()->isAdmin()) || !rex::getUser()->hasPerm($this->permission)) {
                $this->sendError('Only authenticated users can access the REST API', rex_response::HTTP_FORBIDDEN);
            }
        }
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function validateParams(): void
    {
        if (empty($this->validations)) {
            return;
        }

        foreach ($this->validations as $paramName => $type) {
            $param = $this->getParam($paramName);

            if (!$param) {
                continue;
            }

            if (!$this->validateType($type, $param)) {
                $this->sendError(sprintf('Param "%s" needs to be "%s"!', $paramName, $type), rex_response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool
     */
    private function validateType(string $type, mixed $value): bool
    {
        switch ($type) {
            case 'int':
                return (bool) filter_var($value, FILTER_VALIDATE_INT);
            case 'number':
                return (bool) is_numeric($value);
            case 'bool':
            case 'boolean':
                return is_bool($value) || in_array($value, array('true', 'false', '1', '0'), true);
            default:
                return false;
        }
    }

    /**
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @param string $type
     * @return array|bool|float|int|mixed|object|string|null
     */
    public function getParam(string $key, string $type = '')
    {
        if (isset($this->params[$key])) {
            return rex_type::cast($this->params[$key], $type);
        }

        return null;
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
    public function sendContent(array $content, string $statusCode = rex_response::HTTP_OK)
    {
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
    public function sendError(string $message, string $statusCode): void
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
