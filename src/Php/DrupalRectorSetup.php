<?php

namespace Mediatis\DrupalCodingStandards\Php;

use DrupalRector\Set\Drupal10SetList;
use Mediatis\CodingStandards\Php\RectorSetup;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

class DrupalRectorSetup extends RectorSetup
{
    protected static int $drupalVersion;

    /**
     * @return string[]
     */
    protected static function paths(string $packagePath): array
    {
        return [
            $packagePath,
        ];
    }

    /**
     * @return array<string>
     */
    protected static function sets(): array
    {
        $sets = parent::sets();

        // Use the lowest supported Drupal version's set for compatibility.
        // palantirnet/drupal-rector currently only provides sets up to Drupal 10.
        $sets[] = Drupal10SetList::DRUPAL_10;

        return array_unique($sets);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function skip(string $packagePath): array
    {
        $criteria = parent::skip($packagePath);
        $drupalCriteria = [
            $packagePath . '/**/node_modules/*',
            $packagePath . '/vendor/*',
            $packagePath . '/.Build/*',
        ];
        foreach ($drupalCriteria as $value) {
            $criteria[] = $value;
        }

        return $criteria;
    }

    public static function setup(RectorConfig $rectorConfig, string $packagePath, int $drupalVersion = 10, int $phpVersion = PhpVersion::PHP_82): void
    {
        static::$drupalVersion = $drupalVersion;
        static::$phpVersion = $phpVersion;
        parent::setup($rectorConfig, $packagePath, $phpVersion);

        // Add Drupal-specific file extensions
        $rectorConfig->fileExtensions(['php', 'module', 'theme', 'install', 'profile', 'inc', 'engine']);
    }
}
