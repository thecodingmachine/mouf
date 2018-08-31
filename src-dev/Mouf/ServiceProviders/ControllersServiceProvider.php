<?php


namespace Mouf\ServiceProviders;


use Interop\Container\Factories\Alias;
use Interop\Container\ServiceProviderInterface;
use Mouf\Controllers\MoufInstallController;
use Mouf\Controllers\MoufRootController;
use Mouf\Controllers\MoufValidatorController;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Security\Controllers\SimpleLoginController;
use Mouf\Security\UserService\UserServiceInterface;
use Mouf\Validator\MoufValidatorService;
use Psr\Container\ContainerInterface;
use TheCodingMachine\Funky\Annotations\Extension;
use TheCodingMachine\Funky\Annotations\Factory;
use TheCodingMachine\Funky\ServiceProvider;

class ControllersServiceProvider extends ServiceProvider
{
    /**
     * @Factory(name="root_url")
     */
    public static function getRootUrl(): string
    {
        return \ROOT_URL;
    }

    /**
     * @Factory(name="userFile")
     */
    public static function getUserfile(): string
    {
        return __DIR__.'/../../../mouf/no_commit/user.php';
    }

    /**
     * @Factory(name="moufInstallTemplate")
     */
    public static function aliasInstallTemplate(TemplateInterface $template): TemplateInterface
    {
        return $template;
    }

    /**
     * @Factory(name="simpleLoginControllerTemplate")
     */
    public static function aliasSimpleLoginControllerTemplate(ContainerInterface $container): TemplateInterface
    {
        //return $container->get('moufLoginTemplate');
        return $container->get(TemplateInterface::class);
    }

    /**
     * @Factory()
     */
    public static function createRootController(ContainerInterface $container): MoufRootController
    {
        return new MoufRootController($container->get('root_url'));
    }

    /**
     * @Factory()
     */
    public static function createValidatorController(MoufValidatorService $validatorService, TemplateInterface $template, ContainerInterface $container): MoufValidatorController
    {
        return new MoufValidatorController($validatorService, $template, $container->get('block.content'));
    }

    /**
     * @Factory()
     */
    public static function createInstallController(TemplateInterface $template, ContainerInterface $container): MoufInstallController
    {
        return new MoufInstallController($template, $container->get('block.content'));
    }

    /**
     * @Extension(name="thecodingmachine.splash.controllers")
     */
    public static function declareControllers(array $controllers): array
    {
        $controllers[] = MoufRootController::class;
        $controllers[] = MoufValidatorController::class;
        $controllers[] = MoufInstallController::class;
        return $controllers;
    }
}
