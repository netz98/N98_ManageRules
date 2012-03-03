<?php
/**
 * Copyright © 2012
 * netz98 new media GmbH. All rights reserved.
 *
 * The use and redistribution of this software, either compiled or uncompiled,
 * with or without modifications are permitted provided that the
 * following conditions are met:
 *
 * 1. Redistributions of compiled or uncompiled source must contain the above
 * copyright notice, this list of the conditions and the following disclaimer:
 *
 * 2. All advertising materials mentioning features or use of this software must
 * display the following acknowledgement:
 * "This product includes software developed by the netz98 new media GmbH, Mainz."
 *
 * 3. The name of the netz98 new media GmbH may not be used to endorse or promote
 * products derived from this software without specific prior written permission.
 *
 * 4. License holders of the netz98 new media GmbH are only permitted to
 * redistribute altered software, if this is licensed under conditions that contain
 * a copyleft-clause.
 *
 * This software is provided by the netz98 new media GmbH without any express or
 * implied warranties. netz98 is under no condition liable for the functional
 * capability of this software for a certain purpose or the general usability.
 * netz98 is under no condition liable for any direct or indirect damages resulting
 * from the use of the software.
 * Liability and Claims for damages of any kind are excluded.
 */

/**
 * @category n98
 * @package N98_ManageRules
 */
class N98_ManageRules_Block_Adminhtml_Promo_Catalog_Grid extends Mage_Adminhtml_Block_Promo_Catalog_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('n98_managerules/promo_catalog_grid.phtml');
    }

    /**
     * @return Mage_CatalogRule_Model_Mysql4_Rule_Collection
     */
    protected function _getRuleCollection()
    {
        $collection = Mage::getModel('catalogrule/rule')
                ->getResourceCollection();
        return $collection;
    }

    /**
     * @return array
     */
    public function getTableData()
    {
        $data = array();

        foreach (Mage::getModel('core/website')->getCollection() as $website) { /* @var $website Mage_Core_Model_Website */
            $data[$website->getId()] = array('_object' => $website, '_rules' => array());
        }

        $rules = $this->_getRuleCollection();
        if (count($rules) > 0) {
            foreach ($rules as $rule) { /* @var $rule Mage_CatalogRule_Model_Rule */
                foreach ($rule->getWebsiteIds() as $ruleWebsiteId) {
                    $data[$ruleWebsiteId]['_rules'][] = array(
                        'sort_order' => $rule->getSortOrder(),
                        'is_active'  => $rule->getIsActive(),
                        '_object'    => $rule,
                    );
                }
            }
        }

        foreach ($data as $websiteId => &$websiteData) {
            if (count($websiteData['_rules']) == 0) {
                // remove empty rule sets
                unset($data[$websiteId]);
                continue;
            }
            usort($websiteData['_rules'], array($this, '_sortData'));
        }

        return $data;
    }

    /**
     * Sort rule data by website, sort order
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortData($a, $b)
    {
        if ($a['sort_order'] > $b['sort_order']) {
            return 1;
        } elseif ($a['sort_order'] < $b['sort_order']) {
            return -1;
        }
        return 0;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return string
     */
    public function formatWebsiteName(Mage_Core_Model_Website $website)
    {
        return $website->getName();
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleName(Mage_Rule_Model_Rule $rule)
    {
        $linkStyle = '';
        $name = $rule->getName();
        if (!$rule->getIsActive() || $rule->getToDate() < date('Y-m-d')) {
            $linkStyle = 'text-decoration: none;';
            $name = '<span style="text-decoration:line-through; color: #aaa;">' . $name . '</span>';
        }
        return sprintf('<a href="%s" style="%s">%s</a>',
            $this->getUrl('*/promo_catalog/edit', array('id' => $rule->getId())),
            $linkStyle,
            $name
        );
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRulePriority(Mage_Rule_Model_Rule $rule)
    {
        return $rule->getSortOrder();
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleStopProcessing(Mage_Rule_Model_Rule $rule)
    {
        return $rule->getStopRulesProcessing() ? Mage::helper('catalogrule')->__('Yes') : Mage::helper('catalogrule')->__('No');
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleDateStart(Mage_Rule_Model_Rule $rule)
    {
        return Mage::helper('core')->formatDate($rule->getFromDate());
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleDateExpire(Mage_Rule_Model_Rule $rule)
    {
        if ($rule->getToDate() == null) {
            return '';
        }
        return Mage::helper('core')->formatDate($rule->getToDate());
    }
}