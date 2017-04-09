<?php
defined('TYPO3_MODE') or die();

$tca = array(
    'ctrl' => array(
        'typeicon_classes' => array(
            '21' => 'pagebrowser',
            'contains-pbrowser' => 'pagebrowser',
        ),
    ),
    'types' => array(
        '21' => array(
            'showitem' => $GLOBALS['TCA']['pages']['types']['1']['showitem'],
        ),
    ),
    'columns' => array(
        'doktype' => array(
            'config' => array(
                'items' => array(
                    '21' => array(
                        '0' => 'LLL:EXT:cag_pagebrowser/Resources/Private/Language/locallang_db.xlf:pages.doktype.I.21',
                        '1' => '21',
                        '2' => 'pagebrowser',
                    ),
                ),
            ),
        ),
        'module' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:cvma/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.dc_type',
            'config' => array(
                'items' => array(
                    '21' => array(
                        '0' => 'LLL:EXT:cag_pagebrowser/Resources/Private/Language/locallang_db.xlf:pages.doktype.I.21',
                        '1' => 'pbrowser',
                        '2' => 'pagebrowser',
                    ),
                ),
            ),
        ),
    ),
);

$GLOBALS['TCA']['pages'] = array_replace_recursive($GLOBALS['TCA']['pages'], $tca);
