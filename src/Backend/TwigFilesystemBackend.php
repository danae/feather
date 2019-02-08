<?php
namespace Feather\Backend;

use BadMethodCallException;
use Feather\Page;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class TwigFilesystemBackend implements Backend
{
  // Variables
  private $twigLoader;
  private $twigEnvironment;

  // Constructor
  public function __construct(string $path)
  {
    // Initialize the loader
    $this->twigLoader = new FilesystemLoader($path);

    // Initialize the environment
    $this->twigEnvironment = new Environment($this->twigLoader, ['autoescape' => false]);
  }

  // Get the contents of a page
  public function getContents(Page $page): string
  {
    return $this->twigLoader->getSourceContext($page->template)->getCode();
  }

  // Set the contents of a page
  public function setContents(Page $page, string $contents)
  {
    throw new BadMethodCallException(__FUNCTION__ . " is unsupported in class " . __CLASS__);
  }

  // Render a page
  public function render(Page $page, array $variables = []): Response
  {
    try
    {
      return new Response($this->twigEnvironment->render($page->template,$variables));
    }
    catch (LoaderError $ex)
    {
      throw new NotFoundHttpException($ex->getMessage(),$ex);
    }
  }
}
