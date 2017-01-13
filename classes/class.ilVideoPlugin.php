<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 */
class ilVideoPlugin extends ilRepositoryObjectPlugin
{
	const ID = "xvvv";

	// must correspond to the plugin subdirectory
	function getPluginName()
	{
		return "Video";
	}

	protected function uninstallCustom() {
		// TODO: Nothing to do here.
	}
}
?>