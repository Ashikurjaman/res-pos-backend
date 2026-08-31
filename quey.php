ALTER TABLE `product` CHANGE `vat_rate` `vat_rate` DECIMAL(15,3) NOT NULL DEFAULT '0';
ALTER TABLE `product` CHANGE `sd_rate` `sd_rate` DECIMAL(15,3) NOT NULL DEFAULT '0';
ALTER TABLE `branch_store` CHANGE `vat_rate` `vat_rate` DECIMAL(15,3) NULL DEFAULT NULL;
ALTER TABLE `branch_store` CHANGE `sd_rate` `sd_rate` DECIMAL(15,3) NULL DEFAULT NULL;
