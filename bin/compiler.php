#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$bytes = file_get_contents('php://stdin');
\assert(\is_string($bytes) && $bytes !== '');

exit(0);
