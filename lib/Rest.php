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

            $patternRegex = '/\\\{[a-zA-Z0-9\_\-]+\\\\\\}/';
            $pattern = "@^" . preg_replace($patternRegex, '([a-zA-Z0-9\-\_]+)', preg_quote($routePath, null)) . "$@D";

            preg_match($pattern, $currentPath, $matches);
            array_shift($matches);
            preg_match_all('/\{[a-zA-Z0-9\_\-]+\}/', $routePath, $keys);

            if (!empty($matches)) {
                for ($i = 0, $iMax = count($matches); $i < $iMax; $i++) {
                    $matches[trim($keys[0][$i], '{}')] = $matches[$i];
                    unset($matches[$i]);
                }
            }

            if (mb_substr(self::getCurrentPath(), 0, mb_strlen($routePath)) !== $routePath && empty($matches)) {
                continue;
            }

            $route->setParams($matches);
            $route->validateRequestMethod();
            $route->validatePermission();
            $route->executeCallback();
        }

        return true;
    }
}
