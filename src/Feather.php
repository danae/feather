<?php
namespace Feather;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment as TwigEnvironment;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;
use Twig\Loader\LoaderInterface as TwigLoaderInterface;

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
    $builder->addDefinitions($options);

    // Add service definitions
    $builder->addDefinitions([
      // Variables
      'base_path' => function() {
        $dir = dirname($_SERVER['PHP_SELF']);
        return ($dir !== '/' ? $dir : '');
      },

      // Twig extensions
      'twig.base_path' => function(ContainerInterface $c) {
        return new TwigFunction('base_path', function() use ($c) {
          return $c->get('base_path');
        });
      },
      'twig.base_path_for' => function(ContainerInterface $c) {
        return new TwigFunction('base_path_for', function(Page $page) use ($c) {
          return $c->get('base_path') . '/' . $page->path;
        });
      },

      // Twig services
      TwigLoaderInterface::class => autowire(TwigFilesystemLoader::class)
        ->constructorParameter('paths', 'templates'),
      TwigEnvironment::class => autowire()
        ->constructorParameter('options', ['autoescape' => false])
        ->method('addFunction', get('twig.base_path'))
        ->method('addFunction', get('twig.base_path_for')),

      // Feather services
      PageManager::class => autowire(),

      // Convenient names to access the services
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

  // Return a response with the rendered content of a page
  private function render(Page $page, array $context = [])
  {
    // Add the container to the context
    $context['pages'] = $this->container->get(PageManager::class);
    $context['page'] = $page;

    $context = array_merge($this->context, $context);

    // Render the page using the Twig environment
    try
    {
      return new Response($this->container->get(TwigEnvironment::class)->render($page->template . ".twig", $context));
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
      $page = $path ? $this->container->get(PageManager::class)->get($path) : $this->container->get(PageManager::class)->getDefault();
      if ($page === null)
        throw new NotFoundHttpException($path);

      // Render the page
      return $this->render($page);
    }
    catch (NotFoundHttpException $ex)
    {
      // Check if we have a 404 page
      if (($notFoundPage = $this->container->get(PageManager::class)->getNotFoundPage()) !== null)
        return $this->render($notFoundPage, ['error' => $ex->getMessage()]);
      elseif ($catch)
        return new Response("A not found error occurred while processing your request: '{$ex->getMessage()}'", 404);
      else
        throw $ex;
    }
    catch (Exception $ex)
    {
      // Check if we have a 500 page
      if (($errorPage = $this->container->get(PageManager::class)->getErrorPage()) !== null)
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
