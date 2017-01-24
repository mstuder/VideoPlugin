<#1>
<?php
$fields = array(
	'id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	)
);

if(!$ilDB->tableExists('rep_robj_xvvv_data')) {
    $ilDB->createTable("rep_robj_xvvv_data", $fields);
    $ilDB->addPrimaryKey("rep_robj_xvvv_data", array("id"));
}
?>