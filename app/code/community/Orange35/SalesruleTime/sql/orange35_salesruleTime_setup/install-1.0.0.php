<?php
$installer = $this;
$installer->startSetup();

$adapter = $installer->getConnection();
$adapter->modifyColumn($installer->getTable('salesrule/rule'), "from_date", Varien_Db_Ddl_Table::TYPE_DATETIME);
$adapter->modifyColumn($installer->getTable('salesrule/rule'), "to_date", Varien_Db_Ddl_Table::TYPE_DATETIME);
$installer->endSetup();
