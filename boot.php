<?php

rex_extension::register('PACKAGES_INCLUDED', static function ()
{
    Rest::$baseRoute = rex_extension::registerPoint(new rex_extension_point('REST_BASE_ROUTE', 'api'));

    if (!\rex::isBackend()) {
        Rest::handleRoutes();
    }
});
