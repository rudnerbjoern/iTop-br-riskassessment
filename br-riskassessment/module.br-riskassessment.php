<?php

/**
 * @copyright   Copyright (C) 2022-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2025-04-15
 *
 * iTop module definition file
 */

SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-riskassessment/3.2.0',
    array(
        // Identification
        //
        'label' => 'Datamodel: Risk Assessment',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            'itop-config-mgmt/3.1.0',
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
    /**
     * Module installation handler
     */
    class RiskAssessmentInstaller extends ModuleInstallerAPI
    {
        public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
        {
            // Create audit rules introduced in Version 0.4.0
            if (version_compare($sPreviousVersion, '0.4.0', '<')) {
                SetupLog::Info("|- Installing Risk Assessment from '$sPreviousVersion' to '$sCurrentVersion'. The extension comes with audit rules so corresponding objects will created into the DB...");

                if (MetaModel::IsValidClass('AuditRule')) {
                    // First, create audit category for Server mismatch
                    $oSearch = DBObjectSearch::FromOQL('SELECT AuditCategory WHERE name = "Risk Management Mismatch"');
                    $oSet = new DBObjectSet($oSearch);
                    $oAuditCategory = $oSet->Fetch();

                    if ($oAuditCategory === null) {
                        try {
                            $oAuditCategory = MetaModel::NewObject('AuditCategory', array(
                                'name' => 'Risk Management Mismatch',
                                'description' => 'Show FunctionalCIs with higher demand of information security then provided',
                                'definition_set' => 'SELECT FunctionalCI',
                            ));
                            $oAuditCategory->DBWrite();
                            SetupLog::Info('|  |- AuditCategory "Risk Management Mismatch" created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditCategory. (Error: ' . $oException->getMessage() . ')');
                        }
                    } else {
                        SetupLog::Info('|  |- AuditCategory "Risk Management Mismatch" already existing! Weird as it is supposed to be created by this extension, but meh, will use it anyway!');
                    }

                    // Then, create audit rules
                    $aAuditRules = array(
                        array(
                            'name' => '01 - AppSolution Conf > FunctionalCI Conf',
                            'description' => 'Demand for confidentiality is higher then provided',
                            'query' =>  "SELECT FunctionalCI AS f\n" .
                                "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n" .
                                "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n" .
                                "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n" .
                                "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n" .
                                "AND (((a.rm_confidentiality = 'veryhigh') AND (ISNULL(f.rm_confidentiality) OR f.rm_confidentiality != 'veryhigh'))\n" .
                                "OR ((a.rm_confidentiality = 'high') AND (ISNULL(f.rm_confidentiality) OR f.rm_confidentiality NOT IN ('veryhigh', 'high')))\n" .
                                "OR ((a.rm_confidentiality = 'normal') AND (ISNULL(f.rm_confidentiality) OR f.rm_confidentiality NOT IN ('veryhigh', 'high', 'normal'))))",
                            'valid_flag' => 'false',
                        ),
                        array(
                            'name' => '02 - AppSolution Int > FunctionalCI Int',
                            'description' => 'Demand for integrity is higher then provided',
                            'query' =>  "SELECT FunctionalCI AS f\n" .
                                "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n" .
                                "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n" .
                                "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n" .
                                "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n" .
                                "AND (((a.rm_integrity = 'veryhigh') AND (ISNULL(f.rm_integrity) OR f.rm_integrity != 'veryhigh'))\n" .
                                "OR ((a.rm_integrity = 'high') AND (ISNULL(f.rm_integrity) OR f.rm_integrity NOT IN ('veryhigh', 'high')))\n" .
                                "OR ((a.rm_integrity = 'normal') AND (ISNULL(f.rm_integrity) OR f.rm_integrity NOT IN ('veryhigh', 'high', 'normal'))))",
                            'valid_flag' => 'false',
                        ),
                        array(
                            'name' => '03 - AppSolution Avail > FunctionalCI Avail',
                            'description' => 'Demand for availability is higher then provided',
                            'query' =>  "SELECT FunctionalCI AS f\n" .
                                "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n" .
                                "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n" .
                                "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n" .
                                "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n" .
                                "AND (((a.rm_availability = 'veryhigh') AND (ISNULL(f.rm_availability) OR f.rm_availability != 'veryhigh'))\n" .
                                "OR ((a.rm_availability = 'high') AND (ISNULL(f.rm_availability) OR f.rm_availability NOT IN ('veryhigh', 'high')))\n" .
                                "OR ((a.rm_availability = 'normal') AND (ISNULL(f.rm_availability) OR f.rm_availability NOT IN ('veryhigh', 'high', 'normal'))))",
                            'valid_flag' => 'false',
                        ),
                        array(
                            'name' => '04 - AppSolution Auth > FunctionalCI Auth',
                            'description' => 'Demand for authenticity is higher then provided',
                            'query' =>  "SELECT FunctionalCI AS f\n" .
                                "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n" .
                                "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n" .
                                "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n" .
                                "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n" .
                                "AND ((a.rm_authenticity = 'high') AND (ISNULL(f.rm_authenticity) OR f.rm_authenticity != 'high'))",
                            'valid_flag' => 'false',
                        ),
                    );
                    foreach ($aAuditRules as $aAuditRule) {
                        try {
                            $oAuditRule = MetaModel::NewObject('AuditRule', $aAuditRule);
                            $oAuditRule->Set('category_id', $oAuditCategory->GetKey());
                            $oAuditRule->DBWrite();
                            SetupLog::Info('|  |- AuditRule "' . $aAuditRule['name'] . '" created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditRule "' . $aAuditRule['name'] . '". (Error: ' . $oException->getMessage() . ')');
                        }
                    }
                }
            }

            // Extend audit rules introduced in Version 0.5.0
            if (version_compare($sPreviousVersion, '0.5.0', '<')) {
                SetupLog::Info("|- Installing Risk Assessment from '$sPreviousVersion' to '$sCurrentVersion'. The extension adds audit rules so corresponding objects will created into the DB...");

                if (MetaModel::IsValidClass('AuditRule')) {
                    // First, create audit category for Server mismatch
                    $oSearch = DBObjectSearch::FromOQL('SELECT AuditCategory WHERE name = "Risk Management Mismatch"');
                    $oSet = new DBObjectSet($oSearch);
                    $oAuditCategory = $oSet->Fetch();

                    if ($oAuditCategory === null) {
                        try {
                            $oAuditCategory = MetaModel::NewObject('AuditCategory', array(
                                'name' => 'Risk Management Mismatch',
                                'description' => 'Show FunctionalCIs with higher demand of information security then provided',
                                'definition_set' => 'SELECT FunctionalCI',
                            ));
                            $oAuditCategory->DBWrite();
                            SetupLog::Info('|  |- AuditCategory "Risk Management Mismatch" created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditCategory. (Error: ' . $oException->getMessage() . ')');
                        }
                    } else {
                        SetupLog::Info('|  |- AuditCategory "Risk Management Mismatch" already existing! We will use it!');
                    }

                    // Then, create audit rules
                    $aAuditRules = array(
                        array(
                            'name' => '05 - AppSolution Non-Rep > FunctionalCI Non-Rep',
                            'description' => 'Demand for non-repudiation is higher then provided',
                            'query' =>  "SELECT FunctionalCI AS f\n" .
                                "JOIN lnkApplicationSolutionToFunctionalCI AS lnk ON lnk.functionalci_id = f.id\n" .
                                "JOIN ApplicationSolution AS a ON lnk.applicationsolution_id = a.id\n" .
                                "WHERE lnk.functionalci_id_finalclass_recall != 'ApplicationSolution'\n" .
                                "AND lnk.functionalci_id_finalclass_recall IN ('Server', 'Farm', 'Hypervisor', 'VirtualMachine')\n" .
                                "AND ((a.rm_nonrepudiation = 'high') AND (ISNULL(f.rm_authenticity) OR f.rm_nonrepudiation != 'high'))",
                            'valid_flag' => 'false',
                        ),
                    );
                    foreach ($aAuditRules as $aAuditRule) {
                        try {
                            $oAuditRule = MetaModel::NewObject(
                                'AuditRule',
                                $aAuditRule
                            );
                            $oAuditRule->Set('category_id', $oAuditCategory->GetKey());
                            $oAuditRule->DBWrite();
                            SetupLog::Info('|  |- AuditRule "' . $aAuditRule['name'] . '" created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditRule "' . $aAuditRule['name'] . '". (Error: ' . $oException->getMessage() . ')');
                        }
                    }
                }
            }

            // Introduce AuditDomain in Version 3.2.0
            if (version_compare($sPreviousVersion, '3.2.0', '<')) {
                SetupLog::Info("|- Installing Risk Assessment from '$sPreviousVersion' to '$sCurrentVersion'. Updating AuditDomain and lnkAuditCategoryToAuditDomain ...");

                $iAuditDomainId = 0;
                $iAuditCategoryId = 0;

                if (MetaModel::IsValidClass('AuditDomain')) {
                    // First, create audit category for Server mismatch
                    $oSearch = DBObjectSearch::FromOQL('SELECT AuditDomain WHERE name = "Risk Management"');
                    $oSet = new DBObjectSet($oSearch);
                    $oAuditDomain = $oSet->Fetch();

                    if ($oAuditDomain === null) {
                        try {
                            $oAuditDomain = MetaModel::NewObject('AuditDomain', array(
                                'name' => 'Risk Management',
                                'description' => 'Audit Risk Management defined in the CMDB',
                            ));
                            $oAuditDomain->DBWrite();
                            SetupLog::Info('|  |- AuditDomain "Risk Management" created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditDomain. (Error: ' . $oException->getMessage() . ')');
                        }
                    } else {
                        SetupLog::Info('|  |- AuditDomain "Risk Management" already existing! We will use it!');
                    }

                    // Link AuditDomain with AuditCategory
                    $oSearch = DBObjectSearch::FromOQL('SELECT AuditCategory WHERE name = "Risk Management Mismatch"');
                    $oSet = new DBObjectSet($oSearch);
                    $oAuditCategory = $oSet->Fetch();

                    $iAuditDomainId = ($oAuditDomain !== null) ? $oAuditDomain->GetKey() : 0;
                    $iAuditCategoryId = ($oAuditCategory !== null) ? $oAuditCategory->GetKey() : 0;

                    if ($iAuditDomainId > 0 && $iAuditCategoryId > 0) {
                        try {
                            $oAuditLink = MetaModel::NewObject('lnkAuditCategoryToAuditDomain', array(
                                'domain_id' => $iAuditDomainId,
                                'category_id' => $iAuditCategoryId,
                            ));
                            $oAuditLink->DBWrite();
                            SetupLog::Info('|  |- AuditLink created.');
                        } catch (Exception $oException) {
                            SetupLog::Info('|  |- Could not create AuditLink. (Error: ' . $oException->getMessage() . ')');
                        }
                    }
                }
            }
        }
    }
};
