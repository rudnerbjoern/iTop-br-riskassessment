<?php

/**
 * @copyright   Copyright (C) 2021 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2021-04-15
 *
 * Localized data
 */

//
// Risk levels
//
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Server:riskmanagement' => 'Risikomanagement',
    'Class:FunctionalCI/Attribute:rm_confidentiality' => 'Vertraulichkeit',
    'Class:FunctionalCI/Attribute:rm_confidentiality+' => 'Welchen Schutzbedarf an Vertraulichkeit hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:veryhigh' => 'sehr hoch',
    'Class:FunctionalCI/Attribute:rm_integrity' => 'Integrität',
    'Class:FunctionalCI/Attribute:rm_integrity+' => 'Welchen Schutzbedarf an Integrität und Authentizität hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:veryhigh' => 'sehr hoch',
    'Class:FunctionalCI/Attribute:rm_availability' => 'Verfügbarkeit',
    'Class:FunctionalCI/Attribute:rm_availability+' => 'Welchen Schutzbedarf an Verfügbarkeit hat das Objekt?',
    'Class:FunctionalCI/Attribute:rm_availability/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_availability/Value:high' => 'hoch',
    'Class:FunctionalCI/Attribute:rm_availability/Value:veryhigh' => 'sehr hoch',
));
