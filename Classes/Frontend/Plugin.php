<?php
namespace Digicademy\CagPagebrowser\Frontend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2017 Torsten Schrade
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Plugin extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

    var $prefixID = 'tx_cagpagebrowser';
    var $scriptRelPath = 'Classes/Frontend/Plugin/Pagebrowser.php';
    var $extKey = 'cag_pagebrowser';

    /**
     * This function checks if a pagination may be inserted on the current page or not.
     *
     * @param   string $content The content given to the function
     * @param   array  $conf    TypoScript configuration array
     *
     * @return  boolean
     */
    public function main($content, $conf)
    {

        $excludeUids = in_array($this->cObj->getData('page: uid', ''), GeneralUtility::trimExplode(',', $conf['excludeUidList']));

        if ($conf['browserMode'] == 'std') {

            ($this->cObj->getData('levelfield: -2, doktype', '') == '21' && count($GLOBALS['TSFE']->tmpl->rootLine) - 1 != 0 && $excludeUids != 1) ? $value = '1' : $value = '0';

            if (!$value) {
                ($this->cObj->getData('levelfield: -2, module', '') == 'pbrowser' && count($GLOBALS['TSFE']->tmpl->rootLine) - 1 != 0 && $excludeUids != 1) ? $value = '1' : $value = '0';
            }

        } elseif ($conf['browserMode'] == 'rec') {

            ($this->checkValueInRootline('doktype', '21') && $excludeUids != 1) ? $value = '1' : $value = '0';

            if (!$value) {
                ($this->checkValueInRootline('module', 'pbrowser') && $excludeUids != 1) ? $value = '1' : $value = '0';
            }

        } else {
            $value = 0;
        }

        return $value;
    }

    /**
     * Check if a field in the rootline of the current page contains a specific value and if yes return TRUE
     *
     * @param   string $field      The fieldname to look for in the rootline
     * @param   string $fieldValue The value of the field to be checked
     *
     * @return    boolean
     */
    public function checkValueInRootline($field, $fieldValue)
    {

        $rootLine = $GLOBALS['TSFE']->tmpl->rootLine;

        foreach ($rootLine as $key => $val) {

            if ($rootLine[$key][$field] == $fieldValue && $rootLine[$key]['uid'] != $GLOBALS['TSFE']->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current page is of the doktype pagebrowser or if the module module field is set to pagebrowser
     *
     * @param   array  $content The content given to the function
     * @param   string $conf    TypoScript configuration array
     *
     * @return    boolean
     */
    public function entryLink($content, $conf)
    {

        $exludeUids = in_array($this->cObj->data['uid'], GeneralUtility::trimExplode(',', $conf['excludeUidList']));
        $value = 0;

        ($GLOBALS['TSFE']->page['doktype'] == '21' && $exludeUids != 1) ? $value = '1' : $value = '0';
        if (!$value) {
            ($GLOBALS['TSFE']->page['module'] == 'pbrowser' && $exludeUids != 1) ? $value = '1' : $value = '0';
        }

        return $value;
    }

    /**
     * Used to generate the pagenumbers for a pagebrowser section.
     *
     * @param    array $menuArr The menu array with all valid items
     * @param    array $conf    The TypoScript configuration
     *
     * @return    array    The modified menu array
     */
    public function pageNumbers($menuArr, $conf)
    {

        $stepSize = $conf['parentObj']->conf['1.']['stepSize'];
        $useNumbering = $conf['parentObj']->conf['1.']['useNumbering'];

        if ($stepSize) {
            $boundaries = array();
            // check at which position we are at the moment
            foreach ($menuArr as $key => $value) {
                if ($menuArr[$key]['uid'] == $GLOBALS['TSFE']->id) {
                    $position = $key;
                }
                // get the boundaries
                if (($key % $stepSize) == 0) {
                    $boundaries[] = $key;
                }
                // override titles
                if ($useNumbering == 1) {
                    $menuArr[$key]['title'] = $key + 1;
                }
            }

            // test within which boundary we are and set the offset accordingly
            foreach ($boundaries as $key => $value) {

                if ($position < $value) {
                    $offset = $boundaries[$key - 1];
                    break;
                } else {
                    $offset = $boundaries[$key];
                }
            }

            // return only the items within the current boundaries
            $menuArr = array_slice($menuArr, $offset, $stepSize);
        } else {
            if ($useNumbering == 1) {
                foreach ($menuArr as $key => $value) {
                    // override titles
                    $menuArr[$key]['title'] = $key + 1;
                    // override navigation titles
                    $menuArr[$key]['nav_title'] = $key + 1;
                }
            }
        }

        return $menuArr;
    }

    /* Makes it possible to loop through a whole pagetree with first/prev/index/next/last regardless of levels.
     * Shortcuts and external URLs are also supported. Ignored doktypes are 5-n/in menu, 6-BE user, 7-mountpoint, 255-Recycler.
     * Doktypes that will be skipped in the navigation are 199-Spacer and 254-Sysfolder.
     *
     * @param string $content	Content from TypoScript, emtpy in this case
     * @param array  $conf		Configuration of the Userfunction in TypoScript
     *
     * @return void		The function fills $GLOBAL registers so that the 'Pagebrowser (Reloaded)' TypoScript can be used seamlessly
     *
     */
    public function treePrevNext($content, $conf)
    {

        // first go back to the pagebrowser page ...
        foreach ($GLOBALS['TSFE']->tmpl->rootLine as $ancestor) {
            if ($ancestor['module'] == 'pbrowser' || $ancestor['doktype'] == 21) {
                $entrypoint = $ancestor['uid'];
            }
        }

        // ... and set it as index page for the tree branch
        $GLOBALS['TSFE']->register['index'] = $entrypoint;

        // now collect all pages from this branch (mind: collection will not decend into recyclers or down mountpoint sections)
        $additionalWhere = 'AND doktype NOT IN (7,255)';
        $tree = GeneralUtility::trimExplode(',',
            $this->cObj->getTreeList($entrypoint, 10, 0, false, '', $additionalWhere), 1);

        // get uids to exclude if any
        ($conf['excludeUids']) ? $excludeUids = GeneralUtility::trimExplode(',', $conf['excludeUids'],
            1) : $excludeUids = array();

        // doktypes to skip in the pagebrowser navigation / the following will *always* be skipped)
        $doktypesToSkip = array(0 => 5, 1 => 6, 2 => 7, 3 => 21, 4 => 199, 5 => 254, 6 => 255);
        // if the user has set other doktypes to skip, merge them
        if ($conf['excludeDoktypes']) {
            $excludeDoktypes = GeneralUtility::trimExplode(',', $conf['excludeDoktypes'], 1);
        }
        if (count($excludeDoktypes) > 0) {
            $doktypesToSkip = array_merge($doktypesToSkip, array_diff($excludeDoktypes, $doktypesToSkip));
        }

        // filter the page array for forbidden doktypes, uids to skip and for later treatment of shortcuts and externals
        foreach ($tree as $key => $uid) {
            // get page information
            $pageArray[$uid] = $GLOBALS['TSFE']->sys_page->getRawRecord('pages', $uid,
                'uid,doktype,shortcut,shortcut_mode,url,nav_hide');
            // drop excluded uids, doktypes and pages with nav_hide from page array
            if (in_array($pageArray[$uid]['doktype'], $doktypesToSkip) || in_array($pageArray[$uid]['uid'],
                    $excludeUids)
            ) {
                unset($pageArray[$uid]);
            }
            if ($pageArray[$uid]['nav_hide'] == 1) {
                unset($pageArray[$uid]);
            }
        }

        // reset array keys
        $filteredTree = array_keys($pageArray);

        // use pagenumbers?
        if ($conf['pageNumbers'] == 1) {
            $GLOBALS['TSFE']->register['treeuids'] = implode(',', $filteredTree);
        }

        // determine position of the current page within the tree
        $currentKey = array_search($GLOBALS['TSFE']->id, $filteredTree);
        $prevUid = $filteredTree[$currentKey - 1];
        $nextUid = $filteredTree[$currentKey + 1];
        $prevPages = array_reverse(array_slice($filteredTree, 0, $currentKey));
        $nextPages = array_slice($filteredTree, $currentKey + 1);

        // previous page is a shortcut
        if ($pageArray[$prevUid]['doktype'] == 4) {

            // first determine where this points to
            $shortcutTarget = $GLOBALS['TSFE']->getPageShortcut($pageArray[$prevUid]['shortcut'],
                $pageArray[$prevUid]['shortcut_mode'], $pageArray[$prevUid]['uid']);

            // if the shortcut target doesn't exist or points 'behind' current page or is the current page...
            if (!$shortcutTarget || in_array($shortcutTarget['uid'],
                    $nextPages) || $shortcutTarget['uid'] == $GLOBALS['TSFE']->id
            ) {

                // determine a new valid previous page since the current blocks the way
                $validPrevUid = $this->getValidTreePrevNextPage($prevPages, $nextPages, $pageArray);
                ($validPrevUid) ? $prevUid = $validPrevUid : $prevUid = 0;

            } // else nothing done, shortcut points to valid prev page or outside
        }

        // next page is a shortcut
        if ($pageArray[$nextUid]['doktype'] == 4) {

            // first determine where this points to
            $shortcutTarget = $GLOBALS['TSFE']->getPageShortcut($pageArray[$nextUid]['shortcut'],
                $pageArray[$nextUid]['shortcut_mode'], $pageArray[$nextUid]['uid']);

            // if the shortcut target doesn't exist or points 'behind' current page or is the current page...
            if (!$shortcutTarget || in_array($shortcutTarget['uid'],
                    $prevPages) || $shortcutTarget['uid'] == $GLOBALS['TSFE']->id
            ) {

                // determine a new valid next page since the current blocks the way
                $validNextUid = $this->getValidTreePrevNextPage($nextPages, $prevPages, $pageArray);
                ($validNextUid) ? $nextUid = $validNextUid : $nextUid = 0;
            }
        } // else nothing done, shortcut points to valid next page or outside

        // set values to $GLOBAL registers
        // if first and last pages are shortcuts... well, this is not tested here
        $GLOBALS['TSFE']->register['first'] = $filteredTree[0];
        $GLOBALS['TSFE']->register['previous'] = $prevUid;
        $GLOBALS['TSFE']->register['next'] = $nextUid;
        $GLOBALS['TSFE']->register['last'] = end($filteredTree);

        // if looping is configured, prev/next link to first/last page in branch
        if (!$GLOBALS['TSFE']->register['next'] && $conf['browserLoop'] == 1) {
            $GLOBALS['TSFE']->register['next'] = $GLOBALS['TSFE']->register['first'];
            // if there is no prev page we are on the very first page; set this
        } elseif (!$GLOBALS['TSFE']->register['next'] && $conf['browserLoop'] != 1) {
            $GLOBALS['TSFE']->register['next'] = $GLOBALS['TSFE']->register['last'];
        }

        if (!$GLOBALS['TSFE']->register['previous'] && $conf['browserLoop'] == 1) {
            $GLOBALS['TSFE']->register['previous'] = $GLOBALS['TSFE']->register['last'];
        } elseif (!$GLOBALS['TSFE']->register['previous'] && $conf['browserLoop'] != 1) {
            $GLOBALS['TSFE']->register['previous'] = $GLOBALS['TSFE']->register['first'];
        }

        return $content;
    }

    /* Finds the next 'valid' page in the tree for prev/next navigation. Valid means one of the supported doktypes or, if shortcut,
     * not pointing 'behind' the current page (that would block the prev/next flow).
     *
     * @param array $pagesToCheck	Array consisting of uids to check (either the prev/next pages)
     * @param array	$disallowedPages	Array with uids that may not be allowed as prev/next targets
     * @param array	$pageArray	The full page array for the prev/navigation
     *
     * @return integer
     *
     */
    public function getValidTreePrevNextPage($pagesToCheck, $disallowedPages, $pageArray)
    {

        foreach ($pagesToCheck as $key => $value) {

            if ($pageArray[$value]['doktype'] == 4) {
                if ($key == 0) {
                    continue;
                }
                $target = $GLOBALS['TSFE']->getPageShortcut($pageArray[$value]['shortcut'],
                    $pageArray[$value]['shortcut_mode'], $pageArray[$value]['uid']);
                if (!$target || in_array($target['uid'], $disallowedPages) || $target['uid'] == $GLOBALS['TSFE']->id) {
                    // not valid, page points 'behind'
                    continue;
                } else {
                    $validPageUid = $value;
                    break;
                }
                // next valid prev page
            } else {
                $validPageUid = $value;
                break;
            }
        }

        return $validPageUid;
    }
}
