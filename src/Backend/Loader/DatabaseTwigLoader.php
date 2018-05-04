<?php
namespace Feather\Backend\Loader;

use Database\Database;
use Twig_Error_Loader;
use Twig_LoaderInterface;
use Twig_Source;

class DatabaseTwigLoader implements Twig_LoaderInterface
{
  // Variables
  private $database;
  private $table;
  
  // Constructor
  public function __construct(Database $database, string $table = 'templates')
  {
    $this->database = $database;
    $this->table = $table;
  }
  
  // Create the table
  public function createTable()
  {
    $this->database->exec("CREATE TABLE {$this->table} (name VARCHAR(255) PRIMARY KEY, source VARCHAR(65535), last_modified INT);");
  }
  
  // Return a template array by name
  public function getTemplate(string $name)
  {
    return $this->database->selectOne($this->table,['name' => $name]);
  }
  
  // Set a template array
  public function setTemplate(array $template)
  {
    if ($this->getTemplate($template['name']) === null)
      $this->database->insert($this->table,$template);
    else
      $this->database->update($this->table,$template,['name' => $template['name']]);
  }
  
  // Return the source context for a template name
  public function getSourceContext($name): Twig_Source
  {    
    if (($template = $this->getTemplate($name)) === null)
      throw new Twig_Error_Loader($name);
    
    return new Twig_Source($template['source'],$name);
  }
  
  // Return the cache key for a template name
  public function getCacheKey($name): string
  {
    return $name;
  }

  // Return if the template is still fresh
  public function isFresh($name, $time): bool
  {
    if (($template = $this->getTemplate($name)) === null)
      throw new Twig_Error_Loader($name);
    
    if (!$template['last_modified'])
      return false;
    
    return int($template['last_modified']) <= $time;
  }

  // Return if a template with this name exists
  public function exists($name): bool
  {
    return $this->getTemplate($name) !== null;
  }
}
