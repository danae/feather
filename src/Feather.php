<?php
namespace Feather;

use Feather\Backend\Backend;
use Feather\Backend\FilesystemBackend;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Feather implements HttpKernelInterface
{
  // Variables
  private $backend;
  private $pages;
  private $defaultPage;
  private $errorPage;
  private $notFoundPage;
  
  // Constructor
  public function __construct($backend_or_path)
  {
    // Initialize the backend
    if (is_subclass_of($backend_or_path,Backend::class))
      $this->backend = $backend_or_path;
    elseif (is_string($backend_or_path))
      $this->backend = new FilesystemBackend($backend_or_path);
    else
      throw new InvalidArgumentException("The backend must be an instance of " . Backend::class . " or a string to initialize a " . FilesystemBackend::class);
    
    // Initialize variables
    $this->pages = [];
    $this->defaultPage = null;
    $this->notFoundPage = null;
    $this->errorPage = null;
  }
  
  // Return the backend
  public function getBackend()
  {
    return $this->backend;
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
  
  // Return the not found page
  public function getNotFoundPage()
  {
    return $this->notFoundPage;
  }
  
  // Set the not found page
  public function setNotFoundPage(Page $notFoundPage)
  {
    // Set the not found page
    $this->notFoundPage = $notFoundPage;
    
    // Return self for chainability
    return $this;
  }
  
  // Return the error page
  public function getErrorPage()
  {
    return $this->errorPage;
  }
  
  // Set the error page
  public function setErrorPage(Page $errorPage)
  {
    // Set the error page
    $this->errorPage = $errorPage;
    
    // Return self for chainability
    return $this;
  }
  
  // Render a page
  private function render(Page $page, array $variables = [])
  {
    // Add the page variables
    $variables = array_merge($variables,[
      'pages' => $this->pages,
      'page' => $page
    ]);
    
    // Render the page using the backend
    return $this->backend->render($page,$variables);
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
      $variables = array_merge($variables,[
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
    return ($dir !== '/' ? $dir : '');
  }
}
