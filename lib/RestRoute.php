<?php

class RestRoute
{
    /** @var string The route to use */
    protected string $route;

    /** @var array The allowed request methods */
    protected array $methods;

    /** @var mixed The callback to execute */
    protected mixed $callback;

    /** @var array The arguments for the route */
    protected array $args;

    /** @var array The parameters */
    protected array $params;

    /** @var string The permission to check */
    protected string $permission;

    /** @var array The validations */
    protected array $validations;

    /** @var array The allowed request methods */
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
     * Returns the route path.
     *
     * @return string The route
     */
    public function getPath(): string
    {
        return trim($this->route, '/');
    }

    /**
     * Sets the route.
     *
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
     * Validates the request methods.
     *
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
     * Validates the request method.
     *
     * @throws JsonException
     */
    public function validateRequestMethod(): void
    {
        $method = strtoupper(rex_request::requestMethod());

        if (!in_array($method, $this->methods, true)) {
            $this->sendError(sprintf('Method "%s" not allowed!', $method), rex_response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Returns the routes request method.
     *
     * @return string The request method
     */
    public function getRequestMethod(): string
    {
        return rex_request::requestMethod();
    }

    /**
     * Sets and validates the callback.
     *
     * @throws rex_exception
     */
    private function setCallback(): void
    {
        if (!isset($this->args['callback']) && '' !== $this->args['callback']) {
            throw new rex_exception('A callback must be defined');
        }

        $this->validateCallback();
        $this->callback = $this->args['callback'];
    }

    /**
     * Validates the callback.
     * The callback must be callable.
     *
     * @throws rex_exception
     */
    private function validateCallback(): void
    {
        if (!is_callable($this->args['callback'])) {
            throw new rex_exception(sprintf('Callback "%s" is not callable!', $this->args['callback']));
        }
    }

    /**
     * Sets the permission.
     */
    public function setPermission(): void
    {
        if (!isset($this->args['permission']) || '' === $this->args['permission']) {
            $this->permission = '';
            return;
        }

        $this->permission = $this->args['permission'];
    }

    /**
     * Sets the validations.
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
     * Validates the permission.
     *
     * @throws JsonException
     */
    public function validatePermission(): void
    {
        if ('' !== $this->permission) {
            if (!rex::getUser() || ('admin' === $this->permission && !rex::getUser()->isAdmin()) || !rex::getUser(
            )->hasPerm($this->permission)) {
                $this->sendError('Only authenticated users can access the REST API', rex_response::HTTP_FORBIDDEN);
            }
        }
    }

    /**
     * Validates all params.
     *
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
                $this->sendError(
                    sprintf('Invalid parameter type for "%s"!', $paramName),
                    rex_response::HTTP_BAD_REQUEST,
                );
            }
        }
    }

    /**
     * Validates the given value against the given type.
     *
     * @param string $type The type to validate against
     * @param mixed $value The value to validate
     * @return bool
     */
    private function validateType(string $type, mixed $value): bool
    {
        switch ($type) {
            case 'int':
                return (bool) filter_var($value, FILTER_VALIDATE_INT);
            case 'number':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'bool':
            case 'boolean':
                return is_bool($value) || in_array($value, ['true', 'false', '1', '0'], true);
            default:
                return false;
        }
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Returns all params.
     *
     * @return array The parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns the param with the given key.
     * If the param does not exist, null is returned.
     *
     * @param string $key The key of the param
     * @param string $type The type to cast the param to
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
     * Executes the callback.
     *
     * @throws rex_exception
     */
    public function executeCallback(): void
    {
        call_user_func($this->callback, $this);
    }

    /**
     * Sends a response with the given content.
     *
     * @param array $content The content to send
     * @param string $statusCode The status code to send
     * @throws JsonException
     * @return void
     */
    public function sendContent(array $content, string $statusCode = rex_response::HTTP_OK)
    {
        rex_response::cleanOutputBuffers();
        rex_response::sendContentType('application/json');
        rex_response::setStatus($statusCode);
        rex_response::sendContent(json_encode($content, JSON_THROW_ON_ERROR));
        exit;
    }

    /**
     * Sends an error response.
     *
     * @param string $message The error message
     * @param string $statusCode The status code to send. E.g. rex_response::HTTP_FORBIDDEN
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
        exit;
    }
}
