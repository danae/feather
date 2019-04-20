<?php
require("../vendor/autoload.php");

use Database\Database;
use Feather\Backend\DatabaseBackend;
use Feather\Feather;
use Feather\Page;

// Create the context
$backend = new DatabaseBackend(new Database('mysql:host=cerise.het.is;dbname=feather','feather','1ZybBI7BWS0zaXRO'));

// Create the editor
$editor = new Feather('assets/templates');
$editor->addPage(new Page('index'),true);
$editor->run([
  'root' => $editor->rootPath(),
  'assets' => $editor->rootPath() . '/assets',
  'backend' => $backend
]);