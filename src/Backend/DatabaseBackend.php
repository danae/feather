<?php
namespace Feather\Backend;

use Database\Database;
use Feather\Backend\Loader\DatabaseTwigLoader;
use Feather\Page;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig_Environment;
use Twig_Error_Loader;

class DatabaseBackend implements Backend
{
  // Variables
  private $twigLoader;
  private $twigEnvironment;
  
  // Constructor
  public function __construct(Database $database, string $table = 'templates')
  {
    // Initialize the loader
    $this->twigLoader = new DatabaseTwigLoader($database,$table);
    
    // Initialize the environment
    $this->twigEnvironment = new Twig_Environment($this->twigLoader,['autoescape' => false]);
  }

  // Return the contents of a page
  public function getContents(Page $page): string
  {
    if (($template = $this->twigLoader->getTemplate($page->template)) === null)
      throw new Twig_Error_Loader($page->template);
    
    return $template['source'];
  }

  // Set the contents of a page
  public function setContents(Page $page, string $contents)
  {
    $template = [
      'name' => $page->template,
      'source' => $contents,
      'last_modified' => time()
    ];
    
    $this->twigLoader->setTemplate($template);
  }
  
  // Render a page
  public function render(Page $page, array $variables = array()): Response
  {
    try
    {
      return new Response($this->twigEnvironment->render($page->template,$variables));
    }
    catch (Twig_Error_Loader $ex)
    {
      throw new NotFoundHttpException($ex->getMessage(),$ex);
    }
  }
}
