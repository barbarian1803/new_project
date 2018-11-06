<?php
## MODIFIED_START
/**********************************************************************
***********************************************************************/

$page_security = 'SA_SUPPLIERPAYMNT';
$path_to_root = "..";
include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

include_once($path_to_root . "/includes/db/cheque_data.php");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(_($help_context = "Cheque reminder"), false, false, "", $js);

if(get_post("deposit")){
    foreach($_POST["deposit"] as $key=>$val){
        if($val){
            deposit_cheque($key,ST_SUPPAYMENT);
            display_notification_centered(_("Cheque has been deposited"));
        }
    }
    
    $Ajax->activate("_page_body");
}

if(isset($_POST["deposit_status"])){
    $deposit_status = $_POST["deposit_status"];
}else{
    $deposit_status = 2;
    $_POST["deposit_status"] = 2;
}

$sql = get_sql_for_cheque_data(ST_SUPPAYMENT,$deposit_status);

$cols = array(
            _("Payment #") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'),
            _("Cheque no") => array('align'=>'center'), 
            _("Date") => array('type'=>'date','align'=>'center'),
            _("Deposit") => array('fun'=>'is_deposited_view', 'ord'=>'', 'align'=>'center')
	);

$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->width = "85%";
start_form();

start_table();
array_selector_row("Deposit status", "deposit_status", null, array(0=>"No",1=>"Yes",2=>"All"), array("select_submit"=>true));
end_table(1);

display_db_pager($table);
end_form();

end_page();

function trans_view($trans)
{
	return get_trans_view_str(ST_SUPPAYMENT, $trans["trans_no"]);
}

function is_deposited_view($trans){
    if($trans["is_deposited"])
        return "Yes";
    else
        $no = $trans['trans_no'];
        return checkbox("", "deposit[".$no."]",null,true);
}

## MODIFIED_END