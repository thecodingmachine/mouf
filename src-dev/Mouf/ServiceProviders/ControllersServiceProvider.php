<?php


namespace Mouf\ServiceProviders;


use Interop\Container\Factories\Alias;
use Interop\Container\ServiceProvider;
use Mouf\Security\Controllers\SimpleLoginController;
use Mouf\Security\UserService\UserServiceInterface;
use Psr\Container\ContainerInterface;

class ControllersServiceProvider implements ServiceProvider
{

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     *
     * Factories have the following signature:
     *        function(ContainerInterface $container, callable $getPrevious = null)
     *
     * About factories parameters:
     *
     * - the container (instance of `Interop\Container\ContainerInterface`)
     * - a callable that returns the previous entry if overriding a previous entry, or `null` if not
     *
     * @return callable[]
     */
    public function getServices()
    {
        return [
            'thecodingmachine.splash.controllers' => [self::class, 'declareControllers'],
            'simpleLoginControllerTemplate' => new Alias('moufLoginTemplate'),
            'root_url' => function() { return ROOT_URL; },
            UserServiceInterface::class => new Alias('userService'),
        ];
    }

    public static function declareControllers(ContainerInterface $container, callable $previous = null)
    {
        if ($previous !== null) {
            $controllers = $previous();
        } else {
            $controllers = [];
        }

        $controllers[] = SimpleLoginController::class;

        return $controllers;
    }
}