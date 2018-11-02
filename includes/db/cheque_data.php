<?php
## MODIFIED_START
function save_cheque_data($trans_type,$payment_no,$cheque_no,$cheque_date){
    $cheque_date = date2sql($cheque_date);
    $sql = "INSERT INTO ".TB_PREF."cheque_data (trans_type, trans_no, cheque_no, date) VALUES (".db_escape($trans_type).",".db_escape($payment_no).",".db_escape($cheque_no).",".db_escape($cheque_date).")";
    db_query($sql, "order Cannot be Added");
}

function get_sql_for_cheque_data($type){
    #SELECT trans_no,cheque_no,date FROM `0_cheque_data` WHERE `date` >= CURDATE()
    $sql = "SELECT trans_no, cheque_no, date FROM ".TB_PREF."cheque_data WHERE trans_type = ". db_escape($type)." AND `date` >= CURDATE()";
    return $sql;
}

## MODIFIED_END