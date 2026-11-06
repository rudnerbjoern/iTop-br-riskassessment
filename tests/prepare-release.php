<?php

declare(strict_types=1);

require_once __DIR__ . '/../scripts/prepare-release.php';

$manifest = file_get_contents(__DIR__ . '/../extension.xml');
$module = file_get_contents(__DIR__ . '/../br-riskassessment/module.br-riskassessment.php');
$xml = simplexml_load_string($manifest);
$current = trim((string) $xml->version);
$parts = explode('.', $current);
$next = $parts[0] . '.' . $parts[1] . '.' . ((int) $parts[2] + 1);

$tests = [
    'prepare matching release metadata without changing other content' => static function () use ($manifest, $module, $current, $next): void {
        [$updatedManifest, $updatedModule] = prepareReleaseContents($manifest, $module, $next, '2028-02-29');
        $xml = simplexml_load_string($updatedManifest);
        if ((string) $xml->version !== $next || (string) $xml->release_date !== '2028-02-29'
            || $updatedModule !== str_replace("'br-riskassessment/$current'", "'br-riskassessment/$next'", $module)
            || preg_replace('~<(version|release_date)>.*?</\1>~s', '', $updatedManifest)
                !== preg_replace('~<(version|release_date)>.*?</\1>~s', '', $manifest)) {
            throw new RuntimeException('Unexpected release metadata or unrelated edits.');
        }
    },
];

$tests['preserve compatibility and historical migration versions'] = static function () use ($manifest, $module, $next): void {
    [$updatedManifest, $updatedModule] = prepareReleaseContents($manifest, $module, $next, '2028-02-29');
    if ((string) simplexml_load_string($updatedManifest)->itop_version_min
        !== (string) simplexml_load_string($manifest)->itop_version_min) {
        throw new RuntimeException('The minimum iTop version must remain unchanged.');
    }
    preg_match_all('/version_compare\([^;]+/', $module, $before);
    preg_match_all('/version_compare\([^;]+/', $updatedModule, $after);
    if ($before !== $after) {
        throw new RuntimeException('Historical migration versions must remain unchanged.');
    }
};

$cases = [
    'reject malformed version' => [$manifest, $module, 'v3.2.3', '2028-02-29'],
    'reject version with leading zeros' => [$manifest, $module, '03.2.3', '2028-02-29'],
    'reject unchanged version' => [$manifest, $module, $current, '2028-02-29'],
    'reject older version' => [$manifest, $module, '0.0.0', '2028-02-29'],
    'reject invalid calendar date' => [$manifest, $module, $next, '2027-02-29'],
    'reject ambiguous module version' => [$manifest, $module . $module, $next, '2028-02-29'],
    'reject mismatched module version' => [$manifest, str_replace("'br-riskassessment/$current'", "'br-riskassessment/0.0.0'", $module), $next, '2028-02-29'],
    'reject missing date element' => [preg_replace('~<release_date>.*?</release_date>~s', '', $manifest), $module, $next, '2028-02-29'],
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
