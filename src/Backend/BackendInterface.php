<?php
namespace Feather\Backend;

use Feather\Page;
use Symfony\Component\HttpFoundation\Response;

interface BackendInterface
{
  // Return the contents of a page
  public function getContents(Page $page): string;

  // Set the contents of a page
  public function setContents(Page $page, string $contents);

  // Render a page
  public function render(Page $page, array $variables = []): Response;
}
