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

function magicclick_check_content($tag,$Itemid,$url,$content,$src)
{
    $jinput=JFactory::getApplication()->input;

    $found=array();
    $articleitem=null;

    $link_path='/administrator/index.php?option=com_content&task=article.edit&id=';
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
    elseif($content!='')
    {

       $articleitems=magicclick_check_content_find_by_title($content);
        foreach($articleitems as $articleitem)
        {
            if(!magicclick_is_article_exists($found,$articleitem->id))
            {
                $link=$link_path.$articleitem->id;
                $found[]=['title'=>'Article: '.$articleitem->title,'link'=>$link,'id'=>$articleitem->id,'match'=>90];
            }
        }


        $articleitems=magicclick_check_content_find_by_content($content,'introtext');
        foreach($articleitems as $articleitem)
        {
            if(!magicclick_is_article_exists($found,$articleitem->id))
            {
                $link=$link_path.$articleitem->id;
                $found[]=['title'=>'Article: '.$articleitem->title,'link'=>$link,'id'=>$articleitem->id,'match'=>20];
            }
        }

        if(count($articleitems)==0)
        {
            $articleitems=magicclick_check_content_find_by_content($content,'fulltext');
            foreach($articleitems as $articleitem)
            {
                if(!magicclick_is_article_exists($found,$articleitem->id))
                {
                    $link=$link_path.$articleitem->id;
                    $found[]=['title'=>'Article (Full Text): '.$articleitem->title,'link'=>$link,'id'=>$articleitem->id,'match'=>15];
                }
            }
        }
    }

    //minimize the list if we are on article page
    if(count($found)>1)
    {
        $article_id=magicclick_get_current_menu_item_article_id();
        if($article_id!=null)
        {
            $new_found=array();
            foreach($found as $f)
            {
                if($f['id']==$article_id)
                {
                    $f['match']=100;
                    $new_found[]=$f;
                }
            }


        }

        if(count($new_found)>0)
            $found=$new_found;
    }

    if(count($found)>0)
        return $found;
    else
        return null;
}

function magicclick_is_article_exists(&$found,$article_id)
{
    foreach($found as $f)
    {
        if($f['id']==$article_id)
            return true;
    }
    return false;
}

function magicclick_get_current_menu_item_article_id()
{
    $jinput=JFactory::getApplication()->input;
    $Itemid=$jinput->getInt('Itemid');

    $db = JFactory::getDBO();
    $where='id='.(int)$Itemid.' AND INSTR(link,"index.php?option=com_content&view=article&")';
    $query = 'SELECT id,link FROM #__menu WHERE published=1 AND '.$where.' LIMIT 1';

    $db->setQuery($query);
    if (!$db->query())    die( $db->stderr());

    $recs=$db->loadObjectList();
    if(count($recs)==0)
        return null;

    $parts=explode('&id=',$recs[0]->link);
    if(count($parts)!=2)
        return null;

    $article_id=$parts[1];

    return $article_id;
}

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

function magicclick_check_content_find_by_title($content)
{
    $db = JFactory::getDBO();
    $content=MagicClickMisc::cleanContentString($content);
    if($content=='')
        return array();

    $where='TRIM(title)='.$db->quote($content);

    return magicclick_check_content_find($where);
}

function magicclick_check_content_find_by_content($content,$field)
{
    $db = JFactory::getDBO();

    if($content=='')
        return array();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString($field,$content,$new_parts);

    if($where=='')
        return array();

    return magicclick_check_content_find($where);
}

function magicclick_check_content_find($where)
{
    if($where=='')
        return array();

    $db = JFactory::getDBO();
    $query = 'SELECT * FROM #__content WHERE  '.$where;

    $db->setQuery($query);
    if (!$db->query())    die( $db->stderr());

    $recs=$db->loadObjectList();

    return $recs;
}
