<?php

## MODIFIED_START
/* * ********************************************************************
 * ********************************************************************* */

function get_approval_rule() {
    $sql = "SELECT id,rule_type,rule_threshold,need_approval,email FROM " . TB_PREF . "po_quot_approval_rule ORDER BY rule_threshold ASC";
    return db_query($sql);
}

function get_one_rule($id) {
    $sql = "SELECT rule_type,rule_threshold,need_approval,email FROM " . TB_PREF . "po_quot_approval_rule WHERE id=" . db_escape($id);
    return db_fetch_assoc(db_query($sql));
}

function update_one_rule($id, $rule_type, $rule_threshold, $need_approval, $email) {
    $sql = "UPDATE " . TB_PREF . "po_quot_approval_rule SET rule_type=" . db_escape($rule_type) . ",rule_threshold=" . db_escape($rule_threshold) . ",need_approval=" . db_escape($need_approval) . ",email=" . db_escape($email) . " WHERE id=" . db_escape($id);
    db_query($sql);
    return 1;
}

function insert_one_rule($rule_type, $rule_threshold, $need_approval, $email) {
    $sql = "INSERT INTO " . TB_PREF . "po_quot_approval_rule (rule_type,rule_threshold,need_approval,email) VALUES (" . db_escape($rule_type) . "," . $rule_threshold . "," . db_escape($need_approval) . "," . db_escape($email) . ")";
    db_query($sql);
    return db_insert_id();
}

function delete_one_rule($id) {
    $sql = "DELETE FROM " . TB_PREF . "po_quot_approval_rule WHERE id=" . db_escape($id);
    db_query($sql);
}
