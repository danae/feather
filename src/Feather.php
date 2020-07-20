<?php
namespace Danae\Feather;

use DI\ContainerBuilder;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Loader\LoaderInterface as TwigLoaderInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader as TwigContainerRuntimeLoader;

use function DI\{autowire, get};

class Feather implements HttpKernelInterface
{
  // Variables
  private $container;


  // Constructor
  public function __construct(array $options = [])
  {
    // Create a container builder
    $builder = new ContainerBuilder();

    // Add options
    $options['template_paths'] = $options['template_paths'] ?? 'templates';
    $options['template_format'] = $options['template_format'] ?? '%s.twig';
    $builder->addDefinitions($options);

    // Add service definitions
    $builder->addDefinitions([
      // Variables
      'base_path' => function() {
        $dir = dirname($_SERVER['PHP_SELF']);
        return ($dir !== '/' ? $dir : '');
      },

      // Twig services
      TwigLoaderInterface::class => autowire(TwigFilesystemLoader::class)
        ->constructorParameter('paths', get('template_paths')),
      TwigEnvironment::class => autowire()
        ->constructorParameter('options', ['autoescape' => false])
        ->method('addRuntimeLoader', get(TwigContainerRuntimeLoader::class))
        ->method('addExtension', get(FeatherTwigExtension::class)),
      Feather::class => $this,

      // Convenient names to access the services
      'twig_loader' => get(TwigLoaderInterface::class),
      'twig' => get(TwigEnvironment::class),
      'pages' => get(PageManager::class),

      // Context
      'context' => []
    ]);

    // Create the container
    $this->container = $builder->build();
  }

  // Property overloading for easy container access
  public function __isset($name)
  {
    return $this->container->has($offset);
  }
  public function __get($name)
  {
    return $this->container->get($name);
  }
  public function __set($name, $value)
  {
    $this->container->set($name, $value);
  }

  // Return the base path
  public function getBasePath(): string
  {
    return $this->get('base_path');
  }

  // Return the base path for a page
  public function getBasePathFor($page): string
  {
    if (is_a($page, Page::class))
    {
      // Return path from the page
      return $this->base_path . '/' . $page->path;
    }
    else if (is_string($page))
    {
      // Get the page
      $page = $this->pages->get($page);

      // Return path from the page
      if ($page != null)
        return $this->base_path . '/' . $page->path;
      else
        return '';
    }
    else
    {
      // Invalid argumant
      throw new InvalidArgumentException("The 'page' argument must either be a " . Page::class . " or a string");
    }
  }

  // Return a response with the rendered content of a page
  private function render(Page $page, array $context = [])
  {
    // Add the container to the context
    $context['pages'] = $this->pages;
    $context['page'] = $page;

    $context = array_merge($this->context, $context);

    // Render the page using the Twig environment
    try
    {
      return new Response($this->twig->render(sprintf($this->template_format, $page->template), $context));
    }
    catch (TwigLoaderError $ex)
    {
      throw new NotFoundHttpException($ex->getMessage(), $ex);
    }
  }

  // Handle a request
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
  {
    try
    {
      // Get the path of the requested page
      $path = implode('/', array_filter(explode('/', $request->getPathInfo())));

      // Get the page or use the default page if the path is empty
      $page = $path ? $this->pages->get($path) : $this->pages->getDefault();
      if ($page === null)
        throw new NotFoundHttpException($path);

      // Render the page
      return $this->render($page);
    }
    catch (NotFoundHttpException $ex)
    {
      // Check if we have a 404 page
      if (($notFoundPage = $this->pages->getNotFoundPage()) !== null)
        return $this->render($notFoundPage, ['error' => $ex->getMessage()]);
      elseif ($catch)
        return new Response("A not found error occurred while processing your request: '{$ex->getMessage()}'", 404);
      else
        throw $ex;
    }
    catch (Exception $ex)
    {
      // Check if we have a 500 page
      if (($errorPage = $this->pages->getErrorPage()) !== null)
        return $this->render($errorPage, ['error' => $ex->getMessage()]);
      elseif ($catch)
        return new Response("An internal error occurred while processing your request: '{$ex->getMessage()}'", 500);
      else
        throw $ex;
    }
  }

  // Run
  public function run($request = null)
  {
    // Create the request
    if ($request == null)
      $request = Request::createFromGlobals();

    // Handle the request
    $response = $this->handle($request, HttpKernelInterface::MASTER_REQUEST);
    $response->send();
  }
}
