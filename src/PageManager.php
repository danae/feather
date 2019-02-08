<?php
namespace Feather;

use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class PageManager implements IteratorAggregate
{
  // Variables
  public $pages;

  protected $defaultPage;
  protected $errorPage;
  protected $notFoundPage;

  // Constructor
  public function __construct()
  {
    $this->pages = [];

    $this->defaultPage = null;
    $this->errorPage = null;
    $this->notFoundPage = null;
  }

  // Create a page
  private function create(string $path, $page_or_options = null): Page
  {
    if ($page_or_options === null)
      return new Page($path);
    else if (is_array($page_or_options))
      return new Page($path, $page_or_options);
    else if (is_a($page_or_options, Page::class))
      return $page_or_options;
    else
      throw new InvalidArgumentException('Second argument must either be an instance of ' . Page::class . ', an array containing page options or null');
  }

  // Add a page
  public function add(string $path, $page_or_options = null): self
  {
    // Create and add the page
    $page = $this->create($path, $page_or_options);
    $this->pages[$path] = $page;

    // Set the page as default if set
    if ($page->default)
      $this->defaultPage = $page;

    // Return self for chainability
    return $this;
  }

  // Remove a page
  public function remove(string $pageName): self
  {
    // Remove the page
    unset($this->pages[$pageName]);

    // Return self for chainability
    return $this;
  }

  // Add a page that serves as a general error handler
  public function errorPage($path, $page_or_options = null): self
  {
    $this->errorPage = $this->create($path, $page_or_options);
    return $this;
  }

  // Add a page that serves as a not found page
  public function notFoundPage($path, $page_or_options = null): self
  {
    $this->notFoundPage = $this->create($path, $page_or_options);
    return $this;
  }

  // Return an iterator over the visible pages
  public function getIterator(): Traversable
  {
    foreach ($this->pages as $path => $page)
    {
      if ($page->visible)
        yield $path => $page;
    }
  }
}
