<?php

declare(strict_types=1);

if (getenv('ITOP_TEST_MODE') !== '1') {
    throw new RuntimeException('Run through the isolated iTop integration test runner.');
}
require '/var/www/html/approot.inc.php';
require_once APPROOT . 'core/config.class.inc.php';
$config = new Config(APPROOT . 'conf/production/config-itop.php');
if ([$config->Get('db_host'), $config->Get('db_name'), $config->Get('db_user')]
    !== ['test-db', 'itop_test', 'itop_test']) {
    throw new RuntimeException('Expected the disposable test database.');
}
require APPROOT . 'application/startup.inc.php';
require_once APPROOT . 'setup/moduleinstaller.class.inc.php';
require_once APPROOT . 'setup/modulediscovery.class.inc.php';

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

function auditSet(string $oql, array $params = []): DBObjectSet
{
    return new DBObjectSet(DBObjectSearch::FromOQL($oql, $params));
}

function one(string $oql, array $params = []): DBObject
{
    $set = auditSet($oql, $params);
    check($set->Count() === 1, "Expected exactly one object: $oql");
    return $set->Fetch();
}

function createFixture(string $class, array $values): DBObject
{
    $object = MetaModel::NewObject($class, $values);
    $object->DBInsert();
    return $object;
}

check(UserRights::CheckCredentials('admin', (string) getenv('ITOP_TEST_ADMIN_PASSWORD'))
    && UserRights::Login('admin') && UserRights::IsAdministrator(), 'Cannot log in as test administrator.');
$manifest = simplexml_load_file(dirname(__DIR__, 2) . '/extension.xml');
$version = (string) $manifest->version;
$installed = GetModulesInfo();
foreach (['br-riskassessment', 'itop-config-mgmt', 'itop-portal', 'itop-profiles-itil'] as $module) {
    check(isset($installed[$module]), "Setup did not install $module.");
    $actual = $installed[$module]['version'];
    check($module === 'br-riskassessment' ? $actual === $version
        : version_compare($actual, (string) $manifest->itop_version_min, '>='), "Unexpected $module version: $actual");
}
$reader = Combodo\iTop\Setup\ModuleDiscovery\ModuleFileReader::GetInstance();
$metadata = $reader->ReadModuleFileInformation(dirname(__DIR__, 2) . '/br-riskassessment/module.br-riskassessment.php');
$installer = $reader->GetAndCheckModuleInstallerClass($metadata[$reader::MODULE_INFO_CONFIG]);
check($installer === RiskAssessmentInstaller::class, 'Unexpected installer in setup metadata.');

function installedRules(): array
{
    $category = one('SELECT AuditCategory WHERE name = "Risk Management Mismatch"');
    $domain = one('SELECT AuditDomain WHERE name = "Risk Management"');
    one('SELECT lnkAuditCategoryToAuditDomain WHERE domain_id = :d AND category_id = :c',
        ['d' => $domain->GetKey(), 'c' => $category->GetKey()]);
    $set = auditSet('SELECT AuditRule WHERE category_id = :category', ['category' => $category->GetKey()]);
    check($set->Count() === 5, 'Expected five persisted audit rules.');
    $rules = [];
    while ($rule = $set->Fetch()) {
        check($rule->Get('valid_flag') === 'false', 'Rules must select mismatches.');
        $rules[$rule->Get('name')] = $rule;
    }
    ksort($rules);
    return $rules;
}

// Render the compiled portal form with the same Twig filters used by iTop.
$design = new DOMDocument();
check($design->load(APPROOT . 'env-production/core/module_designs/itop-portal.xml'), 'Missing compiled portal design.');
$layout = (new DOMXPath($design))->query('/module_design/forms/form[@id="applicationsolution-view"]/twig')->item(0);
check($layout !== null, 'Missing application solution form.');
$twig = new Twig\Environment(new Twig\Loader\ArrayLoader(['form' => $design->saveXML($layout)]));
foreach (Combodo\iTop\Application\TwigBase\Twig\Extension::GetFilters() as $filter) {
    $twig->addFilter($filter);
}
$appForm = $twig->render('form', ['oObject' => MetaModel::NewObject('ApplicationSolution')]);
$withLandscape = in_array('br-landscape', array_map('strval', $manifest->xpath('modules/module/id')), true);
check(isset($installed['br-landscape']) === $withLandscape, 'Unexpected System Landscape selection in the fixture.');
check(isset($installed['br-riskassessment-bridge-for-br-landscape']) === $withLandscape, 'Unexpected bridge selection.');
if ($withLandscape) {
    check($installed['br-riskassessment-bridge-for-br-landscape']['version'] === $version, 'Unexpected bridge version.');
}
$rendered = new DOMDocument();
check($rendered->loadXML($appForm), 'Invalid application portal form.');
$xpath = new DOMXPath($rendered);
check($xpath->query('//*[@data-field-id="system_landscape"]')->length === (int) $withLandscape,
    'Expected one landscape field only when the bridge is installed.');
