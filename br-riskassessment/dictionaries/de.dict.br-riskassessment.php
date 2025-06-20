<?php

/**
 * @copyright   Copyright (C) 2022-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2025-06-12
 *
 * Localized data
 */

//
// Risk levels
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'FunctionalCI:RiskManagement' => 'Risikomanagement',
    'Class:FunctionalCI/Attribute:rm_confidentiality' => 'Vertraulichkeit',
    'Class:FunctionalCI/Attribute:rm_confidentiality+' => 'Welchen Schutzbedarf an Vertraulichkeit hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:none' => 'keine',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:veryhigh' => 'sehr hoch',
    'Class:FunctionalCI/Attribute:rm_integrity' => 'Integrität',
    'Class:FunctionalCI/Attribute:rm_integrity+' => 'Welchen Schutzbedarf an Integrität und Authentizität hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:none' => 'keine',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:veryhigh' => 'sehr hoch',
    'Class:FunctionalCI/Attribute:rm_availability' => 'Verfügbarkeit',
    'Class:FunctionalCI/Attribute:rm_availability+' => 'Welchen Schutzbedarf an Verfügbarkeit hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_availability/Value:none' => 'keine',
    'Class:FunctionalCI/Attribute:rm_availability/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_availability/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_availability/Value:veryhigh' => 'sehr hoch',
    'Class:FunctionalCI/Attribute:rm_authenticity' => 'Authentizität',
    'Class:FunctionalCI/Attribute:rm_authenticity+' => 'Welchen Schutzbedarf an Authentizität hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:none' => 'keine',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation' => 'Verbindlichkeit',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation+' => 'Welchen Schutzbedarf an Verbindlichkeit hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:none' => 'keine',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:bcm_rpo' => 'BCM: RPO',
    'Class:FunctionalCI/Attribute:bcm_rpo+' => 'Recovery Point Objective',
    'Class:FunctionalCI/Attribute:bcm_rto' => 'BCM: RTO',
    'Class:FunctionalCI/Attribute:bcm_rto+' => 'Recovery Time Objective',
    'Class:FunctionalCI/Attribute:bcm_mtd' => 'BCM: MTD',
    'Class:FunctionalCI/Attribute:bcm_mtd+' => 'Maximum Tolerable Downtime',
    'Class:FunctionalCI/Error:MtdMustBeGreaterThanRto' => 'Der MTD-Wert muss gleich oder größer als der RTO-Wert sein.',
));
