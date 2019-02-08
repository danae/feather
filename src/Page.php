<?php
namespace Feather;

use Feather\Renderer\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

class Page
{
  // Variables
  public $path;
  public $options;

  // Variables derived from options
  public $visible;
  public $default;
  public $title;

  // Constyructor
  public function __construct(string $path, array $options = [])
  {
    $this->path = $path;
    $this->options = $options;

    $this->visible = $options['visible'] ?? true;
    $this->default = $options['default'] ?? false;
    $this->title = $options['title'] ?? ucwords($this->path);
  }

  // Render this page including the specified context
  public function render(RendererInterface $renderer, array $context = []): Response
  {
    return $renderer->render($this, $context);
  }
}
