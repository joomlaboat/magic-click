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

//function magicclick_findPos

function magicclick_check_helix3_template($tag,$Itemid,$url,$content,$src)
{
    $jinput=JFactory::getApplication()->input;

    // Joomla! Site Application Object

    $app = JFactory::getApplication();
    // Menu class of type JMenuSite
    $menus = $app->getMenu();
    // Get Active Menu Item which is a type of stdClass Object
    $activeMenu = $menus->getActive();


    $template_style_id=$activeMenu->template_style_id;
    $template=magicclick_check_template_get_template($template_style_id);

    if(!$template)
        return null;

    if(!isset($template->template))
        return null;


    $template_path=$path=JPATH_SITE.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$template->template;
    $relative_template_path='templates'.DIRECTORY_SEPARATOR.$template->template;
    $params=(array)@json_decode($template->params);

    $found=array();
    if($tag=='IMG')
    {
        if($src!="")
            $found=helix3_checkIfImageIsLogo($template,$relative_template_path,$params,$src);
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function helix3_checkIfImageIsLogo(&$template,$template_path,$params,$src)
{
    $found=array();

    if(!isset($params['preset']))
        return $found;

    $preset=$params['preset'];

    $logo_path=$template_path.'/images/presets/'.$preset.'/logo.png';

    if($logo_path==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Helix 3 Template Default Logo: '.$src,'link'=>$link,'match'=>100];
    }
    elseif(isset($params['logo_image']) and $params['logo_image']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Helix 3 Template Style Logo: '.$src,'link'=>$link,'match'=>100];
    }
    elseif(isset($params['logo_image_2x']) and $params['logo_image_2x']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Helix 3 Template Style Retina Logo: '.$src,'link'=>$link,'match'=>100];
    }
    elseif(isset($params['mobile_logo']) and $params['mobile_logo']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Helix 3 Template Style Mobile Logo: '.$src,'link'=>$link,'match'=>100];
    }

    return $found;
}




function magicclick_check_helix3_template_get_template($style_id)
{
    $db = JFactory::getDBO();
    if($style_id!=0)
        $where='id='.$style_id;
    else
        $where='client_id=0 AND home=1';

    $ext='(SELECT extension_id FROM #__extensions WHERE #__extensions.type="template" AND #__extensions.name=#__template_styles.template LIMIT 1) AS extension_id';
    $query = 'SELECT *,'.$ext.' FROM #__template_styles WHERE '.$where.' LIMIT 1';

	$db->setQuery($query);

    $recs=$db->loadObjectList();
    if(count($recs)==0)
        return null;

    return $recs[0];
}
