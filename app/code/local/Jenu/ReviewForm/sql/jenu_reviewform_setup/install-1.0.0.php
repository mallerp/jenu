<?php


$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('review/review_detail'),'email_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_VARCHAR,
        'nullable'  => false,
        'length'    => 60,
        'comment'   => 'Email Address'
    ));
$installer->getConnection()
    ->addColumn($installer->getTable('review/review_detail'),'products_id', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable'  => true,
        'length'    => 255,
        'comment'   => 'Products Used'
    ));
$installer->endSetup();
