<?php

include_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Video/classes/class.ilObjVideo.php");
require_once("./Services/AccessControl/interfaces/interface.ilConditionHandling.php");

/**
 * Please do not create instances of large application classes
 * Write small methods within this class to determine the status.
 *
 * @author                 Alex Killing <alex.killing@gmx.de>
 * @author					Oskar Truffer <ot@studer-raimann.ch>
 * @version $Id$
 */
class ilObjVideoAccess extends ilObjectPluginAccess
{

	/**
	 * Checks whether a user may invoke a command or not
	 * (this method is called by ilAccessHandler::checkAccess)
	 *
	 * Please do not check any preconditions handled by
	 * ilConditionHandler here. Also don't do usual RBAC checks.
	 *
	 * @param       string $a_cmd command (not permission!)
	 * @param       string $a_permission permission
	 * @param       int $a_ref_id reference id
	 * @param       int $a_obj_id object id
	 * @param 		int$a_user_id user id (default is current user)
	 * @return bool true, if everything is ok
	 */
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = 0)
	{
		global $ilUser, $ilAccess;

		if ($a_user_id == 0)
		{
			$a_user_id = $ilUser->getId();
		}

		switch ($a_permission)
		{
			case "read":
				if (!ilObjVideoAccess::checkOnline($a_obj_id) &&
					!$ilAccess->checkAccessOfUser($a_user_id, "write", "", $a_ref_id))
				{
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 * @param $a_id int
	 * @return bool
	 */
	static function checkOnline($a_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT is_online FROM rep_robj_xvvv_data ".
			" WHERE id = ".$ilDB->quote($a_id, "integer")
		);
		$rec  = $ilDB->fetchAssoc($set);
		return (boolean) $rec["is_online"];
	}
}

?>