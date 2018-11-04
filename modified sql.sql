CREATE TABLE `0_po_quot` (
  `order_no` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL DEFAULT '0',
  `comments` tinytext COLLATE utf8_unicode_ci,
  `ord_date` date NOT NULL DEFAULT '0000-00-00',
  `reference` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `requisition_no` tinytext COLLATE utf8_unicode_ci,
  `into_stock_location` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `delivery_address` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `total` double NOT NULL DEFAULT '0',
  `prep_amount` double NOT NULL DEFAULT '0',
  `alloc` double NOT NULL DEFAULT '0',
  `tax_included` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `approval_code` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`order_no`),
  KEY `ord_date` (`ord_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `0_po_quot_details` (
  `po_detail_item` int(11) NOT NULL AUTO_INCREMENT,
  `po_quot_no` int(11) NOT NULL DEFAULT '0',
  `item_code` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` tinytext COLLATE utf8_unicode_ci,
  `delivery_date` date NOT NULL DEFAULT '0000-00-00',
  `qty_invoiced` double NOT NULL DEFAULT '0',
  `unit_price` double NOT NULL DEFAULT '0',
  `act_price` double NOT NULL DEFAULT '0',
  `std_cost_unit` double NOT NULL DEFAULT '0',
  `quantity_ordered` double NOT NULL DEFAULT '0',
  `quantity_received` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`po_detail_item`),
  KEY `order` (`order_no`,`po_detail_item`),
  KEY `itemcode` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `0_cheque_data` (
  `id` int(11) NOT NULL,
  `trans_type` int(11) NOT NULL,
  `trans_no` int(11) NOT NULL,
  `cheque_no` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `0_cheque_data`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `0_cheque_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;



--
-- Table structure for table `0_po_quot_approval_rule`
--

CREATE TABLE `0_po_quot_approval_rule` (
  `id` int(11) NOT NULL,
  `rule_type` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `rule_threshold` decimal(10,0) NOT NULL,
  `need_approval` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `0_po_quot_approval_rule`
--
ALTER TABLE `0_po_quot_approval_rule`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `0_po_quot_approval_rule`
--
ALTER TABLE `0_po_quot_approval_rule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
