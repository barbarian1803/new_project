<?php
## MODIFIED_START
/**********************************************************************
***********************************************************************/


$path_to_root = "..";
$page_security = 'SA_PURCHASEORDER';

include($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/db/po_approval_db.php");


page(_($help_context = "PO Approval setting"));
simple_page_mode(true);
//-----------------------------------------------------------------------------------

$selected_id = -1;
$rule_type = null;
$rule_threshold = 0;
$need_approval = 0;
$email = "";

$result = get_approval_rule();


start_form();

start_table(TABLESTYLE);
$th = array("No", "Rule","Need approval","Email","","");
inactive_control_column($th);

table_header($th);

$k = 0;
$no = 1;
while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);
        label_cell($no++);
	label_cell($myrow["rule_type"].$myrow["rule_threshold"]);
        if($myrow["need_approval"]){
            label_cell("Yes");
        }else{
            label_cell("No");
        }
        label_cell($myrow["email"]);
        edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
	end_row();
}

end_table(1);



start_table(TABLESTYLE2);

array_selector_row("Rule type", "rule_type", $selected_id, array("<","<=","=",">=",">"), $options=null);

amount_row(_("Amount:"), 'rule_threshold',$rule_threshold);

check_row("Need approval:", "need_approval",$need_approval, FALSE);

text_row_ex(_("Email:"), 'email', 25,null,null,$email);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');


end_form();
//-----------------------------------------------------------------------------------
end_page();