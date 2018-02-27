<?php
namespace Feather;

use Feather\Router\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Loader_Filesystem;

class Feather
{
  // Variables
  private $pages;
  private $default;
  
  private $twigLoader;
  private $twigEnvironment;
  
  // Constructor
  public function __construct(string $path)
  {
    // Initialize variables
    $this->pages = [];
    $this->default = null;
    
    // Initialize Twig
    $this->twigLoader = new Twig_Loader_Filesystem($path);
    $this->twigEnvironment = new Twig_Environment($this->twigLoader,[
      'autoescape' => false
    ]);
  }
  
  // Run
  public function run(array $variables = [])
  {
    // Set the default page if none given
    if (!$this->default)
      $this->default = array_values($this->pages)[0];
    
    // Create the routes
    $router = new Router();
    $router->addRoute('/{page}',function($page) use($variables) { 
      return $this->page($page,$variables); 
    });   
    
    // Create the request
    $request = Request::createFromGlobals();
    
    // Handle the request
    $response = $router->handle($request);
    $response->send();
    
    // Terminate the kernel
    $router->terminate($request,$response);
  }
  
  // Get a page and render it
  private function page(string $name, array $variables = [])
  {
    try
    {
      // Get the page
      $page = $this->pages[$name] ?? $this->default;
      if (!$page)
        throw new NotFoundHttpException($name);
      
      // Create the Twig context variables
      $variables = array_merge($variables,[
        'pages' => $this->pages,
        'page' => $page
      ]);
              
      // Render the template
      return new Response($this->twigEnvironment->render($page->template,$variables));
    }
    catch (Twig_Error_Loader $ex)
    {
      throw new NotFoundHttpException($name,$ex);
    }
  }
  
  // Add a page
  public function addPage(Page $page, bool $default = false)
  {
    // Add the page
    $this->pages[$page->name] = $page;
    
    // Set the page as default if set
    if ($default)
      $this->default = $page;
    
    // Return self for chainability
    return $this;
  }
  
  // Remove a page
  public function removePage(string $pageName)
  {
    // Remove the page
    unset($this->pages[$pageName]);
    
    // Return self for chainability
    return $this;
  }
}
