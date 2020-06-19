<?php
namespace Feather;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatherTwigExtension extends AbstractExtension
{
  // Return the name of the extension
  public function getName(): string
  {
    return 'feather';
  }

  // Return the function definitions
  public function getFunctions(): array
  {
    return [
      new TwigFunction('base_path', [Feather::class, 'getBasePath']),
      new TwigFunction('base_path_for', [Feather::class, 'getBasePathFor'])
    ];
  }
}
