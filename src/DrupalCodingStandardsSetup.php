<?php

namespace Mediatis\DrupalCodingStandards;

use Exception;
use Mediatis\CodingStandards\CodingStandardsSetup;

class DrupalCodingStandardsSetup extends CodingStandardsSetup
{
    public function setup(): void
    {
        parent::setup();
        $this->setupPhpcsConfig();
    }

    protected function setupPhpcsConfig(): void
    {
        $this->updateFile('phpcs.xml', keepExistingFile: true);
    }

    protected function setupRectorConfig(): void
    {
        $phpVersions = $this->getDependencyVersionConstraintsFromComposerData('php', '');
        $phpVersion = match ($phpVersions[0]) {
            8.2 => 'PHP_82',
            8.3 => 'PHP_83',
            default => throw new Exception('Unable to set up rector due to version mismatch. Supported PHP versions are: ' . implode(', ', $this->supportedPackageVersions['php']['versions'])),
        };
        $drupalVersions = $this->getDependencyVersionConstraintsFromComposerData('drupal', 'major');
        if ($drupalVersions !== []) {
            $this->updateFile('rector.php', config: [
                'DRUPAL_VERSION_PLACEHOLDER' => $drupalVersions[0],
                'PHP_VERSION_PLACEHOLDER' => $phpVersion,
            ]);
        } else {
            throw new Exception('Unable to set up rector due to version mismatch. Supported Drupal versions are: ' . implode(', ', $this->supportedPackageVersions['drupal']['versions']));
        }
    }

    public function reset(): void
    {
        parent::reset();
        $this->resetFile('phpcs.xml');
    }
}
