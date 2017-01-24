<?php

include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilTextInputGUI.php");
require_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");
require_once("./Services/Tracking/classes/class.ilLearningProgress.php");
require_once("./Services/Tracking/classes/class.ilLPStatusWrapper.php");
require_once("./Services/Tracking/classes/status/class.ilLPStatusPlugin.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Video/classes/class.ilVideoPlugin.php");
require_once("./Services/Form/classes/class.ilNumberInputGUI.php");
require_once('./Services/Form/classes/class.ilDragDropFileInputGUI.php');
require_once("./Customizing/global/plugins/Services/Cron/CronHook/MediaConverter/classes/Media/class.mcMedia.php");
require_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");

/**
 * @ilCtrl_isCalledBy ilObjVideoGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjVideoGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjVideoGUI extends ilObjectPluginGUI
{
	const LP_SESSION_ID = 'xtst_lp_session_state';
	const A_WIDTH = 178;
	const A_HEIGHT = 100;

	/** @var  ilCtrl */
	protected $ctrl;

	/** @var  ilTabsGUI */
	protected $tabs;

	/** @var  ilTemplate */
	public $tpl;

	/** @var  ilVideoPlugin */
	public $pl;

	/** @var  ilObjVideo */
	public $object;

	/**
	 * Initialisation
	 */
	protected function afterConstructor()
	{
		global $ilCtrl, $ilTabs, $tpl;
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->tpl = $tpl;
		$this->pl = new ilVideoPlugin();
	}

	public function executeCommand() {
		global $tpl;

				$next_class = $this->ctrl->getNextClass($this);
		switch ($next_class) {
			case 'ilexportgui':
				// only if plugin supports it?
				$tpl->setTitle($this->object->getTitle());
				$tpl->setTitleIcon(ilObject::_getIcon($this->object->getId()));
				$this->setLocator();
				$tpl->getStandardTemplate();
				$this->setTabs();
				include_once './Services/Export/classes/class.ilExportGUI.php';
				$this->tabs->activateTab("export");
				$exp = new ilExportGUI($this);
				$exp->addFormat('xml');
				$this->ctrl->forwardCommand($exp);
				$tpl->show();
				return;
				break;
		}

		$return_value = parent::executeCommand();

		return $return_value;
	}

	/**
	 * Get type.
	 */
	final function getType()
	{
		return ilVideoPlugin::ID;
	}

	/**
	 * Handles all commmands of this class, centralizes permission checks
	 */
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			case "editProperties":   // list all commands that need write permission here
			case "updateProperties":
			case "saveProperties":
			case "showExport":
				$this->checkPermission("write");
				$this->$cmd();
				break;

			case "showContent":   // list all commands that need read permission here
			case "setStatusToCompleted":
			case "setStatusToFailed":
			case "setStatusToInProgress":
			case "setStatusToNotAttempted":
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}

	/**
	 * After object has been created -> jump to this command
	 */
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	 * Get standard command
	 */
	function getStandardCmd()
	{
		return "showContent";
	}

