<?php
namespace Feather\Renderer;

use Feather\Page;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\{HttpException, NotFoundHttpException};
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error as TwigError;
use Twig\Error\LoaderError as TwigLoaderError;

class TwigRenderer implements RendererInterface
{
  // Variables
  protected $environment;

  // Constructor
  public function __construct(TwigEnvironment $environment)
  {
    $this->environment = $environment;
  }

  // Render a page including the specified context
  public function render(Page $page, array $variables = []): Response
  {
    // Add the page to the variables
    $variables = array_merge($variables, [
      'page' => $page
    ]);

    // Render the page using the twig environment
    try
    {
      return new Response($this->environment->render($page->path . ".twig", $variables));
    }
    catch (TwigLoaderError $ex)
    {
      throw new NotFoundHttpException($ex->getMessage(), $ex);
    }
    catch (TwigError $ex)
    {
      throw new HttpException(500, $ex->getMessage(), $ex);
    }
  }
}
