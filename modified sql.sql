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
