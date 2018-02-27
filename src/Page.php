<?php
namespace Feather;

class Page
{
  // Variables
  public $name;
  public $template;
  public $title;
  
  // Constyructor
  public function __construct(string $name, string $template = null, string $title = null)
  {
    $this->name = $name;
    $this->template = $template ?? "{$this->name}.twig";
    $this->title = $title ?? ucwords($this->name);
  }
}
