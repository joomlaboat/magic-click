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

function magicclick_check_revslider($tag,$Itemid,$url,$content,$src)
{
    $path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_uniterevolution2';
    if (!file_exists($path))
		return null;

    $jinput=JFactory::getApplication()->input;

    $found=array();
    $articleitem=null;

    $link_path='/administrator/index.php?option=com_uniterevolution2&page=uniterevolution&view=slide&id=';

    if($tag=='IMG')
    {
        if($src!="")
        {
            $revslideritems=magicclick_check_revslider_find_by_img($src);
            foreach($revslideritems as $revslideritem)
            {
                if(!magicclick_is_revslider_slide_exists($found,$revslideritem->id))
                {
                    $link=$link_path.$revslideritem->id;
                    $found[]=['title'=>'Unite Revolution Slider: '.$revslideritem->title.'/'.$revslideritem->found_label,'link'=>$link,'id'=>$revslideritem->id,'match'=>$revslideritem->match];
                }
            }
        }
    }
    else
    {
        $revslideritems=magicclick_check_revslider_find_by_content($content);
        foreach($revslideritems as $revslideritem)
        {
            if(!magicclick_is_revslider_slide_exists($found,$revslideritem->id))
            {
                $link=$link_path.$revslideritem->id;
                $found[]=['title'=>'Unite Revolution Slider: '.$revslideritem->title.'/'.$revslideritem->found_label,'link'=>$link,'id'=>$revslideritem->id,'match'=>$revslideritem->match];
            }
        }
    }


    if(count($found)>0)
        return $found;
    else
        return null;
}


function magicclick_is_revslider_slide_exists(&$found,$slide_id)
{
    foreach($found as $f)
    {
        if($f['id']==$slide_id)
            return true;
    }
    return false;
}


function magicclick_check_revslider_find_by_img($src)
{
    $db = JFactory::getDBO();
    $src_json=str_replace('/','\/',$src);

    $w=array();
    $w[]='INSTR('.$db->quoteName('params').','.$db->quote($src).')';
    $w[]='INSTR('.$db->quoteName('params').','.$db->quote($src_json).')';
    $w[]='INSTR('.$db->quoteName('layers').','.$db->quote($src).')';
    $w[]='INSTR('.$db->quoteName('layers').','.$db->quote($src_json).')';

    $where='('.implode(' OR ',$w).')';

    $records=magicclick_check_revslider_find($where);
    $records=magicclick_check_revslider_records_img($records,$src);
    return $records;
}

function magicclick_check_revslider_find_by_content($content)
{
    $db = JFactory::getDBO();
    $field='layers';

    if($content=='')
        return array();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString($field,$content,$new_parts);

    $records=magicclick_check_revslider_find($where);

    $records=magicclick_check_revslider_records_label($records,$new_parts);
    return $records;
}


function magicclick_check_revslider_records_img($records,$src)
{
    $new_records=array();
    foreach($records as $record)
    {
        $params=(array)@json_decode($record->params);
        if(isset($params['image']) and $params['image']==$src)
        {
            $record->found_label='Slide Image';
            $record->match=90;
            $new_records[]=$record;
        }
        else
        {
            $layers=(array)@json_decode($record->layers);
            foreach($layers as $layer_)
            {
                $layer=(array)$layer_;
                if(isset($layer['type']) and $layer['type']=='image')
                {
                    if(isset($layer['image_url']) and $layer['image_url']==$src)
                    {
                        $record->found_label='Layer Image';
                        $record->match=90;
                        $new_records[]=$record;
                    }
                }
            }
        }
    }
    return $new_records;
}

function magicclick_check_revslider_records_label($records,$find_what)
{
    $new_records=array();
    foreach($records as $record)
    {

        $layers=(array)@json_decode($record->layers);
        foreach($layers as $layer_)
        {
            $layer=(array)$layer_;

                if(isset($layer['type']) and $layer['type']=='text' and $layer['text']!='')
                {

                    $text=MagicClickMisc::cleanContentString($layer['text']);

                    $no_match=false;

                    foreach($find_what as $f)
                    {
                        if(strpos($text,$f)===false)
                            $no_match=true;
                    }

                    if(!$no_match)
                    {
                        $record->found_label='Layer Text';
                        $record->match=90;
                        $new_records[]=$record;
                    }
                }
        }

    }

    return $new_records;
}


function magicclick_check_revslider_find($where)
{
    $db = JFactory::getDBO();
    $s1='(SELECT title FROM #__revslider_sliders WHERE #__revslider_sliders.id=#__revslider_slides.slider_id LIMIT 1) AS title';
    $w='';
    if($where!='')
        $w=' WHERE '.$where;

    $query = 'SELECT *,'.$s1.' FROM #__revslider_slides'.$w;

    $db->setQuery($query);

    $recs=$db->loadObjectList();
    return $recs;
}
