<?php
define('ST_POQUOT', 999);
## MODIFIED_START

$path_to_root = "..";
$page_security = 'SA_PURCHASEORDER';
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_quot_ui.inc");
include_once($path_to_root . "/purchasing/includes/db/suppliers_db.inc");
include_once($path_to_root . "/purchasing/includes/db/po_quot_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");



$js = '';
if ($SysPrefs->use_popup_windows)
    $js .= get_js_open_window(900, 500);
if (user_use_date_picker())
    $js .= get_js_date_picker();

if (isset($_GET['NewOrder'])) {

    $_SESSION['page_title'] = _($help_context = "PO Quotation Entry");
    create_new_po_quot(ST_POQUOT, 0);
    copy_from_cart_quot();
}

page($_SESSION['page_title'], false, false, "", $js);


check_db_has_suppliers(_("There are no suppliers defined in the system."));

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) {
    $order_no = $_GET['AddedID'];
    $trans_type = ST_POQUOT;

    if ($_GET['Approved']==1)
        display_notification_centered(_("PO Quotation has been entered and has been approved"));
    else
        display_notification_centered(_("PO Quotation has been entered and waiting for approval"));
    // TODO, for fixed asset
    hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another PO Quotation"), "NewOrder=yes");
    #hyperlink_no_params($path_to_root."/purchasing/inquiry/po_search.php", _("Select An &Outstanding Purchase Order"));

    display_footer_exit();
}

if ($_SESSION['PO']->fixed_asset)
    check_db_has_purchasable_fixed_assets(_("There are no purchasable fixed assets defined in the system."));
else
    check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));

//--------------------------------------------------------------------------------------------------

function line_start_focus() {
    global $Ajax;

    $Ajax->activate('items_table');
    set_focus('_stock_id_edit');
}

//--------------------------------------------------------------------------------------------------

function unset_form_variables() {
    unset($_POST['stock_id']);
    unset($_POST['qty']);
    unset($_POST['price']);
    unset($_POST['req_del_date']);
}

//---------------------------------------------------------------------------------------------------

