<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    'test_table' => 'CREATE TABLE `test_table` (
  `smallint` smallint NOT NULL AUTO_INCREMENT,
  `tinyint` tinyint DEFAULT NULL,
  `bigint` bigint DEFAULT \'0\',
  `float` float(12,4) DEFAULT \'0.0000\',
  `double` double(14,6) DEFAULT \'11111111.111111\',
  `decimal` decimal(15,0) DEFAULT \'0\',
  `date` date DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datetime` datetime DEFAULT \'0000-00-00 00:00:00\',
  `longtext` longtext,
  `mediumtext` mediumtext,
  `varchar` varchar(254) DEFAULT NULL,
  `mediumblob` mediumblob,
  `blob` blob,
  `boolean` tinyint(1) DEFAULT NULL,
  UNIQUE KEY `TEST_TABLE_SMALLINT_BIGINT` (`smallint`,`bigint`),
  KEY `TEST_TABLE_TINYINT_BIGINT` (`tinyint`,`bigint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'reference_table' => 'CREATE TABLE `reference_table` (
  `tinyint_ref` tinyint NOT NULL AUTO_INCREMENT,
  `tinyint_without_padding` tinyint NOT NULL DEFAULT \'0\',
  `bigint_without_padding` bigint NOT NULL DEFAULT \'0\',
  `smallint_without_padding` smallint NOT NULL DEFAULT \'0\',
  `integer_without_padding` int NOT NULL DEFAULT \'0\',
  `smallint_with_big_padding` smallint NOT NULL DEFAULT \'0\',
  `smallint_without_default` smallint DEFAULT NULL,
  `int_without_unsigned` int DEFAULT NULL,
  `int_unsigned` int unsigned DEFAULT NULL,
  `bigint_default_nullable` bigint unsigned DEFAULT \'1\',
  `bigint_not_default_not_nullable` bigint unsigned NOT NULL,
  PRIMARY KEY (`tinyint_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8',
    'auto_increment_test' => 'CREATE TABLE `auto_increment_test` (
  `int_auto_increment_with_nullable` int unsigned NOT NULL AUTO_INCREMENT,
  `int_disabled_auto_increment` smallint unsigned DEFAULT \'0\',
  UNIQUE KEY `AUTO_INCREMENT_TEST_INT_AUTO_INCREMENT_WITH_NULLABLE` (`int_auto_increment_with_nullable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8'
];
