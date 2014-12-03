<?php

$this->startSetup();

$entityTypeId = $this->getEntityTypeId(Mage_Catalog_Model_Category::ENTITY);
$attributeCode = 'filter_price_ranges';

$this->addAttribute(
    $entityTypeId,
    $attributeCode,
    array(
        'backend'           => 'aurmil_customizepricefilter/catalog_category_attribute_backend_priceranges',
        'type'              => 'varchar',
        'input'             => 'text',
        'label'             => 'Layered Navigation Price Ranges',
        'required'          => false,
        'user_defined'      => true,
        'default'           => null,
        'unique'            => false,
        'note'              => 'Format: ([min1])-[max1];[min2]-[max2];...;[minN]-([maxN]) - min1 and maxN are optional.',
        'input_renderer'    => 'aurmil_customizepricefilter/adminhtml_catalog_category_helper_priceranges',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'           => true,
    )
);

// place this new attribute right after "Layered Navigation Price Step"

$attributeId = $this->getAttributeId($entityTypeId, 'filter_price_range');

$select = $this->_conn->select()
  ->from($this->getTable('eav/entity_attribute'))
  ->where('entity_type_id = ' . $entityTypeId)
  ->where('attribute_id = ' . $attributeId);
$entityAttribute = $this->_conn->fetchRow($select);

$this->addAttributeToGroup(
    $entityTypeId,
    $entityAttribute['attribute_set_id'],
    $entityAttribute['attribute_group_id'],
    $attributeCode,
    $entityAttribute['sort_order'] + 1
);

Mage::getModel('index/indexer')
    ->getProcessByCode('catalog_category_flat')
    ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);

$this->endSetup();
