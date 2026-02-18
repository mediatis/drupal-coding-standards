<?php

declare(strict_types=1);

use Mediatis\DrupalCodingStandards\Php\DrupalRectorSetup;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    DrupalRectorSetup::setup($rectorConfig, __DIR__, DRUPAL_VERSION_PLACEHOLDER, PhpVersion::PHP_VERSION_PLACEHOLDER);
};
