<?php

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");
require_once("./Services/Tracking/interfaces/interface.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Video/classes/class.ilObjVideoGUI.php");

/**
 */
class ilObjVideo extends ilObjectPlugin
{
	/**
	 * Constructor
	 *
	 * @access        public
	 * @param int $a_ref_id
	 */
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}

	/**
	 * Get type.
	 */
	final function initType()
	{
		$this->setType(ilVideoPlugin::ID);
	}

	/**
	 * Create object
	 */
	function doCreate()
	{
		global $ilDB;

		$ilDB->manipulate("INSERT INTO rep_robj_xtst_data ".
			"(id, is_online, option_one, option_two) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote(0, "integer").",".
			$ilDB->quote("default 1", "text").",".
			$ilDB->quote("default 2", "text").
			")");
	}

	/**
	 * Read data from db
	 */
	function doRead()
	{
		global $ilDB;

		$set = $ilDB->query("SELECT * FROM rep_robj_xtst_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
		);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
		}
	}

	/**
	 * Update data
	 */
	function doUpdate()
	{
		global $ilDB;

		$ilDB->manipulate($up = "UPDATE rep_robj_xtst_data SET ".
			" is_online = ".$ilDB->quote($this->isOnline(), "integer")."".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
		);
	}

	/**
	 * Delete data from db
	 */
	function doDelete()
	{
		global $ilDB;

		$ilDB->manipulate("DELETE FROM rep_robj_xtst_data WHERE ".
			" id = ".$ilDB->quote($this->getId(), "integer")
		);
	}

	/**
	 * Do Cloning
	 */
	function doClone($a_target_id,$a_copy_id,$new_obj)
	{
		global $ilDB;

		$new_obj->setOnline($this->isOnline());
		$new_obj->setOptionOne($this->getOptionOne());
		$new_obj->setOptionTwo($this->getOptionTwo());
		$new_obj->update();
	}

	/**
	 * Set online
	 *
	 * @param        boolean                online
	 */
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}

	/**
	 * Get online
	 *
	 * @return        boolean                online
	 */
	function isOnline()
	{
		return $this->online;
	}
}
?>