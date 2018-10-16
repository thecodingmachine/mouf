<?php


namespace Mouf\ServiceProviders;


use Interop\Container\Factories\Alias;
use Interop\Container\ServiceProviderInterface;
use Mouf\Controllers\MoufConfigureLocalUrlController;
use Mouf\Controllers\MoufController;
use Mouf\Controllers\MoufInstallController;
use Mouf\Controllers\MoufRootController;
use Mouf\Controllers\MoufValidatorController;
use Mouf\Controllers\PhpInfoController;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\Utils\WebLibraryManager\InlineWebLibrary;
use Mouf\Html\Utils\WebLibraryManager\WebLibrary;
use Mouf\Security\Controllers\SimpleLoginController;
use Mouf\Security\UserFileDao\UserFileDao;
use Mouf\Security\UserService\UserServiceInterface;
use Mouf\Validator\MoufValidatorService;
use Psr\Container\ContainerInterface;
use TheCodingMachine\Funky\Annotations\Extension;
use TheCodingMachine\Funky\Annotations\Factory;
use TheCodingMachine\Funky\ServiceProvider;
use TheCodingMachine\Funky\Annotations\Tag;

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
        return '../../../mouf/no_commit/user.php';
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
    public static function createInstallController(TemplateInterface $template, ContainerInterface $container, UserFileDao $userFileDao): MoufInstallController
    {
        return new MoufInstallController($template, $container->get('block.content'), $userFileDao);
    }

    /**
     * @Factory()
     */
    public static function createConfigureLocalUrlController(MoufValidatorService $validatorService, TemplateInterface $template, ContainerInterface $container): MoufConfigureLocalUrlController
    {
        return new MoufConfigureLocalUrlController($template, $container->get('block.content'));
    }

    /**
     * @Factory()
     */
    public static function createMoufController(MoufValidatorService $validatorService, TemplateInterface $template, ContainerInterface $container): MoufController
    {
        return new MoufController($template, $container->get('block.content'));
    }

    /**
     * @Factory()
     */
    public static function createPhpInfoController(): PhpInfoController
    {
        return new PhpInfoController();
    }

    /**
     * @Extension(name="thecodingmachine.splash.controllers")
     */
    public static function declareControllers(array $controllers): array
    {
        $controllers[] = MoufRootController::class;
        $controllers[] = MoufValidatorController::class;
        $controllers[] = MoufInstallController::class;
        $controllers[] = PhpInfoController::class;
        $controllers[] = MoufConfigureLocalUrlController::class;
        $controllers[] = MoufController::class;
        return $controllers;
    }

    /**
     * @Extension(name="block.header")
     */
    public static function registerNavBarInHeader(HtmlBlock $block, ContainerInterface $container): HtmlBlock
    {
        $block->addHtmlElement($container->get('navBar'));
        return $block;
    }

    /**
     * @Factory(name="mouf.weblibrary", tags={@Tag(name="webLibraries", priority=0.0)})
     */
    public static function createWebLibrary(): WebLibrary
    {
        return new WebLibrary([
            'src-dev/views/instances/messages.js',
            'src-dev/views/instances/utils.js',
            'src-dev/views/instances/instances.js',
            'src-dev/views/instances/defaultRenderer.js',
            'src-dev/views/instances/moufui.js',
            'src-dev/views/instances/saveManager.js',
            'src-dev/views/instances/jquery.scrollintoview.js',
            'src-dev/views/instances/codeValidator.js',
        ],
        [
            'src-dev/views/instances/defaultRenderer.css',
            'src-dev/views/styles.css'
        ]);
    }

    /**
     * @Factory(name="mouf.setRootUrl.weblibrary", tags={@Tag(name="webLibraries", priority=10.0)})
     */
    public static function createSetRootUrlWebLibrary(ContainerInterface $container): InlineWebLibrary
    {
        return new InlineWebLibrary(null, null, $container->get('rootUrlJsFile'));
    }
}
