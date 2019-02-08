<?php
namespace Feather;

use ArrayAccess;
use DI\ContainerBuilder;
use Feather\Backend\Backend;
use Feather\Backend\FilesystemBackend;
use Feather\Renderer\{RendererInterface, TwigRenderer};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_LoaderInterface;

use function DI\{autowire, get};

class Feather extends PageManager implements HttpKernelInterface, ArrayAccess
{
  // Variables
  private $container;

  // Constructor
  public function __construct(array $values = [])
  {
    // Create a container builder
    $builder = new ContainerBuilder();

    // Add default definitions
    $builder->addDefinitions([
      // Variables
      'document_root' => function() {
        $dir = dirname($_SERVER['PHP_SELF']);
        return ($dir !== '/' ? $dir : '');
      },

      // Twig services
      Twig_LoaderInterface::class => autowire(Twig_Loader_Filesystem::class)
        ->constructorParameter('paths', 'templates'),
      Twig_Environment::class => autowire()
        ->constructorParameter('options', ['autoescape' => false]),

      // Feather services
      PageManager::class => $this,
      RendererInterface::class => autowire(TwigRenderer::class),

      // Convenient names to access the services
      'pages' => get(PageManager::class),
      'renderer' => get(RendererInterface::class),

      // Context
      'context' => []
    ]);

    // Add definitions from constructor
    $builder->addDefinitions($values);

    // Create the container
    $this->container = $builder->build();
  }

  // Array access methods
  public function offsetExists($offset)
  {
    return $this->container->has($offset);
  }
  public function offsetGet($offset)
  {
    return $this->container->get($offset);
  }
  public function offsetSet($offset, $value)
  {
    $this->container->set($offset, $value);
  }
  public function offsetUnset($offset)
  {
    throw new \LogicException(Container::class . " does not support unsetting keys");
  }

  // Render a page including the specified context
  private function render(Page $page)
  {
    // Add the container to the context
    $variables = array_merge($this['context'], [
      'document_root' => $this['document_root'],
      'pages' => $this['pages'],
      'renderer' => $this['renderer']
    ]);

    // Render the page using the renderer
    return $this[RendererInterface::class]->render($page, $variables);
    //return $this->backend->render($page,$variables);
  }

  // Handle a request
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
  {
    try
    {
      // Get the path of the requested page
      $path = implode('/', array_filter(explode('/', $request->getPathInfo())));

      // Get the page or use the default page if the path is empty
      $page = $path ? $this->pages[$path] : $this->defaultPage;
      if ($page === null)
        throw new NotFoundHttpException($path);

      // Render the page
      return $this->render($page);
    }
    catch (NotFoundHttpException $ex)
    {
      // Check if we have a 404 page
      if ($this->notFoundPage !== null)
        return $this->render($this->notFoundPage);
      else
        throw $ex;
    }
    catch (Exception $ex)
    {
      // Update the variables with the error message
      $variables = array_merge($variables,[
        'error' => $ex->getMessage()
      ]);

      // Check if we have a 500 page
      if ($this->errorPage !== null)
        return $this->render($this->errorPage);
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
    $response = $this->handle($request, HttpKernelInterface::MASTER_REQUEST, true);
    $response->send();
  }
}
