<?php

## MODIFIED START
include_once($path_to_root . "/purchasing/includes/db/po_approval_db.php");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces [] = $keyspace[rand(0, $max)];
    }
    return implode('', $pieces);
}

function add_po_quot(&$po_obj) {
    global $Refs;

    begin_transaction();
    hook_db_prewrite($po_obj, ST_POQUOT);

    $is_approved = false;
    $approval_code = random_str(14);

    /* Insert to purchase order header record */
    $sql = "INSERT INTO " . TB_PREF . "po_quot (supplier_id, Comments, ord_date, reference, 
     	requisition_no, into_stock_location, delivery_address, total, tax_included, prep_amount,is_approved,approval_code) VALUES(";
    $sql .= db_escape($po_obj->supplier_id) . "," .
            db_escape($po_obj->Comments) . ",'" .
            date2sql($po_obj->orig_order_date) . "', " .
            db_escape($po_obj->reference) . ", " .
            db_escape($po_obj->supp_ref) . ", " .
            db_escape($po_obj->Location) . ", " .
            db_escape($po_obj->delivery_address) . ", " .
            db_escape($po_obj->get_trans_total()) . ", " .
            db_escape($po_obj->tax_included) . ", " .
            db_escape($po_obj->prep_amount) . ", " .
            db_escape($is_approved) . ", " .
            db_escape($approval_code)
            . ")";

    db_query($sql, "The po_quote header record could not be inserted");

    /* Get the auto increment value of the order number created from the sql above */
    $po_obj->order_no = db_insert_id();

    /* Insert the purchase order detail records */
    foreach ($po_obj->line_items as $line_no => $po_line) {
        $sql = "INSERT INTO " . TB_PREF . "po_quot_details (po_quot_no, item_code, description, delivery_date,	unit_price,	quantity_ordered) VALUES (";
        $sql .= $po_obj->order_no . ", " . db_escape($po_line->stock_id) . "," .
                db_escape($po_line->item_description) . ",'" .
                date2sql($po_line->req_del_date) . "'," .
                db_escape($po_line->price) . ", " .
                db_escape($po_line->quantity) . ")";
        db_query($sql, "One of the purchase order detail records could not be inserted");
        $po_obj->line_items[$line_no]->po_detail_rec = db_insert_id();
    }

    add_audit_trail(ST_POQUOT, $po_obj->order_no, $po_obj->orig_order_date);
    hook_db_postwrite($po_obj, ST_POQUOT);
    commit_transaction();

    return $po_obj->order_no;
}

function add_po_quot_process($cart) {
    global $Refs, $type_shortcuts;

    $ref = $cart->reference;

    $order_no = add_po_quot($cart);
    $cart->order_no = $order_no;

    return $order_no;
}

function approve_po_quot($trans_no, $cart) {
    global $Refs;
    $sql = "UPDATE " . TB_PREF . "po_quot SET is_approved = '1' WHERE order_no=" . db_escape($trans_no);
    db_query($sql);
    // copy po quot to purchase order
    $cart->trans_type = ST_PURCHORDER;
    $cart->reference = $Refs->get_next(ST_PURCHORDER, null, array('supplier_id' => $cart->supplier_id, 'date' => sql2date(date("Y-m-d"))));
    $trans_no = add_direct_supp_trans($cart);
    return $trans_no;
}

function url($url) {
  $result = parse_url($url);
  return $result['scheme']."://".$result['host'];
}

function send_email_approval($trans_no, $email) {
    $sql = "SELECT * FROM " . TB_PREF . "po_quot WHERE order_no=" . db_escape($trans_no);
    $data = db_fetch(db_query($sql));
    $code = $data["approval_code"];
    
    $url = "http://".$_SERVER["HTTP_HOST"]."purchasing/po_quot_review.php?trans_no=".$trans_no."&approval_code=".$code;
    
    $subject = "Purchase Order Quotation Approval";
    $headers = "From: Frontaccounting web system\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $message = "
      <html>
          <body>
             <p>
                New purchase order quotation that needs approval has been inputted. Please check and review the PO Quotation from this link:
                <a href='".$url."'>".$url."</a>
             </p>
          </body>
      </html>
    ";
    
    mail($email, $subject, $message, $headers);
}

