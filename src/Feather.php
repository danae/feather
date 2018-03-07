<?php
namespace Feather;

use Exception;
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
  private $defaultPage;
  private $errorPage;
  private $notFoundPage;
  
  private $twigLoader;
  private $twigEnvironment;
  
  // Constructor
  public function __construct(string $path)
  {
    // Initialize variables
    $this->pages = [];
    $this->defaultPage = null;
    $this->errorPage = null;
    $this->notFoundPage = null;
    
    // Initialize Twig
    $this->twigLoader = new Twig_Loader_Filesystem($path);
    $this->twigEnvironment = new Twig_Environment($this->twigLoader,['autoescape' => false]);
  }
  
  // Run
  public function run(array $variables = [])
  {
    // Set the default page if none given
    if (!$this->defaultPage)
      $this->defaultPage = array_values($this->pages)[0];
    
    // Create the routes
    $router = new Router();
    $router->addRoute('/{page}',function($page) use($variables) { 
      try
      {
        return $this->renderPageByName($page,$variables); 
      }
      catch (NotFoundHttpException $ex)
      {
        if ($this->notFoundPage !== null)
          return $this->renderPage($this->notFoundPage,$variables);
        else
          throw $ex;
      }
      catch (Exception $ex)
      {
        $variables = array_merge($variables,[
          'error' => $ex->getMessage()
        ]);
        
        if ($this->errorPage !== null)
          return $this->renderPage($this->errorPage,$variables);
        else
          throw $ex;
      }
    },['page' => $this->defaultPage->name]);   
    
    // Create the request
    $request = Request::createFromGlobals();
    
    // Handle the request
    $response = $router->handle($request);
    $response->send();
    
    // Terminate the kernel
    $router->terminate($request,$response);
  }
  
  // Render a page
  private function renderPage(Page $page, array $variables = [])
  {
    try
    {
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
      throw new NotFoundHttpException($ex->getMessage(),$ex);
    }
  }
  
  // Get a page and render it
  private function renderPageByName(string $name, array $variables = [])
  {
    // Get the page
    $page = $this->pages[$name];
    if (!$page)
      throw new NotFoundHttpException($name);
      
    // Render the page
    return $this->renderPage($page,$variables);
  }
  
  // Add a page
  public function addPage(Page $page, bool $default = false)
  {
    // Add the page
    $this->pages[$page->name] = $page;
    
    // Set the page as default if set
    if ($default)
      $this->defaultPage = $page;
    
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
  
  // Set the 500 page
  public function setErrorPage(Page $page)
  {
        // Set the error page
    $this->errorPage = $page;
    
    // Return self for chainability
    return $this;
  }
  
  // Set the 404 page
  public function setNotFoundPage(Page $page)
  {
    // Set the not found page
    $this->notFoundPage = $page;
    
    // Return self for chainability
    return $this;
  }
}
