<?php

/**
 * @copyright   Copyright (C) 2022-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2025-06-12
 *
 * Localized data
 */

//
// Risk management
//
/** @disregard P1009 Undefined type Dict */
Dict::Add('RU RU', 'Russian', 'Русский', array(
    'FunctionalCI:RiskManagement' => 'Управление рисками',
    'Class:FunctionalCI/Attribute:rm_confidentiality' => 'Уровень конфиденциальности',
    'Class:FunctionalCI/Attribute:rm_confidentiality+' => 'Каковы требования конфиденциальности для объекта?',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:low' => 'низкий',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:normal' => 'нормальный',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:high' => 'высокий',
    'Class:FunctionalCI/Attribute:rm_confidentiality/Value:veryhigh' => 'очень высокий',
    'Class:FunctionalCI/Attribute:rm_integrity' => 'Целостность',
    'Class:FunctionalCI/Attribute:rm_integrity+' => 'Каковы требования к целостности и подлинности для объекта?',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:low' => 'низкий',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:normal' => 'нормальный',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:high' => 'высокий',
    'Class:FunctionalCI/Attribute:rm_integrity/Value:veryhigh' => 'очень высокий',
    'Class:FunctionalCI/Attribute:rm_availability' => 'Доступность',
    'Class:FunctionalCI/Attribute:rm_availability+' => 'Каковы требования доступности для объекта?',
    'Class:FunctionalCI/Attribute:rm_availability/Value:low' => 'низкий',
    'Class:FunctionalCI/Attribute:rm_availability/Value:normal' => 'нормальный',
    'Class:FunctionalCI/Attribute:rm_availability/Value:high' => 'высокий',
    'Class:FunctionalCI/Attribute:rm_availability/Value:veryhigh' => 'очень высокий',
    'Class:FunctionalCI/Attribute:rm_authenticity' => 'Подлинность',
    'Class:FunctionalCI/Attribute:rm_authenticity+' => 'Каковы требования подлинности для объекта?',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:none' => 'не выбрано',
    'Class:FunctionalCI/Attribute:rm_authenticity/Value:high' => 'высокий',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation' => 'Неотказуемость',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation+' => 'Каковы требования неотказуемости для объекта?',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:none' => 'не выбрано',
    'Class:FunctionalCI/Attribute:rm_nonrepudiation/Value:high' => 'высокий',
    'Class:FunctionalCI/Attribute:bcm_rpo' => 'BCM: RPO',
    'Class:FunctionalCI/Attribute:bcm_rpo+' => 'Целевая точка восстановления',
    'Class:FunctionalCI/Attribute:bcm_rto' => 'BCM: RTO',
    'Class:FunctionalCI/Attribute:bcm_rto+' => 'Целевое время восстановления',
    'Class:FunctionalCI/Attribute:bcm_mtd' => 'BCM: MTD',
    'Class:FunctionalCI/Attribute:bcm_mtd+' => 'Максимально доступное время простоя',
    'Class:FunctionalCI/Error:MtdMustBeGreaterThanRto' => 'Убедитесь, что RTO находиться в пределах MTD.',
));
