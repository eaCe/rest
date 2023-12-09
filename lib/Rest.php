<?php

class Rest
{
    /** @var string The base route for all routes, defaults to 'api' */
    public static string $baseRoute = '';

    /** @var array The registered routes */
    private static array $routes = [];

    /**
     * Registers a new route with the given arguments.
     *
     * @param array $routeArgs The arguments for the route
     * @throws rex_exception
     */
    public static function registerRoute(array $routeArgs): void
    {
        if (empty($routeArgs)) {
            throw new rex_exception('Array must not be empty');
        }

        self::$routes[] = new RestRoute($routeArgs);
    }

    /**
     * Returns all registered routes.
     *
     * @return array The registered routes
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Returns the current path.
     *
     * @throws rex_exception
     * @return string The current path
     */
    public static function getCurrentPath(): string
    {
        $url = parse_url(rex::getRequest()->getRequestUri());
        return trim($url['path'], '/') ?? '';
    }

    /**
     * Handles all registered routes.
     *
     * @throws JsonException
     * @throws rex_exception
     */
    public static function handleRoutes(): void
    {
        /**
         * Check if the base route is empty.
         * If so, return to stop further execution.
         */
        if ('' === self::$baseRoute) {
            return;
        }

        $currentPath = self::getCurrentPath();
        $pathSegments = explode('/', $currentPath);

        /**
         * Check if the base route is the first segment of the current path.
         * If not, return to stop further execution.
         */
        if ($pathSegments[0] !== self::$baseRoute) {
            return;
        }

        foreach (self::$routes as $route) {
            assert($route instanceof RestRoute);
            $routePath = implode('/', [self::$baseRoute, $route->getPath()]);

            $patternRegex = '/\\\{[a-zA-Z0-9\_\-]+\\\\\\}/';
            $pattern = '@^' . preg_replace($patternRegex, '([a-zA-Z0-9\-\_]+)', preg_quote($routePath, null)) . '$@D';

            /**
             * Extract the matched values.
             */
            preg_match($pattern, $currentPath, $matches);
            array_shift($matches);
            preg_match_all('/\{[a-zA-Z0-9\_\-]+\}/', $routePath, $keys);

            if (!empty($matches)) {
                for ($i = 0, $iMax = count($matches); $i < $iMax; ++$i) {
                    $matches[trim($keys[0][$i], '{}')] = $matches[$i];
                    unset($matches[$i]);
                }
            }

            /**
             * Check if the route path matches the current path.
             * If not, continue to the next route.
             */
            if ($routePath !== self::getCurrentPath() && empty($matches)) {
                continue;
            }

            /**
             * Route found, validate, execute and return to stop further execution.
             */
            $route->setParams($matches);
            $route->validateRequestMethod();
            $route->validateApiKey();
            $route->validatePermission();
            $route->validateParams();
            $route->executeCallback();
            return;
        }

        /**
         * No route found, send 404.
         */
        rex_response::cleanOutputBuffers();
        rex_response::sendContentType('application/json');
        rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
        rex_response::sendContent(json_encode([
            'message' => 'Not found!',
            'status' => rex_response::HTTP_NOT_FOUND,
        ], JSON_THROW_ON_ERROR));
    }
}
