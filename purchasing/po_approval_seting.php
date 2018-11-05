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


if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{
	$input_error = 0;
        
        if(check_value("need_approval")==1 && strlen($_POST["email"])==0){
            $input_error = 1;
            display_error(_("The email cannot be empty if you don't check need approval"));
            set_focus('email');
        }
        
	if ($input_error != 1){
            if ($selected_id != -1){
                update_one_rule($selected_id,$_POST['rule_type'],input_num('rule_threshold'),$_POST['need_approval'],$_POST['email']);
                $note = _('Selected rule has been updated');
            }else{
    		insert_one_rule($_POST['rule_type'],input_num('rule_threshold'),$_POST['need_approval'],$_POST['email']);
                $note = _('New rule has been added');
            }
            display_notification($note);    	
            $Mode = 'RESET';
	}
} 

if ($Mode == 'Delete'){
	delete_one_rule($selected_id);
        display_notification(_('Selected rule has been deleted'));
        $Mode = 'RESET';
} 

if ($Mode == 'RESET'){
	$selected_id = -1;
	unset($_POST);
}


//-----------------------------------------------------------------------------------

$result = get_approval_rule();

$opt = array("<","<=","=",">=",">");

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
	label_cell($opt[$myrow["rule_type"]]." ".number_format($myrow["rule_threshold"]));
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

if ($selected_id != -1) {
    if ($Mode == 'Edit') {
            //editing an existing area
            $myrow = get_one_rule($selected_id);

            $_POST['rule_type']  = $myrow["rule_type"];
            $_POST['rule_threshold']  = $myrow["rule_threshold"];
            $_POST['need_approval']  = $myrow["need_approval"];
            $_POST['email']  = $myrow["email"];
    }
    hidden("selected_id", $selected_id);
}



array_selector_row("Rule type", "rule_type", null,$opt);

amount_row(_("Amount:"), 'rule_threshold');

check_row("Need approval:", "need_approval",null,true);

if(isset($_POST["need_approval"])){
    $Ajax->activate("_page_body");
}

if(check_value("need_approval")==1){
    text_row_ex(_("Email:"), 'email', 25,255);
}else{
    hidden("email", "");
}

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');


end_form();
//-----------------------------------------------------------------------------------
end_page();