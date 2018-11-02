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

$sql = get_sql_for_cheque_data(ST_SUPPAYMENT);

$cols = array(
            _("Paymnet #") => array('fun'=>'trans_view', 'ord'=>'', 'align'=>'right'),
            _("Cheque no") => array('align'=>'center'), 
            _("Date") => array('type'=>'date','align'=>'center')
	);

$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->width = "85%";

display_db_pager($table);


end_page();

function trans_view($trans)
{
	return get_trans_view_str(ST_SUPPAYMENT, $trans["trans_no"]);
}

## MODIFIED_END