function validate_approval($cart, $trans_no) {
    $total = $cart->get_trans_total();
    $rules = get_approval_rule();
    $rule_sign = array("<", "<=", "=", ">=", ">");
    while ($rule = db_fetch($rules)) {
        $rule_type = $rule_sign[$rule["rule_type"]];
        $rule_threshold = $rule["rule_threshold"];
        $need_approval = $rule["need_approval"];
        $email = $rule["email"];

        $check = false;

        switch ($rule_type) {
            case "<":
                $check = ($total < $rule_threshold);
                break;
            case "<=":
                $check = ($total <= $rule_threshold);
                break;
            case "=":
                $check = ($total = $rule_threshold);
                break;
            case ">":
                $check = ($total > $rule_threshold);
                break;
            case ">=":
                $check = ($total >= $rule_threshold);
                break;
        }

        if ($check) {
            if ($need_approval) {
                send_email_approval($trans_no, $email);
                return -1;
            } else {
                return approve_po_quot($trans_no, $cart);
                ;
            }
        }
    }
}

//----------------------------------------------------------------------------------------

function read_po_quot_header($order_no,$approval_code, &$order) {
    $sql = "SELECT po.*, supplier.*, loc.location_name 
		FROM " . TB_PREF . "po_quot po,"
            . TB_PREF . "suppliers supplier,"
            . TB_PREF . "locations loc
		WHERE po.supplier_id = supplier.supplier_id
		AND loc.loc_code = into_stock_location
		AND po.order_no = " . db_escape($order_no).
                "AND approval_code = ". db_escape($approval_code);

    $result = db_query($sql, "The order cannot be retrieved");

    if (db_num_rows($result) == 1) {

        $myrow = db_fetch($result);

        $order->trans_type = ST_POQUOT;
        $order->order_no = $order_no;

        $order->set_supplier($myrow["supplier_id"], $myrow["supp_name"], $myrow["curr_code"], $myrow['tax_group_id'], $myrow["tax_included"]);

        $order->credit = get_current_supp_credit($order->supplier_id);

        $order->orig_order_date = sql2date($myrow["ord_date"]);
        $order->Comments = nl2br($myrow["comments"]);
        $order->Location = $myrow["into_stock_location"];
        $order->supp_ref = $myrow["requisition_no"];
        $order->reference = $myrow["reference"];
        $order->delivery_address = $myrow["delivery_address"];
        $order->alloc = $myrow["alloc"];
        $order->prep_amount = $myrow["prep_amount"];
        $order->prepayments = null;
        
        $order->is_approved = $myrow["is_approved"];
        
        return true;
    }

    display_db_error("FATAL : purchase order quotation not found or the code is wrong!", "", true);
    return false;
}

//----------------------------------------------------------------------------------------

function read_po_quot_items($order_no, &$order, $open_items_only = false) {
    /* now populate the line po array with the purchase order details records */

    $sql = "SELECT poline.*, units
		FROM " . TB_PREF . "po_quot_details poline
			LEFT JOIN " . TB_PREF . "stock_master item	ON poline.item_code=item.stock_id
		WHERE po_quot_no =" . db_escape($order_no);

    if ($open_items_only)
        $sql .= " AND (poline.quantity_ordered > poline.quantity_received) ";

    $sql .= " ORDER BY po_detail_item";

    $result = db_query($sql, "The lines on the purchase order cannot be retrieved");

    if (db_num_rows($result) > 0) {
        while ($myrow = db_fetch($result)) {
            $data = get_purchase_data($order->supplier_id, $myrow['item_code']);
            if ($data !== false) {
                if ($data['supplier_description'] != "")
                    $myrow['description'] = $data['supplier_description'];
            }
            if (is_null($myrow["units"])) {
                $units = "";
            } else {
                $units = $myrow["units"];
            }

            if ($order->add_to_order($order->lines_on_order, $myrow["item_code"], $myrow["quantity_ordered"], $myrow["description"], $myrow["unit_price"], $units, sql2date($myrow["delivery_date"]), $myrow["qty_invoiced"], $myrow["quantity_received"])) {
                $order->line_items[$order->lines_on_order - 1]->po_detail_rec = $myrow["po_detail_item"];
            }
        } /* line po from purchase order details */
    } //end of checks on returned data set
}

//----------------------------------------------------------------------------------------

function read_po_quot($order_no,$approval_code, &$order, $open_items_only = false) {
    $result = read_po_quot_header($order_no,$approval_code, $order);

    if ($result)
        read_po_quot_items($order_no, $order, $open_items_only);
}

//----------------------------------------------------------------------------------------