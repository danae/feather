<?php
namespace Feather\Renderer;

use Feather\Page;
use Symfony\Component\HttpFoundation\Response;

interface RendererInterface
{
  // Render a page including the specified context
  public function render(Page $page, array $context = []): Response;
}
