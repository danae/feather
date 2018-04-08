<?php
namespace Feather;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Loader_Filesystem;

class Feather implements HttpKernelInterface
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
    $this->notFoundPage = null;
    $this->errorPage = null;
    
    // Initialize Twig
    $this->twigLoader = new Twig_Loader_Filesystem($path);
    $this->twigEnvironment = new Twig_Environment($this->twigLoader,['autoescape' => false]);
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
  
    // Set the 404 page
  public function setNotFoundPage(Page $page)
  {
    // Set the not found page
    $this->notFoundPage = $page;
    
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
  
  // Render a page
  private function render(Page $page, array $variables = [])
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
  
  // Handle a request
  public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true, array $variables = [])
  {
    try
    {
      // Get the path of the page
      $pageArray = array_filter(explode('/',$request->getPathInfo()));
      $pageName = implode('.',$pageArray);

      // Get the page or use the default page if the path is empty
      $page = $pageName ? $this->pages[$pageName] : $this->defaultPage; 
      if ($page === NULL)
        throw new NotFoundHttpException($pageName);
      
      // Render the page
      return $this->render($page,$variables);
    }
    catch (NotFoundHttpException $ex)
    {
      // Check if we have a 404 page
      if ($this->notFoundPage !== null)
        return $this->render($this->notFoundPage,$variables);
      else
        throw $ex;
    }
    catch (Exception $ex)
    {
      // Update the variables with the error message
      $this->variables = array_merge($this->variables,[
        'error' => $ex->getMessage()
      ]);
      
      // Check if we have a 500 page
      if ($this->errorPage !== null)
        return $this->render($this->errorPage,$variables);
      else
        throw $ex;
    }
  }
  
  // Run
  public function run(array $variables = [])
  {
    // Set the default page if none given
    if (!$this->defaultPage)
      $this->defaultPage = array_values($this->pages)[0];
    
    // Create the request
    $request = Request::createFromGlobals();
    
    // Handle the request
    $response = $this->handle($request,HttpKernelInterface::MASTER_REQUEST,true,$variables);
    $response->send();
  }
  
  // Return the root path
  public static function rootPath()
  {
    $dir = dirname($_SERVER['PHP_SELF']);
    return $dir . ($dir !== '/' ? '/' : '');
  }
}
