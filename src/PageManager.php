<?php
namespace Feather;

class PageManager implements \ArrayAccess, \IteratorAggregate
{
  // Constants
  const ERROR_PAGE = '__error';
  const NOT_FOUND_PAGE = '__notfound';

  // Variables
  private $pages = [];

  // Array access
  public function offsetExists($name)
  {
    return isset($this->pages[$name]);
  }
  public function offsetGet($name)
  {
    return $this->pages[$name];
  }
  public function offsetSet($name, $value)
  {
    $this->pages[$name] = $value;
  }
  public function offsetUnset($name)
  {
    unset($this->pages[$name]);
  }

  // Add a page
  public function add(string $path, array $options = []): self
  {
    // Create the default options
    $options['path'] = $path;
    $options['template'] = $options['template'] ?? implode('/', array_filter(explode('/', $path)));
    $options['title'] = $options['title'] ?? ucwords($path);
    $options['visible'] = $options['visible'] ?? true;

    // Create the page
    $this[$path] = new Page($options);

    return $this;
  }

  // Get a page
  public function get(string $path): ?Page
  {
    return $this[$path];
  }

  // Get the default page
  public function getDefault(): Page
  {
    foreach ($this->pages as $page)
      if ($page->default)
        return $page;

    return null;
  }

  // Remove a page
  public function remove(string $path): self
  {
    unset($this[$path]);
    return $this;
  }

  // Add a page that serves as a general error handler
  public function addErrorPage(array $options = []): self
  {
    return $this->add(self::ERROR_PAGE, array_merge($options, ['visible' => false]));
  }

  // Get the error page
  public function getErrorPage(): ?Page
  {
    return $this->get(self::ERROR_PAGE);
  }

  // Add a page that serves as a not found page
  public function addNotFoundPage(array $options = []): self
  {
    return $this->add(self::NOT_FOUND_PAGE, array_merge($options, ['visible' => false]));
  }

  // Get the not found page
  public function getNotFoundPage(): ?Page
  {
    return $this->get(self::NOT_FOUND_PAGE);
  }

  // Return an iterator over the visible pages
  public function getIterator(): \Traversable
  {
    foreach ($this->pages as $page)
      if ($page->visible)
        yield $page;
  }
}
