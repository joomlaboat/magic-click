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

function magicclick_check_template_backend()
{
    $jinput=JFactory::getApplication()->input;
	if($jinput->getCmd('view')=='template')
	{
   		$document = JFactory::getDocument();
		$document->addScript(JURI::root(true).'/plugins/system/magicclick/drivers/template.js');

        $params=$jinput->getCmd('mc_params');

        $js='
		<script>
            document.addEventListener("DOMContentLoaded",function(){magicclick_template_do("'.$params.'");})
		</script>
		';

		$document->addCustomTag($js);
    }
}

//function magicclick_findPos

function magicclick_check_template($tag,$Itemid,$url,$content,$src)
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

    if($tag=='IMG')
    {
        if($src!="")
        {
            $found=checkIfImageIsLogo($template,$src);

            $found_new=magicclick_check_template_check_template_files($template_path,$template->extension_id,$src);


            if(count($found_new)>0)
                $found=array_merge($found,$found_new);
        }

    }
    else
    {
        $found=magicclick_check_template_check_template_files($template_path,$template->extension_id,$content);
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function checkIfImageIsLogo(&$template,$src)
{
    $found=array();

    $params=(array)json_decode($template->params);

    if(isset($params['logo_file']) and $params['logo_file']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Template Style Logo: '.$src,'link'=>$link,'match'=>100];
    }
    elseif(isset($params['logoimage']) and $params['logoimage']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Template Style Logo: '.$src,'link'=>$link,'match'=>100];
    }
    elseif(isset($params['footer_logoimage']) and $params['footer_logoimage']==$src)
    {
        $link='/administrator/index.php?option=com_templates&task=style.edit&id='.$template->id;
        $found[]=['title'=>'Template Style Footer Logo: '.$src,'link'=>$link,'match'=>100];
    }

    return $found;
}

function magicclick_check_template_check_template_files($template_path,$extension_id,$lookforstring)
{
    if($lookforstring=='')
        return null;

    $found=array();

    $files = scandir($template_path);


	foreach($files as $file)
	{
		if($file!='.' and $file!='..')
		{
            $filename=$template_path.DIRECTORY_SEPARATOR.$file;
            if(is_dir($filename))
            {
            }
            elseif(file_exists($filename))
            {
                    $content=file_get_contents($filename);

                    $pos1=strpos($content,$lookforstring);

                    if($pos1!==false)
                    {


                        $pos2=$pos1+strlen($lookforstring);

                        $params=implode('a',magicclick_findposition_line_ch($content,$pos1,$pos2));
                        $link='/administrator/index.php?option=com_templates&view=template&id='.$extension_id.'&file='.base64_encode($file).'&mc_params='.$params;

                        $found[]=['title'=>'Template File: '.$file,'link'=>$link,'match'=>70];//,'params'=>$params];
                    }
            }
		}
	}

    return $found;
}
function magicclick_findposition_line_ch($content,$pos1,$pos2)
{
    $l1=0;
    $c1=0;
    $l2=0;
    $c2=0;
    $lines = preg_split( '/\r\n|\r|\n/', $content);
    $p1=magicclick_findposition_line_ch_($lines,$pos1);
    $p2=magicclick_findposition_line_ch_($lines,$pos2);

    return [$p1[0],$p1[1],$p2[0],$p2[1]];
}

function magicclick_findposition_line_ch_(&$lines,$pos)
{
    $pos_s=0;
    $line_count=0;
    foreach($lines as $line)
    {
        $l=strlen($line);
        $pos_e=$pos_s+$l;
        if($pos>=$pos_s and $pos<=$pos_e)
        {
            $ch=$pos-$pos_s;
            return [$line_count,$ch];
        }
        $line_count++;
        $pos_s=$pos_e+1;
    }

    return [-1,-1];

}

function magicclick_check_template_get_template($style_id)
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