//
// DISPLAY TABS
//

	/**
	 * Set tabs
	 */
	function setTabs()
	{
		global $ilCtrl, $ilAccess;

		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$this->tabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$this->tabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard permission tab
		$this->addPermissionTab();
		$this->activateTab();
	}

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	protected function editProperties()
	{
		$this->tabs->activateTab("properties");
		$form = $this->initPropertiesForm();
		$this->addValuesToForm($form);
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function initPropertiesForm() {
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->plugin->txt("obj_xvvv"));
		$form->setMultipart(true);

		$title = new ilTextInputGUI($this->plugin->txt("title"), "title");
		$title->setRequired(true);
		$form->addItem($title);

		$description = new ilTextInputGUI($this->plugin->txt("description"), "description");
		$form->addItem($description);

		$online = new ilCheckboxInputGUI($this->plugin->txt("online"), "online");
		$form->addItem($online);

		$form->setFormAction($this->ctrl->getFormAction($this, "saveProperties"));
		$form->addCommandButton("saveProperties", $this->plugin->txt("update"));

		$file_input = new ilFileInputGUI($this->pl->txt('video_file'), 'video_file');
		$file_input->setRequired(true);
		$file_input->setSuffixes(array( '3gp', 'flv', 'mp4', 'webm' ));

		if($this->object->hasVideo()) {
			$cb = new ilCheckboxInputGUI($this->pl->txt("override_file"));
			$cb->addSubItem($file_input);
			$form->addItem($cb);
		} else {
			$form->addItem($file_input);
		}

//		$num_input = new ilNumberInputGUI($this->pl->txt('form_image_at_sec'), 'image_at_sec');
//		$num_input->setInfo($this->pl->txt('form_image_at_sec_info'));
//		$form->addItem($num_input);

		return $form;
	}

	/**
	 * @param $form ilPropertyFormGUI
	 */
	protected function addValuesToForm(&$form) {
		$form->setValuesByArray(array(
			"title" => $this->object->getTitle(),
			"description" => $this->object->getDescription(),
			"online" => $this->object->isOnline(),
		));
	}

	/**
	 *
	 */
	protected function saveProperties() {
		$form = $this->initPropertiesForm();
		$form->setValuesByPost();
		if($form->checkInput()) {
			$this->fillObject($this->object, $form);
			$this->object->update();
			$this->saveVideo();
			ilUtil::sendSuccess($this->plugin->txt("update_successful"), true);
			$this->ctrl->redirect($this, "editProperties");
		}
		$this->tpl->setContent($form->getHTML());
	}

	protected function saveVideo() {
		$video_file = $_FILES['video_file'];
		if(!$video_file || !$video_file['name'])
			return true;

		$this->deleteAllFiles();

		$suffix = pathinfo($video_file['name'], PATHINFO_EXTENSION);
		if (! $this->checkSuffix($suffix)) {
			$response = new stdClass();
			$response->error = $this->pl->txt('form_wrong_filetype') . ' (' . $suffix . ')';
			throw new ilException($this->pl->txt('form_wrong_filetype') . ' (' . $suffix . ')');
		}

		move_uploaded_file($video_file['tmp_name'], $this->object->getOriginalPath($suffix));

		$mediaConverter = new mcMedia();
		$mediaConverter->uploadFile('video', $suffix, $this->object->getVideoPath(), substr($this->object->getVideoPath(), 0, -1), $this->object->getId());
	}

	protected function deleteAllFiles() {
		$this->deleteFiles($this->object->getVideoPath()."*");
		$this->deleteFiles($this->object->getIconPath()."*");
	}

	protected function deleteFiles($path) {
		$files = glob($path); // get all file names
		foreach($files as $file){ // iterate files
			if(is_file($file))
				unlink($file); // delete file
		}
	}

	public function extractImage($atSecond = 1)
	{
		try {
			mcFFmpeg::extractImage($this->object->getOriginalPath(), $this->object->getIconPath(), $this->object->getPosterTitle(), $atSecond);
		} catch (ilFFmpegException $e) {
			ilUtil::sendFailure($e->getMessage(), true);
		}
		ilUtil::resizeImage($this->object->getPosterPath(), $this->object->getThumbnailPath(), self::A_WIDTH, self::A_HEIGHT, true);
	}

	protected function showContent() {
		$this->tabs->activateTab("content");

		if(count($this->object->getSourcesToURL()) && $this->object->conversionFailed()) {
			// Conversion failed but we have a displayable format.
			ilUtil::sendInfo($this->pl->txt("conversion_failed_info"));
		} elseif(!count($this->object->getSourcesToURL() && $this->object->conversionFailed())) {
			//converstion failed and we have no displayable format.
			ilUtil::sendFailure($this->pl->txt("converstion_failed_fail"));
		}

		if($this->object->isConverting()) {
			ilUtil::sendInfo($this->pl->txt("conversion_in_progress"));
		}

		if(!count($this->object->getSourcesToURL()))
			$html = $this->pl->txt("no_video_available");
		else {

			$tpl = $this->pl->getTemplate("tpl.content.html");

			foreach ($this->object->getSourcesToURL() as $format => $path) {
				$tpl->setCurrentBlock("source");
				$tpl->setVariable("PATH", $path);
				$tpl->setVariable("FORMAT", $format);
			}

			$html = $tpl->get();
		}

		$this->tpl->setContent($html);
	}

	/**
	 * @param $object ilObjVideo
	 * @param $form ilPropertyFormGUI
	 */
	private function fillObject($object, $form) {
		$object->setTitle($form->getInput('title'));
		$object->setDescription($form->getInput('description'));
		$object->setOnline($form->getInput('online'));
	}

	protected function showExport() {
		require_once("./Services/Export/classes/class.ilExportGUI.php");
		$export = new ilExportGUI($this);
		$export->addFormat("xml");
		$ret = $this->ctrl->forwardCommand($export);

	}

	/**
	 * We need this method if we can't access the tabs otherwise...
	 */
	private function activateTab() {
		$next_class = $this->ctrl->getCmdClass();

		switch($next_class) {
			case 'ilexportgui':
				$this->tabs->activateTab("export");
				break;
		}

		return;
	}

	private function setStatusToCompleted() {
		$this->setStatusAndRedirect(ilLPStatus::LP_STATUS_COMPLETED_NUM);
	}

	private function setStatusAndRedirect($status) {
		global $ilUser;
		$_SESSION[self::LP_SESSION_ID] = $status;
		ilLPStatusWrapper::_updateStatus($this->object->getId(), $ilUser->getId());
		$this->ctrl->redirect($this, $this->getStandardCmd());
	}

	protected function setStatusToFailed() {
		$this->setStatusAndRedirect(ilLPStatus::LP_STATUS_FAILED_NUM);
	}

	protected function setStatusToInProgress() {
		$this->setStatusAndRedirect(ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
	}

	protected function setStatusToNotAttempted() {
		$this->setStatusAndRedirect(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
	}

	function checkSuffix($suffix) {
		if (in_array($suffix, array( '3pgg', '3gp', 'flv', 'mp4', 'webm' ))) {
			return true;
		}

		return false;
	}
}
?>