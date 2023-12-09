<?php

/**
 * Wait for all packages to be included.
 */
rex_extension::register('PACKAGES_INCLUDED', static function () {
    /**
     * Register a new extension point to change the base route.
     */
    Rest::$baseRoute = rex_extension::registerPoint(new rex_extension_point('REST_BASE_ROUTE', 'api'));

    /**
     * Handle routes only if not in backend.
     */
    if (!\rex::isBackend()) {
        Rest::handleRoutes();
    }
});
