<?php
/**
* Magic Click Joomla! Plugin
*
* @version	1.0.7
* @author    Ivan Komlev
* @copyright Copyright (C) 2012-2018 Ivan Komlev. All rights reserved.
* @license	 GNU/GPL
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


jimport('joomla.plugin.plugin');

class plgSystemMagicClick extends JPlugin
{

	public function onBeforeRender()
	{
		$app = JFactory::getApplication();

		if($app->isSite())
		{
			$availabletoadmin=(int)$this->params->get('availabletoadmin');

			$doit=false;
			if($availabletoadmin==1)
			{
				$user = JFactory::getUser();
				$isroot = $user->authorise('core.admin');

				if($isroot)
					$doit=true;
			}
			else
				$doit=true;

			if($doit)
			{
				$path=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'magicclick'.DIRECTORY_SEPARATOR.'includes';
				require_once($path.DIRECTORY_SEPARATOR.'misc.php');

				$jinput=JFactory::getApplication()->input;
				$task=$jinput->getCmd('magicclick_task');



				if($task!='')
				{
					MagicClickMisc::process_api_task($task,['drivers','prodrivers']);
				}
				else
				{
					$app = JFactory::getApplication();
					if($app->isSite())
					{
						$hotkeys=$this->params->get('hotkeys');
						if($hotkeys=='')
							$hotkeys="ctrlalt";

						MagicClickMisc::setCSSStyleHeader($hotkeys);
					}
				}
			}
		}


		if($app->isAdmin())
		{

			$output = JResponse::getBody();

			$jinput=JFactory::getApplication()->input;
			if($jinput->getCmd('option')=='com_templates')
			{
				$path=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'magicclick'.DIRECTORY_SEPARATOR.'includes';
				require_once($path.DIRECTORY_SEPARATOR.'misc.php');

				$path=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'magicclick'.DIRECTORY_SEPARATOR.'drivers';
				require_once($path.DIRECTORY_SEPARATOR.'template.php');
				magicclick_check_template_backend();
			}

			JResponse::setBody($output);

		}
	}


	public function onAfterRender()
	{
		$app = JFactory::getApplication();

		if($app->isSite())
		{
			$path=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'magicclick'.DIRECTORY_SEPARATOR.'includes';
			require_once($path.DIRECTORY_SEPARATOR.'misc.php');

			$doit=false;
			$availabletoadmin=(int)$this->params->get('availabletoadmin');

			if($availabletoadmin==1)
			{
				$user = JFactory::getUser();
				$isroot = $user->authorise('core.admin');

				if($isroot)
					$doit=true;
			}
			else
				$doit=true;

			if($doit)
			{
				$hotkeys=$this->params->get('hotkeys');
				if($hotkeys=='')
					$hotkeys="ctrlalt";

				$output = JResponse::getBody();
				$output=MagicClickMisc::addDivs($output,$availabletoadmin,$hotkeys);
				JResponse::setBody($output);
			}
		}
	}
}
