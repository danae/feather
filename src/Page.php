<?php
namespace Feather;

class Page
{
  // Variables
  public $path;
  public $template;
  public $title;
  public $visible;

  // Constructor
  public function __construct(array $options = [])
  {
    $this->path = $options['path'];
    $this->template = $options['template'] ?? implode('/', array_filter(explode('/', $this->path)));
    $this->title = $options['title'] ?? ucwords($this->path);
    $this->visible = $options['visible'] ?? true;
    $this->default = $options['default'] ?? false;
  }
}
