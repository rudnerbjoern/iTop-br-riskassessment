<?php

/**
 * @copyright   Copyright (C) 2024 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-10-25
 *
 * Localized data
 */

//
// Risk management
//
Dict::Add('EN US', 'English', 'English', array(
    'Server:riskmanagement' => 'Risk management',
    'Class:FunctionalCI/Attribute:rm_confidentiality' => 'Confidentiality',
    'Class:FunctionalCI/Attribute:rm_confidentiality+' => 'What level of confidentiality protection is required for the object?',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:low' => 'low',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:high' => 'high',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:veryhigh' => 'very high',
    'Class:FunctionalCI/Attribute:rm_integrity' => 'Integrity',
    'Class:FunctionalCI/Attribute:rm_integrity+' => 'What is the object\'s integrity and authenticity protection requirement?',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:low' => 'low',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:high' => 'high',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:veryhigh' => 'very high',
    'Class:FunctionalCI/Attribute:rm_availability' => 'Availability',
    'Class:FunctionalCI/Attribute:rm_availability+' => 'What is the availability protection requirement of the object?',
    'Class:FunctionalCI/Attribute:rm_availability/Value:low' => 'low',
    'Class:FunctionalCI/Attribute:rm_availability/Value:normal' => 'normal',
    'Class:FunctionalCI/Attribute:rm_availability/Value:high' => 'high',
    'Class:FunctionalCI/Attribute:rm_availability/Value:veryhigh' => 'very high',
    'Class:FunctionalCI/Attribute:rm_authenticity' => 'Authenticity',
    'Class:FunctionalCI/Attribute:rm_authenticity+' => 'What is the authenticity required for the object?',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:none' => 'none',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:high' => 'high',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation' => 'Non-repudiation',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation+' => 'What is the non-repudiation required for the object?',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:none' => 'none',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:high' => 'high',
    'Class:FunctionalCI/Attribute:bcm_rpo' => 'BCM: RPO',
    'Class:FunctionalCI/Attribute:bcm_rpo+' => 'Recovery Point Objective',
    'Class:FunctionalCI/Attribute:bcm_rto' => 'BCM: RTO',
    'Class:FunctionalCI/Attribute:bcm_rto+' => 'Recovery Time Objective',
    'Class:FunctionalCI/Attribute:bcm_mtd' => 'BCM: MTD',
    'Class:FunctionalCI/Attribute:bcm_mtd+' => 'Maximum Tolerable Downtime',
));
