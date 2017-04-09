<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// Adding TypoScript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/v1', 'Pagebrowser (v.1): Basics');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/v1/pagenumbers', 'Pagebrowser (v.1): Pagenumbers');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/v2', 'Pagebrowser (v.2): Basics');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/v2/treeprevnext', 'Pagebrowser (v.2): Tree Prev/Next');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/common/entrylink', 'Pagebrowser (v.1/2): Entry links');
