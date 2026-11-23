<?php

declare(strict_types=1);

// Minimal iTop substitutes: execute the real registration and installer offline.
class SetupWebPage
{
    public static array $registrations = [];

    public static function AddModule(string $file, string $id, array $settings): void
    {
        self::$registrations[explode('/', $id, 2)[0]] = [$id, $settings];
    }
}

class ModuleInstallerAPI
{
}

class Config
{
}

class SetupLog
{
    public static function Info(string $message): void
    {
    }
}

class MetaModel
{
    public static array $objects = [];
    public static bool $auditAvailable = true;
    public static array $missingClasses = [];
    public static ?string $failNextRule = null;

    public static function IsValidClass(string $class): bool
    {
        return self::$auditAvailable && !in_array($class, self::$missingClasses, true);
    }

    public static function NewObject(string $class, array $values): object
    {
        return new class ($class, $values) {
            public int $writes = 0;
            private int $id = 0;

            public function __construct(public string $class, public array $values)
            {
            }

            public function Get(string $attribute)
            {
                return $this->values[$attribute];
            }

            public function Set(string $key, $value): void
            {
                $this->values[$key] = $value;
            }

            public function DBWrite(): void
            {
                if (MetaModel::$failNextRule !== null && $this->class === 'AuditRule'
                    && $this->values['name'] === MetaModel::$failNextRule) {
                    MetaModel::$failNextRule = null;
                    throw new RuntimeException('Simulated write failure');
                }
                if ($this->id === 0) {
                    $this->id = count(MetaModel::$objects) + 1;
                    MetaModel::$objects[] = $this;
                }
                $this->writes++;
            }

            public function GetKey(): int
            {
                return $this->id;
            }
        };
    }
}

class DBObjectSearch
{
    public array $conditions = [];

    public function __construct(public string $class)
    {
    }

    public function AddCondition(string $attribute, $value, string $operator): void
    {
        if ($operator !== '=') {
            throw new LogicException('Only exact installer lookups are supported.');
        }
        $this->conditions[$attribute] = $value;
    }
}

class DBObjectSet
{
    private array $matches;
    private int $position = 0;

    public function __construct(DBObjectSearch $search)
    {
        $this->matches = array_values(array_filter(MetaModel::$objects, static fn ($object) =>
            $object->class === $search->class && !array_diff_assoc($search->conditions, $object->values)));
    }

    public function Fetch(): ?object
    {
        return $this->matches[$this->position++] ?? null;
    }
}


require __DIR__ . '/../br-riskassessment/module.br-riskassessment.php';
require __DIR__ . '/../br-riskassessment-bridge-for-br-landscape/module.br-riskassessment-bridge-for-br-landscape.php';

function registeredRiskAssessmentVersion(): string
{
    return explode('/', SetupWebPage::$registrations['br-riskassessment'][0], 2)[1];
}

$tests = [
    'register the optional System Landscape bridge' => static function (): void {
        $version = registeredRiskAssessmentVersion();
        [$id, $bridge] = SetupWebPage::$registrations['br-riskassessment-bridge-for-br-landscape'];
        if ($id !== 'br-riskassessment-bridge-for-br-landscape/' . $version
            || $bridge['mandatory'] !== false
            || $bridge['dependencies'] !== ["br-riskassessment/$version", 'br-landscape/3.2.2 || !(br-landscape/0.0.0)']
            || $bridge['auto_select'] !== 'SetupInfo::ModuleIsSelected("br-riskassessment") && SetupInfo::ModuleIsSelected("br-landscape")') {
            throw new RuntimeException('Unexpected bridge registration or selection conditions.');
        }
    },
    'register matching module version and configuration dependency' => static function (): void {
        $xml = simplexml_load_file(__DIR__ . '/../extension.xml');
        [$id, $settings] = SetupWebPage::$registrations['br-riskassessment'];
        $minimum = (string) $xml->itop_version_min;
        $dependencies = ["itop-config-mgmt/$minimum", "itop-portal/$minimum", "itop-profiles-itil/$minimum"];
        if ((string) $xml->extension_code !== 'br-riskassessment'
            || (string) $xml->modules->module->id !== 'br-riskassessment'
            || (string) $xml->modules->module->version !== trim((string) $xml->version)
            || $id !== 'br-riskassessment/' . trim((string) $xml->version)
            || $minimum !== '3.3.0' || $settings['dependencies'] !== $dependencies
            || $settings['installer'] !== RiskAssessmentInstaller::class
            || $settings['mandatory'] !== false || $settings['visible'] !== true) {
            throw new RuntimeException('Module registration does not match the extension contract.');
        }
    },
];

