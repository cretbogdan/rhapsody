<?php

namespace Rhapsody\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use \Rhapsody;

/**
 * Rhapsody Provider.
 */
class RhapsodyServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        Rhapsody::setup(array(
            'doctrine_connection' => $app['db']
        ));

        if (isset($app['rhapsody.model_formatter'])) {
            Rhapsody::setModelFormatter($app['rhapsody.model_formatter']);
        }
    }

    public function boot(Application $app)
    {
    }
}
