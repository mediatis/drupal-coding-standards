<?php

namespace Mediatis\DrupalCodingStandards;

use Exception;
use Mediatis\CodingStandards\CodingStandardsSetup;

class DrupalCodingStandardsSetup extends CodingStandardsSetup
{
    /**
     * Minimum PHP version required per Drupal major version.
     *
     * @var array<int,float>
     */
    protected const DRUPAL_MINIMUM_PHP_VERSION = [
        10 => 8.1,
        11 => 8.3,
    ];

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

    protected function setupCiPipeline(): void
    {
        $phpVersions = $this->getDependencyVersionConstraintsFromComposerData('php', '');
        $drupalVersions = $this->getDependencyVersionConstraintsFromComposerData('drupal', 'major');

        // GitLab CI: no native exclude support, so build separate matrix entries
        $gitlabMatrix = $this->buildGitlabMatrix($phpVersions, $drupalVersions);
        $this->updateFile('.gitlab-ci.yml',
            config: [
                'code-quality' => [
                    'parallel' => [
                        'matrix' => $gitlabMatrix,
                    ],
                ],
            ]
        );

        // GitHub Actions: supports exclude in the matrix
        $githubMatrix = $this->buildGithubMatrix($phpVersions, $drupalVersions);
        $this->updateFile('.github/workflows/ci.yml',
            config: [
                'jobs' => [
                    'code-quality' => [
                        'strategy' => [
                            'matrix' => $githubMatrix,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Build a GitHub Actions matrix with exclude entries for incompatible combinations.
     *
     * @param float[] $phpVersions
     * @param int[] $drupalVersions
     *
     * @return array<string,mixed>
     */
    protected function buildGithubMatrix(array $phpVersions, array $drupalVersions): array
    {
        $matrix = [
            'php_version' => $phpVersions,
            'drupal_version' => $drupalVersions,
        ];

        $exclude = [];
        foreach ($drupalVersions as $drupalVersion) {
            $minPhp = static::DRUPAL_MINIMUM_PHP_VERSION[$drupalVersion] ?? null;
            if ($minPhp === null) {
                continue;
            }

            foreach ($phpVersions as $phpVersion) {
                if ($phpVersion < $minPhp) {
                    $exclude[] = [
                        'php_version' => $phpVersion,
                        'drupal_version' => $drupalVersion,
                    ];
                }
            }
        }

        if ($exclude !== []) {
            $matrix['exclude'] = $exclude;
        }

        return $matrix;
    }

    /**
     * Build a GitLab CI matrix with separate entries to avoid incompatible combinations.
     *
     * @param float[] $phpVersions
     * @param int[] $drupalVersions
     *
     * @return array<int,array<string,string[]>>
     */
    protected function buildGitlabMatrix(array $phpVersions, array $drupalVersions): array
    {
        // Group Drupal versions by their compatible PHP versions
        $groups = [];
        foreach ($drupalVersions as $drupalVersion) {
            $minPhp = static::DRUPAL_MINIMUM_PHP_VERSION[$drupalVersion] ?? 0.0;
            $compatiblePhpVersions = array_values(array_filter(
                $phpVersions,
                static fn (float $phpVersion): bool => $phpVersion >= $minPhp,
            ));

            $key = implode(',', $compatiblePhpVersions);
            $groups[$key]['php_versions'] = $compatiblePhpVersions;
            $groups[$key]['drupal_versions'][] = $drupalVersion;
        }

        // Build matrix entries
        $matrix = [];
        foreach ($groups as $group) {
            $matrix[] = [
                'php_version' => array_map(static fn (float $v): string => (string) $v, $group['php_versions']),
                'drupal_version' => array_map(static fn (int $v): string => (string) $v, $group['drupal_versions']),
            ];
        }

        return $matrix;
    }

    public function reset(): void
    {
        parent::reset();
        $this->resetFile('phpcs.xml');
    }
}
