<?php
## MODIFIED_START
function save_cheque_data($trans_type,$payment_no,$cheque_no,$cheque_date){
    $cheque_date = date2sql($cheque_date);
    $sql = "INSERT INTO ".TB_PREF."cheque_data (trans_type, trans_no, cheque_no, date, is_deposited) VALUES (".db_escape($trans_type).",".db_escape($payment_no).",".db_escape($cheque_no).",".db_escape($cheque_date).",0)";
    db_query($sql, "cheque be Added");
}

function get_sql_for_cheque_data($type,$deposit_status=2){
    #SELECT trans_no,cheque_no,date FROM `0_cheque_data` WHERE `date` >= CURDATE()
    if($deposit_status==2){
        $append = "";
    }else{
        $append = " AND `is_deposited` = ".$deposit_status;
    }
    $sql = "SELECT trans_no, cheque_no, date, is_deposited FROM ".TB_PREF."cheque_data WHERE trans_type = ". db_escape($type).$append;
    return $sql;
}

function deposit_cheque($trans_no,$type){
    $sql = "UPDATE ".TB_PREF."cheque_data SET is_deposited=1 WHERE trans_type = ". db_escape($type)." AND trans_no=".db_escape($trans_no);
    db_query($sql, "cheque Cannot be update");
}
## MODIFIED_END