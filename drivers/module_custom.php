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

function magicclick_check_module_custom($tag,$Itemid,$url,$content,$src)
{
    $jinput=JFactory::getApplication()->input;

    $found=array();

    $link_path='/administrator/index.php?option=com_modules&task=module.edit&id=';
    if($tag=='IMG')
    {
        if($src!="")
        {
                /*
            $moduleitems=magicclick_check_module_custom_find_by_img($src);
            foreach($moduleitems as $moduleitem)
            {
                if(!magicclick_is_module_exists($found,$moduleitem->id))
                {
                    $link=$link_path.$moduleitem->id;
                    $found[]=['title'=>'Module "Custom": '.$moduleitem->title,'link'=>$link,'id'=>$moduleitem->id,'match'=>90];
                }
            }*/

            $moduleitems=magicclick_check_module_custom_find_by_content($src,'content');
            foreach($moduleitems as $moduleitem)
            {
                if(!magicclick_is_module_exists($found,$moduleitem->id))
                {
                    $link=$link_path.$moduleitem->id;
                    $found[]=['title'=>'Module "Custom": '.$moduleitem->title,'link'=>$link,'id'=>$moduleitem->id,'match'=>90];
                }
            }
        }
    }
    else
    {


       $moduleitems=magicclick_check_module_custom_find_by_title($content);
        foreach($moduleitems as $moduleitem)
        {
            if(!magicclick_is_module_exists($found,$moduleitem->id))
            {
                $link=$link_path.$moduleitem->id;
                $found[]=['title'=>'Module "Custom": '.$moduleitem->title,'link'=>$link,'id'=>$moduleitem->id,'match'=>90];
            }
        }

        $moduleitems=magicclick_check_module_custom_find_by_content($content,'content');
        foreach($moduleitems as $moduleitem)
        {
            if(!magicclick_is_module_exists($found,$moduleitem->id))
            {
                $link=$link_path.$moduleitem->id;
                $found[]=['title'=>'Module "Custom": '.$moduleitem->title,'link'=>$link,'id'=>$moduleitem->id,'match'=>20];
            }
        }
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function magicclick_is_module_exists(&$found,$article_id)
{
    foreach($found as $f)
    {
        if($f['id']==$article_id)
            return true;
    }
    return false;
}

function magicclick_check_module_custom_find_by_img($src)
{
    $db = JFactory::getDBO();
    $where='INSTR(params,'.$db->quote($src).')';
    return magicclick_module_custom_find($where);
}

function magicclick_check_module_custom_find_by_title($content)
{
    $db = JFactory::getDBO();

    $content=strip_tags($content);
    $content=MagicClickMisc::cleanContentString($content);

    if($content=='')
        return array();

    $where='TRIM(title)='.$db->quote($content);

    return magicclick_module_custom_find($where);
}

function magicclick_check_module_custom_find_by_content($content,$field)
{
    $db = JFactory::getDBO();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString($field,$content,$new_parts);

    if($where=='')
        return array();

    return magicclick_module_custom_find($where);
}


function magicclick_module_custom_find($where)
{

    $db = JFactory::getDBO();
    $query = 'SELECT * FROM #__modules WHERE  '.$where.' ';

    $db->setQuery($query);

    $recs=$db->loadObjectList();

    return $recs;
}
