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
			"(id, is_online) VALUES (".
			$ilDB->quote($this->getId(), "integer").",".
			$ilDB->quote(0, "integer") . ")");

		$this->recursiveMkdir($this->getPath());
		$this->recursiveMkdir($this->getVideoPath());
		$this->recursiveMkdir($this->getIconPath());
	}

	/**
	 * @param $path
	 *
	 * @return bool
	 */
	protected function recursiveMkdir($path) {
		$dirs = explode(DIRECTORY_SEPARATOR, $path);
		$count = count($dirs);
		$path = '';
		for ($i = 0; $i < $count; ++ $i) {
			if ($path != '/') {
				$path .= DIRECTORY_SEPARATOR . $dirs[$i];
			} else {
				$path .= $dirs[$i];
			}
			if (! is_dir($path)) {
				ilUtil::makeDir(($path));
			}
		}

		return true;
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

	/**
	 * @return string f.e.: '/var/www/ilias_44/data/client_id/vidm/1/2/5/video_6'
	 */
	public function getPath() {
		return ILIAS_ABSOLUTE_PATH . '/' . ILIAS_WEB_DIR . '/' . CLIENT_ID . '/xvvv/' . $this->id . '/';
	}

	public function getPosterPath() {
		return $this->getPath() . 'icons/' . $this->getPosterTitle();
	}

	public function getThumbnailPath() {
		return $this->getPath() . 'icons/' . $this->getThumbnailTitle();
	}

	public function getIconPath() {
		return $this->getPath() . 'icons/';
	}

	public function getWebmPath() {
		return $this->getPath() . 'videos/video.webm';
	}

	public function getMP4Path() {
		return $this->getPath() . 'videos/video.mp4';
	}

	public function getOriginalPath($suffix) {
		return $this->getPath() . 'videos/' . $this->getOriginalTitle($suffix);
	}

	public function getOriginalTitle($suffix) {
		return 'video.' . $suffix;
	}

	public function getVideoPath() {
		return $this->getPath() . 'videos/';
	}

	public function getThumbnailTitle() {
		return 'thumbnail.png';
	}
	public function getPosterTitle() {
		return 'poster.png';
	}

	public function getHttpPath() {
		return ilUtil::_getHttpPath() . '/data/' . CLIENT_ID . '/xvvv/' . $this->id . '/';
	}

	/**
	 * ("webm" => url, "mp4" => url), wenn alles da. sonst evtl leer!
	 */
	public function getSourcesToURL() {
		$sources = [];

		if(is_readable($this->getWebmPath()))
			$sources['webm'] = $this->getHttpPath() . 'videos/' . $this->getOriginalTitle('webm');

		if(is_readable($this->getMP4Path()))
			$sources['mp4'] = $this->getHttpPath() . 'videos/' . $this->getOriginalTitle('mp4');

		return $sources;
	}

	public function hasVideo() {
		$scanned_directory = array_diff(scandir($this->getVideoPath()), array('..', '.'));
		return count($scanned_directory) > 0;
	}

	public function isConverting() {
		return 0 < mcMedia::where(array("trigger_obj_id" => $this->id, "status_convert" => array(mcMedia::STATUS_WAITING, mcMedia::STATUS_RUNNING)))->count();
	}

	public function conversionFailed() {
		return 0 < mcMedia::where(array("trigger_obj_id" => $this->id, "status_convert" => mcMedia::STATUS_FAILED))->count();
	}
}
?>