foreach ([
    'install all audit objects on first installation' => ['', true, 5, 1],
    'upgrade 0.4.0 with only the new rule and domain' => ['0.4.0', true, 1, 1],
    'upgrade 0.5.0 with only the audit domain' => ['0.5.0', true, 0, 1],
    'skip completed migrations from 3.2.0 onward' => ['3.2.0', true, 0, 0],
    'skip audit setup when audit classes are unavailable' => ['', false, 0, 0],
] as $name => [$previous, $available, $rules, $domains]) {
    $tests[$name] = static function () use ($previous, $available, $rules, $domains): void {
        MetaModel::$objects = [];
        MetaModel::$auditAvailable = $available;
        if ($previous !== '' && $previous !== '3.2.0' && $available) {
            MetaModel::NewObject('AuditCategory', ['name' => 'Risk Management Mismatch'])->DBWrite();
        }
        RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), $previous, registeredRiskAssessmentVersion());
        $counts = array_count_values(array_map(static fn (object $object): string => $object->class, MetaModel::$objects));
        if (($counts['AuditRule'] ?? 0) !== $rules || ($counts['AuditDomain'] ?? 0) !== $domains
            || ($counts['lnkAuditCategoryToAuditDomain'] ?? 0) !== $domains
            || ($counts['AuditCategory'] ?? 0) !== (($rules || $domains) ? 1 : 0)) {
            throw new RuntimeException('Unexpected audit objects: ' . json_encode($counts));
        }
        foreach (MetaModel::$objects as $object) {
            if ($object->class === 'AuditRule' && ($object->values['category_id'] !== 1 || $object->values['valid_flag'] !== 'false')) {
                throw new RuntimeException('Audit rule must identify mismatches and reference its category.');
            }
            if ($object->class === 'lnkAuditCategoryToAuditDomain'
                && ($object->values['category_id'] !== 1 || $object->values['domain_id'] !== $rules + 2)) {
                throw new RuntimeException('Audit domain must be linked to the existing category.');
            }
        }
    };
}

$tests['resume a failed installation and preserve existing objects on retries'] = static function (): void {
    MetaModel::$objects = [];
    MetaModel::$auditAvailable = true;
    MetaModel::$failNextRule = '03 - AppSolution Avail > FunctionalCI Avail';
    try {
        RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '', registeredRiskAssessmentVersion());
        throw new LogicException('Installer swallowed a failed write.');
    } catch (RuntimeException $failure) {
        if ($failure->getMessage() !== 'Simulated write failure') {
            throw $failure;
        }
    }
    $firstRule = MetaModel::$objects[1];
    $firstRule->Set('query', 'SELECT Server');
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '', registeredRiskAssessmentVersion());
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '', registeredRiskAssessmentVersion());
    if (count(MetaModel::$objects) !== 8 || array_sum(array_column(MetaModel::$objects, 'writes')) !== 8
        || $firstRule->Get('query') !== 'SELECT Server') {
        throw new RuntimeException('Retry duplicated or overwrote an existing audit object.');
    }
};

$tests['missing audit domain support still installs all five rules'] = static function (): void {
    MetaModel::$objects = [];
    MetaModel::$auditAvailable = true;
    foreach (['AuditDomain', 'lnkAuditCategoryToAuditDomain'] as $unavailable) {
        MetaModel::$objects = [];
        MetaModel::$missingClasses = [$unavailable];
        try {
            RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '', registeredRiskAssessmentVersion());
            if (count(MetaModel::$objects) !== 6) {
                throw new RuntimeException("Unexpected installation without $unavailable.");
            }
        } finally {
            MetaModel::$missingClasses = [];
        }
    }
};

$tests['upgrade repairs only the shipped non-repudiation rule in its own category'] = static function (): void {
    MetaModel::$objects = [];
    MetaModel::$auditAvailable = true;
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '', registeredRiskAssessmentVersion());
    $rule = MetaModel::$objects[5];
    $correct = $rule->Get('query');
    if (!str_contains($correct, 'ISNULL(f.rm_nonrepudiation)') || str_contains($correct, 'rm_authenticity')) {
        throw new RuntimeException('Non-repudiation must check its own value.');
    }
    $legacy = str_replace('ISNULL(f.rm_nonrepudiation)', 'ISNULL(f.rm_authenticity)', $correct);
    $rule->Set('query', $legacy);
    $other = MetaModel::NewObject('AuditRule', ['category_id' => 99, 'query' => $legacy] + $rule->values);
    $other->DBWrite();
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '3.2.1', '3.2.2');
    if ($rule->Get('query') !== $legacy) {
        throw new RuntimeException('Repair ran before the 3.3 upgrade.');
    }
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '3.2.2', registeredRiskAssessmentVersion());
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '3.2.2', registeredRiskAssessmentVersion());
    if ($rule->Get('query') !== $correct || $rule->writes !== 2 || $other->Get('query') !== $legacy) {
        throw new RuntimeException('Repair changed the wrong rows or wrote twice.');
    }
    $custom = $legacy . ' AND f.id > 100';
    $rule->Set('query', $custom);
    $duplicate = MetaModel::NewObject('AuditRule', ['query' => $legacy] + $rule->values);
    $duplicate->DBWrite();
    RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), '3.2.2', registeredRiskAssessmentVersion());
    if ($rule->Get('query') !== $custom || $rule->writes !== 2 || $duplicate->Get('query') !== $correct) {
        throw new RuntimeException('Customized rule was overwritten.');
    }
};

return $tests;