check($xpath->query('//*[@id="riskassessment-app-classification"]/*[@data-field-id="system_landscape" and @data-field-flags="read_only"]')->length === (int) $withLandscape,
    'Landscape must be read-only in the classification column.');
echo $withLandscape ? "System Landscape and its portal bridge installed.\n" : "Standalone portal form verified.\n";
check(str_contains($appForm, 'data-field-id="rm_confidentiality"'), 'Missing risk assessment fields.');

$rules = installedRules();
$names = [
    '01 - AppSolution Conf > FunctionalCI Conf' => 'rm_confidentiality',
    '02 - AppSolution Int > FunctionalCI Int' => 'rm_integrity',
    '03 - AppSolution Avail > FunctionalCI Avail' => 'rm_availability',
    '04 - AppSolution Auth > FunctionalCI Auth' => 'rm_authenticity',
    '05 - AppSolution Non-Rep > FunctionalCI Non-Rep' => 'rm_nonrepudiation',
];
check(array_keys($rules) === array_keys($names), 'Unexpected installed rule names.');

CMDBObject::SetCurrentChange(null);
CMDBSource::Query('START TRANSACTION');
try {
    $prefix = 'Risk installer ' . bin2hex(random_bytes(4));
    $org = createFixture('Organization', ['name' => $prefix, 'status' => 'active']);
    $common = ['org_id' => $org->GetKey()];
    $high = array_fill_keys(array_values($names), 'high');
    $app = createFixture('ApplicationSolution', ['name' => "$prefix application"] + $common + $high);
    // Opposite authenticity and non-repudiation ratings detect crossed attributes.
    $weak = ['rm_confidentiality' => 'normal', 'rm_integrity' => 'normal',
        'rm_availability' => 'normal', 'rm_authenticity' => 'high'];
    $adequate = $high;
    unset($adequate['rm_authenticity']);
    $servers = [];
    foreach (['weak' => $weak, 'adequate' => $adequate, 'unlinked' => $weak] as $label => $ratings) {
        $server = createFixture('Server', ['name' => "$prefix $label"] + $common + $ratings);
        $servers[$label] = (int) $server->GetKey();
        if ($label !== 'unlinked') {
            createFixture('lnkApplicationSolutionToFunctionalCI', [
                'applicationsolution_id' => $app->GetKey(), 'functionalci_id' => $server->GetKey(),
            ]);
        }
    }
    foreach ($rules as $name => $rule) {
        $hits = [];
        $set = auditSet($rule->Get('query'));
        while ($ci = $set->Fetch()) {
            $hits[] = (int) $ci->GetKey();
        }
        $expected = $names[$name] === 'rm_authenticity' ? 'adequate' : 'weak';
        check(array_intersect($servers, $hits) === [$expected => $servers[$expected]], "Incorrect results: $name");
    }
    echo "All five installed OQL rules classified the real linked and unlinked servers correctly.\n";

    $rule = end($rules);
    $ruleId = $rule->GetKey();
    $correct = $rule->Get('query');
    // Original OQL from the 3.2.2 release, independent of the new query builder.
    $legacy = "SELECT FunctionalCI AS f\n"
        . "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n"
        . "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n"
        . "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n"
        . "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n"
        . "AND ((a.rm_nonrepudiation = 'high') AND (ISNULL(f.rm_authenticity) OR f.rm_nonrepudiation != 'high'))";
    $rule->Set('query', $legacy);
    $rule->DBWrite();
    $installer::AfterDatabaseCreation($config, '3.2.2', $version);
    check(MetaModel::GetObject('AuditRule', $ruleId)->Get('query') === $correct, 'Legacy rule was not repaired.');
    $custom = 'SELECT Server WHERE status = "production"';
    $rule = MetaModel::GetObject('AuditRule', $ruleId);
    $rule->Set('query', $custom);
    $rule->DBWrite();
    $installer::AfterDatabaseCreation($config, '3.2.2', $version);
    $installer::AfterDatabaseCreation($config, '', $version);
    $again = installedRules();
    foreach ($rules as $name => $original) {
        check($original->GetKey() === $again[$name]->GetKey(), "Setup replaced rule $name");
    }
    check($again[array_key_last($again)]->Get('query') === $custom, 'Custom rule was overwritten.');
    // Older setup runs could insert a second copy alongside an administrator's custom rule.
    $duplicate = createFixture('AuditRule', [
        'name' => $rule->Get('name'), 'category_id' => $rule->Get('category_id'),
        'valid_flag' => 'false', 'query' => $legacy,
    ]);
    $installer::AfterDatabaseCreation($config, '3.2.2', $version);
    check(MetaModel::GetObject('AuditRule', $duplicate->GetKey())->Get('query') === $correct
        && MetaModel::GetObject('AuditRule', $ruleId)->Get('query') === $custom, 'Legacy duplicate was not repaired selectively.');
    echo "Upgrade repaired shipped rules including a duplicate. Repeated setup preserved IDs and the custom query.\n";
} finally {
    CMDBSource::Query('ROLLBACK');
}
