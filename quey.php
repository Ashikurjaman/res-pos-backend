ALTER TABLE `product` CHANGE `vat_rate` `vat_rate` DECIMAL(15,3) NOT NULL DEFAULT '0';
ALTER TABLE `product` CHANGE `sd_rate` `sd_rate` DECIMAL(15,3) NOT NULL DEFAULT '0';
ALTER TABLE `branch_store` CHANGE `vat_rate` `vat_rate` DECIMAL(15,3) NULL DEFAULT NULL;
ALTER TABLE `branch_store` CHANGE `sd_rate` `sd_rate` DECIMAL(15,3) NULL DEFAULT NULL;
ALTER TABLE `head_office_stock` CHANGE `supplier_id` `supplier_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL;
-- ============================================
-- 1. OUTLET REQUEST (branch asks for stock)
-- ============================================
CREATE TABLE `outlet_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `requesting_outlet_id` bigint(20) UNSIGNED NOT NULL COMMENT 'outlet that needs stock',
  `source_outlet_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'HO outlet id or another outlet id; NULL = HO decides',
  `request_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=HO request, 2=outlet to outlet transfer request',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=pending,1=approved,2=partial_approved,3=rejected,4=despatched,5=received,6=closed',
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `outlet_request_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `requested_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `approved_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `despatched_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `received_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `remarks` varchar(255) DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `idx_req_prod` (`request_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. DESPATCH (goods physically sent out — reduces source stock)
-- ============================================
CREATE TABLE `outlet_despatches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `despatch_no` varchar(50) NOT NULL,
  `despatch_date` date NOT NULL,
  `request_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'null = direct/ad-hoc despatch, no request',
  `source_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=head_office,2=outlet',
  `source_outlet_id` bigint(20) UNSIGNED NOT NULL,
  `dest_outlet_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `despatched_by` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=pending,1=in_transit,2=received,3=partial_received,4=cancelled',
  `total_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `total_amount` decimal(15,3) NOT NULL DEFAULT 0.000,
  `remarks` text DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `idx_source_dest` (`source_outlet_id`,`dest_outlet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `outlet_despatch_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `despatch_id` bigint(20) UNSIGNED NOT NULL,
  `request_detail_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `despatch_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `purchase_price` decimal(15,3) NOT NULL DEFAULT 0.000,
  `total_amount` decimal(15,3) NOT NULL DEFAULT 0.000,
  `remarks` varchar(255) DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `idx_desp_prod` (`despatch_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. RECEIVE (destination outlet confirms — increases dest stock)
-- ============================================
CREATE TABLE `outlet_receives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receive_no` varchar(50) NOT NULL,
  `receive_date` date NOT NULL,
  `despatch_id` bigint(20) UNSIGNED NOT NULL,
  `receiving_outlet_id` bigint(20) UNSIGNED NOT NULL,
  `received_by` bigint(20) UNSIGNED NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=pending,1=complete,2=partial,3=discrepancy',
  `remarks` text DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `outlet_receive_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receive_id` bigint(20) UNSIGNED NOT NULL,
  `despatch_detail_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `despatched_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `received_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `short_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `damage_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `remarks` varchar(255) DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `idx_recv_prod` (`receive_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. STOCK LEDGER (audit trail — same pattern as your supplier_ledger)
-- ============================================
CREATE TABLE `outlet_stock_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `outlet_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `table_name` varchar(50) NOT NULL COMMENT 'outlet_despatch / outlet_receive / sales / head_office_stock',
  `unique_id` bigint(20) UNSIGNED NOT NULL COMMENT 'source record id',
  `in_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `out_qty` decimal(15,3) NOT NULL DEFAULT 0.000,
  `balance_before` decimal(15,3) NOT NULL DEFAULT 0.000,
  `balance_after` decimal(15,3) NOT NULL DEFAULT 0.000,
  `type` tinyint(4) NOT NULL COMMENT '1=IN,2=OUT',
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `validity` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `idx_outlet_product` (`outlet_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
