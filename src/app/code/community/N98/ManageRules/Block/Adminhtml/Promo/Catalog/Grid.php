<?php
/**
 * Magento Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    N98
 * @package     N98_ManageRules
 * @copyright   Copyright (c) 2013 netz98 new media GmbH. (http://www.netz98.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Overwrites admin grid view of catalog rules.
 */
class N98_ManageRules_Block_Adminhtml_Promo_Catalog_Grid extends Mage_Adminhtml_Block_Promo_Catalog_Grid
{
    /**
     * @var array
     */
    protected $_customerGroupNames = array();

    /**
     * changed by n98
     * @var int
     */
    protected $_defaultLimit    = 100;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->_loadCustomerGroupNames();
        $this->setTemplate('n98_managerules/promo_catalog_grid.phtml');
    }


    /**
     * @return array
     */
    protected function _loadCustomerGroupNames()
    {
        $this->_customerGroupNames = Mage::getResourceModel('customer/group_collection')
                                        ->load()
                                        ->toOptionHash();
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

        $rules =  $this->getCollection();
        if (count($rules) > 0) {
            foreach ($rules as $rule) { /* @var $rule Mage_CatalogRule_Model_Rule */
                foreach ($rule->getWebsiteIds() as $ruleWebsiteId) {
                    if (!isset($data[$ruleWebsiteId])) {
                        continue; // for incorrect enterprise edition demo data
                    }
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
    public function formatRuleName($rule)
    {
        $linkStyle = '';
        $name = $rule->getName();
        if (!$rule->getIsActive()
            || ($rule->getToDate() < date('Y-m-d') && $rule->getToDate() !== null)
        ) {
            $linkStyle = 'text-decoration: none;';
            $name = '<span style="text-decoration:line-through; color: #aaa;">' . $name . '</span>';
        }
        return sprintf('<a href="%s" style="%s" title="%s">%s</a>',
            $this->getUrl('*/promo_catalog/edit', array('id' => $rule->getId())),
            $linkStyle,
            'ID: ' . $rule->getId(),
            $name
        );
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRulePriority($rule)
    {
        return $rule->getSortOrder();
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleStopProcessing($rule)
    {
        return $rule->getStopRulesProcessing() ? Mage::helper('catalogrule')->__('Yes') : Mage::helper('catalogrule')->__('No');
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleDateStart($rule)
    {
        return Mage::helper('core')->formatDate($rule->getFromDate());
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatRuleDateExpire($rule)
    {
        if ($rule->getToDate() == null) {
            return '&#8734;';  // infinity char
        }
        return Mage::helper('core')->formatDate($rule->getToDate());
    }

    /**
     * @param Mage_Rule_Model_Rule $rule
     * @return string
     */
    public function formatCustomerGroups($rule)
    {
        $names = array();
        $ids = $rule->getCustomerGroupIds();
        foreach ($ids as $id) {
            $names[] = $this->_customerGroupNames[$id];
        }
        return implode(', ', $names);
    }
}