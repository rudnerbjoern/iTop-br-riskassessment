<?php

/**
 * @copyright   Copyright (C) 2022-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * iTop module definition file
 */

/** @disregard P1009 Undefined type SetupWebPage */
SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-riskassessment/3.3.0',
    array(
        // Identification
        //
        'label' => 'Datamodel: Risk Assessment',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            'itop-config-mgmt/3.3.0',
            'itop-portal/3.3.0',
            'itop-profiles-itil/3.3.0',
        ),
        'mandatory' => false,
        'visible' => true,
        'installer' => 'RiskAssessmentInstaller',

        // Components
        //
        'datamodel' => array(),
        'webservice' => array(),
        'data.struct' => array(
            // add your 'structure' definition XML files here,
        ),
        'data.sample' => array(
            // add your sample data XML files here,
        ),

        // Documentation
        //
        'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
        'doc.more_information' => '', // hyperlink to more information, if any

        // Default settings
        //
        'settings' => array(
            // Module specific settings go here, if any
        ),
    )
);

if (!class_exists('RiskAssessmentInstaller')) {
    class RiskAssessmentInstaller extends ModuleInstallerAPI
    {
        private const CATEGORY = 'Risk Management Mismatch';
        private const RULES = [
            'rm_confidentiality' => '01 - AppSolution Conf > FunctionalCI Conf',
            'rm_integrity' => '02 - AppSolution Int > FunctionalCI Int',
            'rm_availability' => '03 - AppSolution Avail > FunctionalCI Avail',
            'rm_authenticity' => '04 - AppSolution Auth > FunctionalCI Auth',
            'rm_nonrepudiation' => '05 - AppSolution Non-Rep > FunctionalCI Non-Rep',
        ];

        public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
        {
            if (!MetaModel::IsValidClass('AuditCategory') || !MetaModel::IsValidClass('AuditRule')) {
                return;
            }
            $installDomain = version_compare($sPreviousVersion, '3.2.0', '<');
            $repairLegacyRule = $sPreviousVersion !== '' && version_compare($sPreviousVersion, '3.3.0', '<')
                && version_compare($sCurrentVersion, '3.3.0', '>=');
            if (!$installDomain && !$repairLegacyRule) {
                return;
            }

            $categoryKey = ['name' => self::CATEGORY];
            if ($installDomain) {
                $category = self::ensureObject('AuditCategory', $categoryKey, [
                    'description' => 'Identify CIs whose protection level is below the application requirement',
                    'definition_set' => 'SELECT FunctionalCI',
                ]);
                foreach (self::RULES as $field => $name) {
                    $introduced = $field === 'rm_nonrepudiation' ? '0.5.0' : '0.4.0';
                    if (version_compare($sPreviousVersion, $introduced, '<')) {
                        self::ensureObject('AuditRule', ['category_id' => $category->GetKey(), 'name' => $name], [
                            'description' => 'Application requires a higher level of ' . substr($field, 3),
                            'query' => self::ruleQuery($field),
                            'valid_flag' => 'false',
                        ]);
                    }
                }
                if (MetaModel::IsValidClass('AuditDomain') && MetaModel::IsValidClass('lnkAuditCategoryToAuditDomain')) {
                    $domain = self::ensureObject('AuditDomain', ['name' => 'Risk Management'], [
                        'description' => 'Audit risk management requirements recorded in the CMDB',
                    ]);
                    self::ensureObject('lnkAuditCategoryToAuditDomain', [
                        'domain_id' => $domain->GetKey(), 'category_id' => $category->GetKey(),
                    ]);
                }
            } else {
                $category = self::matchingObjects('AuditCategory', $categoryKey)->Fetch();
            }

            if ($repairLegacyRule && $category !== null) {
                $rules = self::matchingObjects('AuditRule', [
                    'category_id' => $category->GetKey(), 'name' => self::RULES['rm_nonrepudiation'],
                ]);
                $correct = self::ruleQuery('rm_nonrepudiation');
                // Match the shipped query exactly so an administrator's own OQL is preserved.
                $legacy = str_replace('ISNULL(f.rm_nonrepudiation)', 'ISNULL(f.rm_authenticity)', $correct);
                while ($rule = $rules->Fetch()) {
                    if (str_replace("\r\n", "\n", $rule->Get('query')) === $legacy) {
                        $rule->Set('query', $correct);
                        $rule->DBWrite();
                    }
                }
            }
        }

        private static function matchingObjects(string $class, array $key): DBObjectSet
        {
            $search = new DBObjectSearch($class);
            foreach ($key as $attribute => $value) {
                $search->AddCondition($attribute, $value, '=');
            }
            return new DBObjectSet($search);
        }

        private static function ensureObject(string $class, array $key, array $defaults = [])
        {
            $object = self::matchingObjects($class, $key)->Fetch();
            if ($object === null) {
                $object = MetaModel::NewObject($class, $key + $defaults);
                $object->DBWrite();
                SetupLog::Info("Risk Assessment created $class #" . $object->GetKey());
            }
            return $object;
        }

        private static function ruleQuery(string $field): string
        {
            $base = "SELECT FunctionalCI AS f\n"
                . "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n"
                . "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n"
                . "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n"
                . "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n";
            if ($field === 'rm_authenticity' || $field === 'rm_nonrepudiation') {
                return $base . "AND ((a.$field = 'high') AND (ISNULL(f.$field) OR f.$field != 'high'))";
            }
            return $base . "AND (((a.$field = 'veryhigh') AND (ISNULL(f.$field) OR f.$field != 'veryhigh'))\n"
                . "OR ((a.$field = 'high') AND (ISNULL(f.$field) OR f.$field NOT IN ('veryhigh', 'high')))\n"
                . "OR ((a.$field = 'normal') AND (ISNULL(f.$field) OR f.$field NOT IN ('veryhigh', 'high', 'normal'))))";
        }
    }
}