function handle_delete_item($line_no) {
    if ($_SESSION['PO']->some_already_received($line_no) == 0) {
        $_SESSION['PO']->remove_from_order($line_no);
        unset_form_variables();
    } else {
        display_error(_("This item cannot be deleted because some of it has already been received."));
    }
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function handle_cancel_po() {
    global $path_to_root;

    //need to check that not already dispatched or invoiced by the supplier
    if (($_SESSION['PO']->order_no != 0) &&
            $_SESSION['PO']->any_already_received() == 1) {
        display_error(_("This order cannot be cancelled because some of it has already been received.")
                . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
        return;
    }

    $fixed_asset = $_SESSION['PO']->fixed_asset;

    if ($_SESSION['PO']->order_no != 0)
        delete_po($_SESSION['PO']->order_no);
    else {
        unset($_SESSION['PO']);

        if ($fixed_asset)
            meta_forward($path_to_root . '/index.php', 'application=assets');
        else
            meta_forward($path_to_root . '/index.php', 'application=AP');
    }

    $_SESSION['PO']->clear_items();
    $_SESSION['PO'] = new purch_order;

    display_notification(_("This purchase order has been cancelled."));

    hyperlink_params($path_to_root . "/purchasing/po_quotation.php", _("Enter a new purchase order"), "NewOrder=Yes");
    echo "<br>";

    end_page();
    exit;
}

//---------------------------------------------------------------------------------------------------

function check_data() {
    if (!get_post('stock_id_text', true)) {
        display_error(_("Item description cannot be empty."));
        set_focus('stock_id_edit');
        return false;
    }

    $dec = get_qty_dec($_POST['stock_id']);
    $min = 1 / pow(10, $dec);
    if (!check_num('qty', $min)) {
        $min = number_format2($min, $dec);
        display_error(_("The quantity of the order item must be numeric and not less than ") . $min);
        set_focus('qty');
        return false;
    }

    if (!check_num('price', 0)) {
        display_error(_("The price entered must be numeric and not less than zero."));
        set_focus('price');
        return false;
    }
    if ($_SESSION['PO']->trans_type == ST_POQUOT && !is_date($_POST['req_del_date'])) {
        display_error(_("The date entered is in an invalid format."));
        set_focus('req_del_date');
        return false;
    }

    return true;
}

//---------------------------------------------------------------------------------------------------

function handle_update_item() {
    $allow_update = check_data();

    if ($allow_update) {
        if ($_SESSION['PO']->line_items[$_POST['line_no']]->qty_inv > input_num('qty') ||
                $_SESSION['PO']->line_items[$_POST['line_no']]->qty_received > input_num('qty')) {
            display_error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") .
                    "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
            set_focus('qty');
            return;
        }

        $_SESSION['PO']->update_order_item($_POST['line_no'], input_num('qty'), input_num('price'), @$_POST['req_del_date'], $_POST['item_description']);
        unset_form_variables();
    }
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function handle_add_new_item() {
    $allow_update = check_data();

    if ($allow_update == true) {
        if (count($_SESSION['PO']->line_items) > 0) {
            foreach ($_SESSION['PO']->line_items as $order_item) {
                /* do a loop round the items on the order to see that the item
                  is not already on this order */
                if (($order_item->stock_id == $_POST['stock_id'])) {
                    display_warning(_("The selected item is already on this order."));
                }
            } /* end of the foreach loop to look for pre-existing items of the same code */
        }

        if ($allow_update == true) {
            $result = get_short_info($_POST['stock_id']);

            if (db_num_rows($result) == 0) {
                $allow_update = false;
            }

            if ($allow_update) {
                $_SESSION['PO']->add_to_order(count($_SESSION['PO']->line_items), $_POST['stock_id'], input_num('qty'), get_post('stock_id_text'), //$myrow["description"], 
                        input_num('price'), '', // $myrow["units"], (retrived in cart)
                        $_SESSION['PO']->trans_type == ST_POQUOT ? $_POST['req_del_date'] : '', 0, 0);

                unset_form_variables();
                $_POST['stock_id'] = "";
            } else {
                display_error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
            }
        } /* end of if not already on the order and allow input was true */
    }
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function can_commit() {
    if (!get_post('supplier_id')) {
        display_error(_("There is no supplier selected."));
        set_focus('supplier_id');
        return false;
    }

    if (!is_date($_POST['OrderDate'])) {
        display_error(_("The entered order date is invalid."));
        set_focus('OrderDate');
        return false;
    }

    if ($_SESSION['PO']->trans_type == ST_POQUOT && get_post('delivery_address') == '') {
        display_error(_("There is no delivery address specified."));
        set_focus('delivery_address');
        return false;
    }
    if (get_post('StkLocation') == '') {
        display_error(_("There is no location specified to move any items into."));
        set_focus('StkLocation');
        return false;
    }
    if (!db_has_currency_rates($_SESSION['PO']->curr_code, $_POST['OrderDate'], true))
        return false;
    if ($_SESSION['PO']->order_has_items() == false) {
        display_error(_("The order cannot be placed because there are no lines entered on this order."));
        return false;
    }
    if (floatcmp(input_num('prep_amount'), $_SESSION['PO']->get_trans_total()) > 0) {
        display_error(_("Required prepayment is greater than total invoice value."));
        set_focus('prep_amount');
        return false;
    }

    return true;
}

function handle_commit_order() {
    $cart = &$_SESSION['PO'];

    if (can_commit()) {

        copy_to_cart_quot();
        new_doc_date($cart->orig_order_date);
        
        if ($cart->order_no == 0) { // new po quot
            $trans_no = add_po_quot_process($cart);
            if ($trans_no) {
                $result = validate_approval($cart,$trans_no);
                unset($_SESSION['PO']);
                if($result!=-1){
                    meta_forward($_SERVER['PHP_SELF'], "AddedID=$result&Approved=1");
                }else{
                    meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no&Approved=0");
                }
            }
        }
    }
}

//---------------------------------------------------------------------------------------------------
if (isset($_POST['update'])) {
    copy_to_cart_quot();
    $Ajax->activate('items_table');
}

$id = find_submit('Delete');
if ($id != -1)
    handle_delete_item($id);

if (isset($_POST['Commit'])) {
    handle_commit_order();
}
if (isset($_POST['UpdateLine']))
    handle_update_item();

if (isset($_POST['EnterLine']))
    handle_add_new_item();

if (isset($_POST['CancelOrder']))
    handle_cancel_po();

if (isset($_POST['CancelUpdate']))
    unset_form_variables();

if (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

start_form();

display_po_header_quot($_SESSION['PO']);
echo "<br>";

display_po_items_quot($_SESSION['PO']);

start_table(TABLESTYLE2);


if ($_SESSION['PO']->trans_type == ST_SUPPINVOICE) {
    cash_accounts_list_row(_("Payment:"), 'cash_account', null, false, _('Delayed'));
}

textarea_row(_("Memo:"), 'Comments', null, 70, 4);

end_table(1);

div_start('controls', 'items_table');
$process_txt = _("Place Order");
$update_txt = _("Update Order");
$cancel_txt = _("Cancel Order");
if ($_SESSION['PO']->trans_type == ST_SUPPRECEIVE) {
    $process_txt = _("Process GRN");
    $update_txt = _("Update GRN");
    $cancel_txt = _("Cancel GRN");
} elseif ($_SESSION['PO']->trans_type == ST_SUPPINVOICE) {
    $process_txt = _("Process Invoice");
    $update_txt = _("Update Invoice");
    $cancel_txt = _("Cancel Invoice");
}
if ($_SESSION['PO']->order_has_items()) {
    if ($_SESSION['PO']->order_no)
        submit_center_first('Commit', $update_txt, '', 'default');
    else
        submit_center_first('Commit', $process_txt, '', 'default');
    submit_center_last('CancelOrder', $cancel_txt);
} else
    submit_center('CancelOrder', $cancel_txt, true, false, 'cancel');
div_end();
//---------------------------------------------------------------------------------------------------

end_form();
end_page();
