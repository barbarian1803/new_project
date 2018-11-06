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
define('ST_POQUOT', 999);


$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "..";
include($path_to_root . "/purchasing/includes/po_class.inc");

include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/db/po_quot_db.inc");
include($path_to_root . "/purchasing/includes/purchasing_quot_ui.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Order Quotation"), false, false, "", $js);


if (!isset($_GET['trans_no']) && !isset($_POST['trans_no'])) {
    die("<br>" . _("This page must be called with a purchase order number to review."));
}

if (!isset($_GET['approval_code']) && !isset($_POST['approval_code'])) {
    die("<br>" . _("This page must be called with an approval code to review."));
}

if(isset($_POST['trans_no'])){
    $_GET['trans_no'] = $_POST['trans_no'];
}

if(isset($_POST['approval_code'])){
    $_GET['approval_code'] = $_POST['approval_code'];
}

$purchase_order = new purch_order;
read_po_quot($_GET['trans_no'],$_GET['approval_code'], $purchase_order);


if(isset($_POST['approve']) && $_POST['approve']==1){
    approve_po_quot($_GET['trans_no'],$purchase_order);
    display_notification_centered("PO Quotation approved");
    end_page();
    return;
}else if(isset($_POST['approve']) && $_POST['approve']==-1){
    reject_po_quot($_GET['trans_no'],$purchase_order);
    display_notification_centered("PO Quotation rejected");
    end_page();
    return;
}else if(isset($_POST['approve']) && $_POST['approve']==0){
    display_notification_centered("PO Quotation status is pending");
    end_page();
    return;
}



display_heading(_("Purchase Order Quotation") . " #" . $_GET['trans_no']);

echo "<br>";
display_po_quot_summary($purchase_order, true);

start_table(TABLESTYLE, "width='90%'", 6);
echo "<tr><td valign=top>"; // outer table

display_heading2(_("Line Details"));

start_table(TABLESTYLE, "width='100%'");

$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"),
    _("Requested By"), _("Line Total"));
table_header($th);
$total = $k = 0;
$overdue_items = false;

foreach ($purchase_order->line_items as $stock_item) {

    $line_total = $stock_item->quantity * $stock_item->price;

    // if overdue and outstanding quantities, then highlight as so
    if (($stock_item->quantity - $stock_item->qty_received > 0) &&
            date1_greater_date2(Today(), $stock_item->req_del_date)) {
        start_row("class='overduebg'");
        $overdue_items = true;
    } else {
        alt_table_row_color($k);
    }

    label_cell($stock_item->stock_id);
    label_cell($stock_item->item_description);
    $dec = get_qty_dec($stock_item->stock_id);
    qty_cell($stock_item->quantity, false, $dec);
    label_cell($stock_item->units);
    amount_decimal_cell($stock_item->price);
    label_cell($stock_item->req_del_date);
    amount_cell($line_total);
    end_row();

    $total += $line_total;
}

$display_sub_tot = number_format2($total, user_price_dec());
label_row(_("Sub Total"), $display_sub_tot, "align=right colspan=6", "nowrap align=right");

$taxes = $purchase_order->get_taxes();
$tax_total = display_edit_tax_items($taxes, 6, $purchase_order->tax_included);

$display_total = price_format(($total + $tax_total));

label_row(_("Amount Total"), $display_total, "colspan=6 align='right'", "align='right'");

end_table();

if ($overdue_items)
    display_note(_("Marked items are overdue."), 0, 0, "class='overduefg'");


end_table(1); // outer table

if($purchase_order->is_approved==0){
    start_form();
    hidden("trans_no",$purchase_order->order_no);
    hidden("approval_code",$_GET['approval_code']);
    start_table();
    start_row();
    echo "<td>";
    echo "Approval status: ";
    echo "</td>";
    echo "<td>";
    echo array_selector("approve", null,array(0=>"Pending",-1=>"Rejected",1=>"Accepted"));
    echo "</td>";
    echo "<td>";
    submit_cells("Submit", "submit");
    echo "</td>";
    end_row();
    end_table();
    end_form();
}else if ($purchase_order->is_approved==1){
    echo "<center>";
    echo "<h2>This PO Quotation has been approved</h2>";
    echo "</center>";
}else if ($purchase_order->is_approved==-1){
    echo "<center>";
    echo "<h2>This PO Quotation has been rejected</h2>";
    echo "</center>";
}


//----------------------------------------------------------------------------------------------------

end_page();

