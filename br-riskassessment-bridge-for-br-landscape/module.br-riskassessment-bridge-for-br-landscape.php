<?php

/**
 * @copyright   Copyright (C) 2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 */

SetupWebPage::AddModule(
    __FILE__,
    'br-riskassessment-bridge-for-br-landscape/3.3.0',
    [
        'label' => 'Risk Assessment – System Landscape portal integration',
        'category' => 'business',
        'dependencies' => [
            'br-riskassessment/3.3.0',
            'br-landscape/3.2.2 || !(br-landscape/0.0.0)',
        ],
        'mandatory' => false,
        'visible' => true,
        'auto_select' => 'SetupInfo::ModuleIsSelected("br-riskassessment") && SetupInfo::ModuleIsSelected("br-landscape")',
        'datamodel' => [],
        'webservice' => [],
        'dictionary' => [],
        'data.struct' => [],
        'data.sample' => [],
        'doc.manual_setup' => '',
        'doc.more_information' => '',
        'settings' => [],
    ]
);
