<?php

/* * ********************************************************************
  Copyright (C) FrontAccounting, LLC.
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 * ********************************************************************* */
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/purchasing/includes/db/po_quot_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 600);
if (user_use_date_picker())
    $js .= get_js_date_picker();
page(_($help_context = "Search Outstanding Purchase Orders Quotation"), false, false, "", $js);

if (isset($_GET['order_number'])) {
    $_POST['order_number'] = $_GET['order_number'];
}
//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) {
    $Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) {
    $disable = get_post('order_number') !== '';

    $Ajax->addDisable(true, 'OrdersAfterDate', $disable);
    $Ajax->addDisable(true, 'OrdersToDate', $disable);
    $Ajax->addDisable(true, 'StockLocation', $disable);
    $Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
    $Ajax->addDisable(true, 'SelectStockFromList', $disable);

    if ($disable) {
        $Ajax->addFocus(true, 'order_number');
    } else
        $Ajax->addFocus(true, 'OrdersAfterDate');

    $Ajax->activate('orders_tbl');
}


//---------------------------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();
date_cells(_("from:"), 'OrdersAfterDate', '', null, -user_transaction_days());
date_cells(_("to:"), 'OrdersToDate');
echo "<td>Status: </td><td>" . array_selector("status", null, array(2 => "All", 0 => "Pending", -1 => "Rejected", 1 => "Accepted")) . "</td>";
submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
end_row();
end_table(1);

//---------------------------------------------------------------------------------------------
function trans_view($trans) {
    return viewer_link($trans["order_no"], "purchasing/po_quot_view.php?no_menu=1&trans_no=".$trans['order_no']);
    //return get_trans_view_str(ST_PURCHORDER, $trans["order_no"]);
}

function status_view($trans){
    if($trans["is_approved"]==1){
        return "Accepted";
    }
    if($trans["is_approved"]==0){
        return "Pending";
    }
    if($trans["is_approved"]==-1){
        return "Rejected";
    }
}

//---------------------------------------------------------------------------------------------
//figure out the sql required from the inputs available
$sql = get_sql_for_po_quot_search(get_post('OrdersAfterDate'), get_post('OrdersToDate'), get_post('status'));

//$result = db_query($sql,"No orders were returned");

/* show a table of the orders returned by the sql */
$cols = array(
    _("#") => array('fun' => 'trans_view', 'ord' => ''),
    _("Supplier") => array('ord' => ''),
    _("Supplier's Reference"),
    _("Order Date") => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'),
    _("Currency") => array('align' => 'center'),
    _("Order Total") => 'amount',
    _("Status") => array('fun' => 'status_view','align' => 'center', 'ord' => '')
);

$table = & new_db_pager('orders_tbl', $sql, $cols);
$table->width = "65%";

display_db_pager($table);

end_form();
end_page();
