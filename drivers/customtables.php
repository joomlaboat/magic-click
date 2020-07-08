<?php
/**
* Magic Click Joomla! Plugin
*
* @author    Ivan Komlev
* @copyright Copyright (C) 2012-2018 Ivan Komlev. All rights reserved.
* @license	 GNU/GPL
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

function magicclick_check_customtables($tag,$Itemid,$url,$content,$src)
{
	//check if custom tables installed
	$esfile=JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'importtables.php';
	if(!file_exists($esfile))
		return null;
	
    
	/*
    if($tag=='IMG')
    {
        if($src!="")
        {
            $articleitems=magicclick_check_content_find_by_img($src);
            foreach($articleitems as $articleitem)
            {
                if(!magicclick_is_article_exists($found,$articleitem->id))
                {
                    $link=$link_path.$articleitem->id;
                    $found[]=['title'=>'Article: '.$articleitem->title.'/'.$articleitem->found_label,'link'=>$link,'id'=>$articleitem->id,'match'=>90];
                }
            }

            $articleitems=magicclick_check_content_find_by_content($src,'introtext');
            foreach($articleitems as $articleitem)
            {
                if(!magicclick_is_article_exists($found,$articleitem->id))
                {
                    $link=$link_path.$articleitem->id;
                    $found[]=['title'=>'Article: '.$articleitem->title.'/Intro Text','link'=>$link,'id'=>$articleitem->id,'match'=>90];
                }
            }

            if(count($articleitems)==0)
            {
                $articleitems=magicclick_check_content_find_by_content($src,'fulltext');
                foreach($articleitems as $articleitem)
                {
                    if(!magicclick_is_article_exists($found,$articleitem->id))
                    {
                        $link=$link_path.$articleitem->id;
                        $found[]=['title'=>'Article: '.$articleitem->title.'/Full Text','link'=>$link,'id'=>$articleitem->id,'match'=>15];
                    }
                }
            }
        }
    }
    else
	*/	
	if($content!='')
    {
		$link_path='/administrator/index.php?option=com_customtables&view=listoflayouts&task=layouts.edit&id=';
		$layoutitems=magicclick_check_ct_layouts_find_by_content($content);
        foreach($layoutitems as $layoutitem)
        {
            if(!magicclick_is_ct_layout_exists($found,$layoutitem->id))
            {
                $link=$link_path.$layoutitem->id;
                $found[]=['title'=>'Custom Tables Layout: '.$layoutitem->layoutname,
				'link'=>$link,
				'id'=>$layoutitem->id,
				'match'=>90];
            }
        }
		
		//$link_path='/administrator/index.php?option=com_customtables&view=listoftables&task=tables.edit&id=';
		
		
		$fielditems=magicclick_check_ct_fields_find_by_title($content,'fieldtitle');
        foreach($fielditems as $fielditem)
        {
            if(!magicclick_is_ct_field_exists($found,$fielditem->id))
            {
				$link_path='/administrator/index.php?option=com_customtables&view=listoffields&task=fields.edit';
                $link=$link_path.'&tableid='.$fielditem->tableid.'&id='.$fielditem->id;
                $found[]=['title'=>'Custom Tables: Table "'.$fielditem->tabletitle.'", Field: "'.$fielditem->fieldtitle.'"',
				'link'=>$link,
				'id'=>$fielditem->id,
				'match'=>90];
            }
        }
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function magicclick_is_ct_layout_exists(&$found,$layout_id)
{
    foreach($found as $f)
    {
        if($f['id']==$layout_id)
            return true;
    }
    return false;
}

function magicclick_is_ct_field_exists(&$found,$field_id)
{
    foreach($found as $f)
    {
        if($f['id']==$field_id)
            return true;
    }
    return false;
}

/*
function magicclick_check_content_find_by_img($src)
{
    $db = JFactory::getDBO();
    $src_json=str_replace('/','\/',$src);

    $w1='INSTR(images,'.$db->quote($src).')';
    $w2='INSTR(images,'.$db->quote($src_json).')';
    $where='('.$w1.' OR '.$w2.')';

    $records=magicclick_check_content_find($where);

    $new_records=array();
    foreach($records as $record)
    {
        $images=(array)json_decode($record->images);


        if(isset($images['image_intro']) and $images['image_intro']==$src)
        {
            $record->found_label='Intro Image';
            $new_records[]=$record;
        }
        elseif(isset($images['image_fulltext']) and $images['image_fulltext']==$src)
        {
            $record->found_label='Full Article Image';
            $new_records[]=$record;
        }
    }

    return $new_records;
}
*/

function magicclick_check_ct_layouts_find_by_content($content)
{
    $db = JFactory::getDBO();

    if($content=='')
        return array();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString('layoutcode',$content,$new_parts);

    if($where=='')
        return array();

    $db = JFactory::getDBO();
    $query = 'SELECT * FROM #__customtables_layouts WHERE  '.$where;

    $db->setQuery($query);
    if (!$db->query())    die( $db->stderr());

    return $db->loadObjectList();
}

function magicclick_check_ct_fields_find_by_title($content,$field)
{
    $db = JFactory::getDBO();

    if($content=='')
        return array();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString($field,$content,$new_parts);

    if($where=='')
        return array();

    $db = JFactory::getDBO();
    $query = 'SELECT id,tableid,fieldtitle,(SELECT tabletitle FROM #__customtables_tables AS t WHERE t.id=f.tableid LIMIT 1) AS tabletitle FROM #__customtables_fields AS f WHERE  '.$where;

    $db->setQuery($query);
    if (!$db->query())    die( $db->stderr());

    return $db->loadObjectList();
}
