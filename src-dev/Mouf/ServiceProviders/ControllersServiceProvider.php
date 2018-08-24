<?php


namespace Mouf\ServiceProviders;


use Interop\Container\Factories\Alias;
use Interop\Container\ServiceProviderInterface;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Security\Controllers\SimpleLoginController;
use Mouf\Security\UserService\UserServiceInterface;
use Psr\Container\ContainerInterface;
use TheCodingMachine\Funky\Annotations\Extension;
use TheCodingMachine\Funky\Annotations\Factory;
use TheCodingMachine\Funky\ServiceProvider;

class ControllersServiceProvider extends ServiceProvider
{
    /**
     * TODO: UserServiceInterface::class should be created by the user service package.
     * @Factory()
     */
    public static function createUserService(ContainerInterface $container): UserServiceInterface
    {
        return $container->get('userService');
    }

    /**
     * @Factory(name="ROOT_URL")
     */
    public static function getRootUrl(): string
    {
        return \ROOT_URL;
    }

    /**
     * @Factory(name="simpleLoginControllerTemplate")
     */
    public static function aliasSimpleLoginControllerTemplate(ContainerInterface $container): TemplateInterface
    {
        return $container->get('moufLoginTemplate');
    }

    /**
     * @Extension(name="thecodingmachine.splash.controllers")
     */
    public static function declareControllers(array $controllers): array
    {
        $controllers[] = SimpleLoginController::class;
        return $controllers;
    }
}
