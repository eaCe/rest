<?php

use Symfony\Component\Dotenv\Dotenv;

function getLaunchUrl() {
    $env = dirname(__DIR__, 2) . '/.env';
    $dotenv = new Dotenv();

    if (file_exists($env)) {
        $dotenv->usePutenv()->load(dirname(__DIR__, 2) . '/.env');
    }

    return getenv('LAUNCH_URL');
}

beforeEach(function ()
{
    $this->url = getLaunchUrl();
});

test('test', function ()
{
});
