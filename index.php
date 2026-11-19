<?php
/**
 * InfinityFree serves htdocs as the web root.
 * Keep the original Laravel front controller in public/index.php.
 * This tiny file lets the hosting panel find an index.php in htdocs.
 */
require __DIR__.'/public/index.php';
