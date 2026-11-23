<?php

declare(strict_types=1);

require_once __DIR__ . '/../scripts/prepare-release.php';

$manifest = file_get_contents(__DIR__ . '/../extension.xml');
$modules = [];
foreach (RELEASE_MODULES as $id) {
    $modules[$id] = file_get_contents(dirname(__DIR__) . "/$id/module.$id.php");
}
$xml = simplexml_load_string($manifest);
$current = trim((string) $xml->version);
[$major, $minor, $patch] = explode('.', $current);
$next = "$major.$minor." . ((int) $patch + 1);
$date = (new DateTimeImmutable((string) $xml->release_date))->modify('+1 day')->format('Y-m-d');

$tests = [
    'prepare the next release with its supplied date and preserve other content' =>
        static function () use ($manifest, $modules, $current, $next, $date): void {
            [$updatedManifest, $updatedModule] = prepareReleaseContents($manifest, $modules, $next, $date);
            $xml = simplexml_load_string($updatedManifest);
            if (releaseMetadataVersion($updatedManifest, $updatedModule) !== $next
                || (string) $xml->modules->module->version !== $next || (string) $xml->release_date !== $date
                || $updatedModule !== str_replace(["'br-riskassessment/$current'", "'br-riskassessment-bridge-for-br-landscape/$current'"],
                    ["'br-riskassessment/$next'", "'br-riskassessment-bridge-for-br-landscape/$next'"], $modules)
                || preg_replace('~<(version|release_date)>.*?</\1>~s', '', $updatedManifest)
                    !== preg_replace('~<(version|release_date)>.*?</\1>~s', '', $manifest)) {
                throw new RuntimeException('Unexpected release metadata or unrelated edits.');
            }
        },
    'default to the preparation day in Europe/Berlin' => static function () use ($manifest, $modules, $next): void {
        $zone = new DateTimeZone('Europe/Berlin');
        $before = (new DateTimeImmutable('now', $zone))->format('Y-m-d');
        [$updated] = prepareReleaseContents($manifest, $modules, $next);
        $after = (new DateTimeImmutable('now', $zone))->format('Y-m-d');
        if (!in_array((string) simplexml_load_string($updated)->release_date, [$before, $after], true)) {
            throw new RuntimeException('Unexpected default release date.');
        }
    },
    'refresh the release date when the branch already carries its target version' =>
        static function () use ($manifest, $modules, $current, $date): void {
            [$updated, $registration] = prepareReleaseContents($manifest, $modules, $current, $date);
            if ((string) simplexml_load_string($updated)->release_date !== $date || $registration !== $modules
                || prepareReleaseContents($updated, $registration, $current, $date) !== [$updated, $registration]) {
                throw new RuntimeException('Refreshing the date must be repeatable.');
            }
        },
];

$cases = [
    'reject malformed version' => [$manifest, $modules, 'v' . $next, $date],
    'reject version with leading zeros' => [$manifest, $modules, '0' . $next, $date],
    'reject a downgrade' => [$manifest, $modules, ((int) $major - 1) . ".$minor.$patch", $date],
    'reject invalid calendar date' => [$manifest, $modules, $next, substr($date, 0, 8) . '32'],
    'reject ambiguous module version' => [$manifest, ['br-riskassessment' => str_repeat($modules['br-riskassessment'], 2)] + $modules, $next, $date],
    'reject mismatched module version' => [$manifest, str_replace("'br-riskassessment/$current'", "'br-riskassessment/$next'", $modules), $next, $date],
    'reject mismatched listed module version' => [
        preg_replace('~(<module><id>br-riskassessment</id><version>).*?(</version>)~s', '${1}' . $next . '$2', $manifest),
        $modules, $next, $date,
    ],
    'reject a missing bridge' => [$manifest, ['br-riskassessment' => $modules['br-riskassessment']], $next, $date],
    'reject missing date element' => [preg_replace('~<release_date>.*?</release_date>~s', '', $manifest), $modules, $next, $date],
    'reject an empty release description' => [
        preg_replace('~<version_description>.*?</version_description>~s', '<version_description/>', $manifest), $modules, $next, $date,
    ],
    'reject invalid release date already in the manifest' => [
        preg_replace('~<release_date>.*?</release_date>~s', '<release_date>invalid</release_date>', $manifest), $modules, $next, $date,
    ],
];
foreach ($cases as $name => $arguments) {
    $tests[$name] = static function () use ($arguments): void {
        try {
            prepareReleaseContents(...$arguments);
        } catch (RuntimeException $error) {
            return;
        }
        throw new RuntimeException('Invalid release input was accepted.');
    };
}

return $tests;
