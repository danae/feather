<?php
namespace Feather\Router;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
  // Variables
  private $routes;
  private $kernel;
  
  // Constructor
  public function __construct()
  {
    $this->routes = new RouteCollection();
  }
  
  // Add a route
  public function addRoute(string $path, callable $fn)
  {
    $this->routes->add($path,new Route($path,['_controller' => $fn]));
  }
  
  // Remove a route
  public function removeRoute(string $path)
  {
    $this->routes->remove($path);
  }
  
  // Call a kernel function
  public function __call(string $name, array $args)
  {
    // Create the kernel if not yet created
    if (!$this->kernel)
    {
      $matcher = new UrlMatcher($this->routes,new RequestContext());
      
      $dispatcher = new EventDispatcher();
      $dispatcher->addSubscriber(new RouterListener($matcher,new RequestStack()));
      
      $this->kernel = new HttpKernel($dispatcher,new ControllerResolver(),new RequestStack(),new ArgumentResolver());
    }
    
    // Execute the kernel function
    return call_user_func_array([$this->kernel,$name],$args);
  }
}
