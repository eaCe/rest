<?php

class Rest
{
    public static $baseRoute = '';
    protected static $routes = [];

    /**
     * @param array $routeArgs
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
     * @return array
     */
    public static function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @return string
     */
    public static function getCurrentPath(): string
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        return trim($url['path'], '/') ?? '';
    }

    /**
     * @return bool
     * @throws JsonException
     * @throws rex_exception
     */
    public static function handleRoutes(): bool
    {
        if ('' === self::$baseRoute) {
            return false;
        }

        $currentPath = self::getCurrentPath();
        $pathSegments = explode('/', $currentPath);

        if ($pathSegments[0] !== self::$baseRoute) {
            return false;
        }

        /** @var RestRoute $route */
        foreach (self::$routes as $route) {
            $routePath = implode('/', [self::$baseRoute, $route->getPath()]);

            if (mb_substr(self::getCurrentPath(), 0, mb_strlen($routePath)) !== $routePath) {
                continue;
            }

            // TODO: stuff :)
            $route->validateRequestMethod();
            $route->executeCallback();
        }

        return true;
    }
}
