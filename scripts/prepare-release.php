<?php

declare(strict_types=1);

const RELEASE_MODULES = ['br-riskassessment', 'br-riskassessment-bridge-for-br-landscape'];

function validateReleaseVersion(string $version): void
{
    if (!preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $version)) {
        throw new RuntimeException('Version must use X.Y.Z format without leading zeros.');
    }
}

function validateReleaseDate(string $date): void
{
    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
        throw new RuntimeException('Date must be a valid YYYY-MM-DD date.');
    }
}

function releaseMetadataVersion(string $manifest, array $modules): string
{
    $previous = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($manifest, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || $xml->getName() !== 'extension'
            || count($xml->version) !== 1 || count($xml->release_date) !== 1
            || trim((string) $xml->extension_code) !== 'br-riskassessment') {
            throw new RuntimeException('Expected one version and release_date in the Risk Assessment extension manifest.');
        }
        $currentVersion = trim((string) $xml->version);
        validateReleaseVersion($currentVersion);
        validateReleaseDate(trim((string) $xml->release_date));
        if (count($xml->version_description) !== 1 || trim((string) $xml->version_description) === ''
            || trim((string) $xml->itop_version_min) !== '3.3.0') {
            throw new RuntimeException('Expected a release description and an iTop minimum of 3.3.0.');
        }
        $listed = [];
        foreach ($xml->modules->module as $entry) {
            $id = (string) $entry->id;
            if (isset($listed[$id]) || (string) $entry->version !== $currentVersion) {
                throw new RuntimeException("Duplicate or mismatched module: $id");
            }
            $listed[$id] = true;
        }
        if (array_diff(RELEASE_MODULES, array_keys($listed)) || count($listed) !== count(RELEASE_MODULES)) {
            throw new RuntimeException('The manifest must list the base module and the System Landscape bridge.');
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    foreach (RELEASE_MODULES as $id) {
        $source = $modules[$id] ?? '';
        if (preg_match_all("~'$id/([^']+)'~", $source, $matches) !== 1 || $matches[1][0] !== $currentVersion) {
            throw new RuntimeException("Expected one matching registration for $id.");
        }
        if ($id !== 'br-riskassessment' && substr_count($source, "'br-riskassessment/$currentVersion'") !== 1) {
            throw new RuntimeException("The base module dependency of $id must match its bundled version.");
        }
    }
    return $currentVersion;
}

function prepareReleaseContents(string $manifest, array $modules, string $version, ?string $date = null): array
{
    $date ??= (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d');
    validateReleaseVersion($version);
    validateReleaseDate($date);
    $currentVersion = releaseMetadataVersion($manifest, $modules);
    if (version_compare($version, $currentVersion, '<')) {
        throw new RuntimeException('Cannot prepare an older version than ' . $currentVersion . '.');
    }
    foreach (['version' => $version, 'release_date' => $date] as $element => $value) {
        $manifest = preg_replace_callback(
            '~<' . $element . '>.*?</' . $element . '>~s',
            static fn (): string => "<$element><![CDATA[$value]]></$element>",
            $manifest,
            -1,
            $count
        );
        if ($manifest === null || $count !== ($element === 'version' ? count(RELEASE_MODULES) + 1 : 1)) {
            throw new RuntimeException('Unexpected number of editable ' . $element . ' elements.');
        }
    }
    foreach ($modules as $id => $source) {
        foreach (array_unique([$id, 'br-riskassessment']) as $bundledId) {
            $source = str_replace("'$bundledId/$currentVersion'", "'$bundledId/$version'", $source);
        }
        $modules[$id] = $source;
    }
    return [$manifest, $modules];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $root = dirname(__DIR__);
        $manifest = file_get_contents($root . '/extension.xml');
        $modules = [];
        $paths = [];
        foreach (RELEASE_MODULES as $id) {
            $paths[$id] = "$id/module.$id.php";
            $modules[$id] = file_get_contents($root . '/' . $paths[$id]);
        }
        if (($argv[1] ?? '') === '--check' && $argc <= 3) {
            $version = releaseMetadataVersion($manifest, $modules);
            if (isset($argv[2]) && $argv[2] !== 'release/' . $version) {
                throw new RuntimeException('Release branch must match release/' . $version . '.');
            }
            echo $version . "\n";
            exit(0);
        }
        if ($argc < 2 || $argc > 3) {
            throw new RuntimeException('Usage: php scripts/prepare-release.php X.Y.Z [YYYY-MM-DD]');
        }
        [$newManifest, $newModules] = prepareReleaseContents($manifest, $modules, $argv[1], $argv[2] ?? null);
        $originals = ['extension.xml' => $manifest];
        $updated = ['extension.xml' => $newManifest];
        foreach ($paths as $id => $path) {
            $originals[$path] = $modules[$id];
            $updated[$path] = $newModules[$id];
        }
        foreach (array_keys($updated) as $path) {
            if (!is_writable($root . '/' . $path)) {
                throw new RuntimeException('Cannot write ' . $path);
            }
        }
        try {
            foreach ($updated as $path => $content) {
                if (file_put_contents($root . '/' . $path, $content, LOCK_EX) !== strlen($content)) {
                    throw new RuntimeException('Cannot write ' . $path);
                }
            }
        } catch (Throwable $error) {
            foreach ($originals as $path => $content) {
                if (file_put_contents($root . '/' . $path, $content, LOCK_EX) !== strlen($content)) {
                    fwrite(STDERR, "Could not restore $path; inspect it manually.\n");
                }
            }
            throw $error;
        }
        $date = (string) simplexml_load_string($updated['extension.xml'])->release_date;
        echo "Prepared version {$argv[1]} with release date $date. Running tests...\n";
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tests/run.php'), $status);
        if ($status !== 0) {
            fwrite(STDERR, "Tests failed. Version edits are retained for inspection; do not publish yet.\n");
        }
        exit($status);
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . "\n");
        exit(1);
    }
}
