<?php

declare(strict_types=1);

// Minimal iTop substitutes: execute the real registration and installer offline.
class SetupWebPage
{
    public static array $module;

    public static function AddModule(string $file, string $id, array $settings): void
    {
        self::$module = [$id, $settings];
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

    public static function IsValidClass(string $class): bool
    {
        return self::$auditAvailable;
    }

    public static function NewObject(string $class, array $values): object
    {
        return new class ($class, $values) {
            public string $class;
            public array $values;
            private int $id = 0;

            public function __construct(string $class, array $values)
            {
                $this->class = $class;
                $this->values = $values;
            }

            public function Set(string $key, $value): void
            {
                $this->values[$key] = $value;
            }

            public function DBWrite(): void
            {
                $this->id = count(MetaModel::$objects) + 1;
                MetaModel::$objects[] = $this;
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
    public static function FromOQL(string $query): string
    {
        return $query;
    }
}

class DBObjectSet
{
    private string $query;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function Fetch(): ?object
    {
        foreach (MetaModel::$objects as $object) {
            if ($this->query === 'SELECT ' . $object->class . ' WHERE name = "' . ($object->values['name'] ?? '') . '"') {
                return $object;
            }
        }
        return null;
    }
}

require __DIR__ . '/../br-riskassessment/module.br-riskassessment.php';

$tests = [
    'register matching module version and configuration dependency' => static function (): void {
        $xml = simplexml_load_file(__DIR__ . '/../extension.xml');
        [$id, $settings] = SetupWebPage::$module;
        if ($id !== 'br-riskassessment/' . trim((string) $xml->version)
            || $settings['dependencies'] !== ['itop-config-mgmt/3.2.0']
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
        RiskAssessmentInstaller::AfterDatabaseCreation(new Config(), $previous, '3.2.1');
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

return $tests;
