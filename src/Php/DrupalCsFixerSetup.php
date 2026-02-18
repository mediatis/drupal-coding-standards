<?php

namespace Mediatis\DrupalCodingStandards\Php;

use Mediatis\CodingStandards\Php\CsFixerSetup;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

class DrupalCsFixerSetup
{
    public static function create(): Config
    {
        $config = CsFixerSetup::setup();

        $finder = $config->getFinder();
        if ($finder instanceof Finder) {
            // Add Drupal-specific file extensions
            $finder->name('*.module')
                ->name('*.theme')
                ->name('*.install')
                ->name('*.profile')
                ->name('*.inc');
        }

        return $config;
    }
}
