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

function magicclick_check_menu($tag,$Itemid,$url,$content,$src)
{
    $jinput=JFactory::getApplication()->input;

    $href=$jinput->getString('mc_href');
	$href=str_replace(' ','+',$href);
	$href=base64_decode($href);

    $found=array();
    $menuitem=null;

    if($tag=='IMG')
    {
        if($src!="")
            $menuitem=magicclick_check_menu_find_by_img($src);

    }
    elseif($tag=='A')
    {

        $Itemid=(int)MagicClickMisc::getURLQueryOption($href,'Itemid');

        if($Itemid!=0)
            $menuitem=magicclick_check_menu_find_by_Itemid($Itemid);
        else
        {
            $parts=explode('?',$href);
            $otherparts=explode('/',$parts[0]);
            $alias=end($otherparts);
            $menuitem=magicclick_check_menu_find_by_alias($alias,$content);
        }
    }
    else
    {
        //$found=magicclick_check_template_check_template_files($template_path,$template->extension_id,$content);

    }


    if($menuitem==null)
    {
        $menuitem=magicclick_check_menu_find_by_title($content);

        if($menuitem!=null)
        {
            $link='/administrator/index.php?option=com_menus&view=item&client_id=0&layout=edit&id='.$menuitem->id;
            $found[]=['title'=>'Menu Item: '.$menuitem->title,'link'=>$link,'match'=>90];
        }

    }
    else
    {
            $link='/administrator/index.php?option=com_menus&view=item&client_id=0&layout=edit&id='.$menuitem->id;
            $found[]=['title'=>'Menu Item: '.$menuitem->title,'link'=>$link,'match'=>100];
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function magicclick_check_menu_find_by_Itemid($Itemid)
{
    $db = JFactory::getDBO();
    $where='id='.(int)$Itemid;
    return magicclick_check_menu_find($where);
}

function magicclick_check_menu_find_by_img($src)
{
    $db = JFactory::getDBO();
    $where='img='.$db->quote($src);
    return magicclick_check_menu_find($where);
}

function magicclick_check_menu_find_by_alias($alias,$content)
{
    $db = JFactory::getDBO();

    if($alias=='index.php' or $alias=='')
    {
        $content=MagicClickMisc::cleanContentString($content);
        if($content=='')
            return null;

        $where='home=1 AND title='.$db->quote(strip_tags($content));
    }
    else
        $where='alias='.$db->quote($alias);

    return magicclick_check_menu_find($where);
}

function magicclick_check_menu_find_by_title($content)
{
    $db = JFactory::getDBO();

    $content=MagicClickMisc::cleanContentString($content);
    if($content=='')
        return null;

    $where='title='.$db->quote(strip_tags($content));

    return magicclick_check_menu_find($where);
}


function magicclick_check_menu_find($where)
{

    $db = JFactory::getDBO();
    $query = 'SELECT * FROM #__menu WHERE published=1 AND '.$where.' LIMIT 1';

    $db->setQuery($query);

    $recs=$db->loadObjectList();
    if(count($recs)==0)
        return null;

    return $recs[0];
}
