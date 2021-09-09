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

function magicclick_check_sppagebuilder($tag,$Itemid,$url,$content,$src)
{
    $path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_sppagebuilder';
    if (!file_exists($path))
		return null;

    $jinput=JFactory::getApplication()->input;

    $found=array();
    $articleitem=null;

    $link_path='/administrator/index.php?option=com_sppagebuilder&task=page.edit&id=';
    if($tag=='IMG')
    {
        if($src!="")
        {
            $sppageitems=magicclick_check_sppagebuilder_find_by_img($src);
            foreach($sppageitems as $sppageitem)
            {
                if(!magicclick_is_sppagebuilder_page_exists($found,$sppageitem->id))
                {
                    $link=$link_path.$sppageitem->id.'&mc_params='.$sppageitem->found_addon_id;

                    $found[]=['title'=>'SP Page Builder: '.$sppageitem->title.'/'.$sppageitem->found_label,'link'=>$link,'id'=>$sppageitem->id,'match'=>$sppageitem->match];
                }
            }
        }
    }
    else
    {

        $sppageitems=magicclick_check_sppagebuilder_find_by_content($content);
        foreach($sppageitems as $sppageitem)
        {

            if(!magicclick_is_sppagebuilder_page_exists($found,$sppageitem->id))
            {
                $link=$link_path.$sppageitem->id.'&mc_params='.$sppageitem->found_addon_id;

                $found[]=['title'=>'SP Page Builder: '.$sppageitem->title.'/'.$sppageitem->found_label,'link'=>$link,'id'=>$sppageitem->id,'match'=>$sppageitem->match];
            }
        }

    }

    if(count($found)>0)
        return $found;
    else
        return null;
}


function magicclick_is_sppagebuilder_page_exists(&$found,$page_id)
{
    foreach($found as $f)
    {
        if($f['id']==$page_id)
            return true;
    }
    return false;
}


function magicclick_check_sppagebuilder_find_by_img($src)
{
    $db = JFactory::getDBO();
    $src_json=str_replace('/','\/',$src);

    $where1='INSTR('.$db->quoteName('text').','.$db->quote($src).')';
    $where2='INSTR('.$db->quoteName('text').','.$db->quote($src_json).')';
    $where='('.$where1.' OR '.$where2.')';

    $records=magicclick_check_sppagebuilder_find($where);
    $records=magicclick_check_sppagebuilder_records_($records,$src);

    return $records;
}

function magicclick_check_sppagebuilder_records_($records,$find_what)
{

    $new_records=array();
    foreach($records as $record)
    {
        $text=(array)@json_decode($record->text);


        foreach($text as $item_)
        {
            $item=(array)$item_;

            //print_r($item);

            if(isset($item['columns']))
            {
                $columns=$item['columns'];

                foreach($columns as $column_)
                {

                    $column=(array)$column_;
                    if(isset($column['addons']))
                    {
                        $a=$column['addons'];

                        $r=magicclick_check_sppagebuilder_addons($a,$find_what);
                        if($r!=null)
                        {
                            $record->found_addon_id=$r[0];
                            $record->found_label=$r[1];
                            $record->match=$r[2];
                            $new_records[]=$record;
                        }
                    }
                }
            }
        }
    }

    return $new_records;
}

function magicclick_check_sppagebuilder_addons($addons,$find_what)
{

    foreach($addons as $addon_)
    {

        $addon=(array)$addon_;
        if(isset($addon['name']))
            $name=$addon['name'];
        else
            $name='';

        $settings=(array)$addon['settings'];

        if(isset($addon['settings']))
        {
            if($name=='feature')
            {

                $v=magicclick_check_sppagebuilder_check_addon($settings,'title',$find_what);
                if($v!=null)
                    return $v;

                $v=magicclick_check_sppagebuilder_check_addon($settings,'text',$find_what);
                if($v!=null)
                    return $v;

            }
            elseif($name=='text_block')
            {
                $v=magicclick_check_sppagebuilder_check_addon($settings,'title',$find_what);
                if($v!=null)
                    return $v;

                $v=magicclick_check_sppagebuilder_check_addon($settings,'text',$find_what);
                if($v!=null)
                    return $v;
            }
            elseif($name=='image')
            {
                $v=magicclick_check_sppagebuilder_check_addon($settings,'image',$find_what);



                if($v!=null)
                {
                    $v[1]='Image';
                    $v[2]=100;

                    //print_r($v);
                    //die;

                    return $v;
                }


            }
        }

    }
    return null;
}

function magicclick_check_sppagebuilder_check_addon($settings,$field,$find_what)
{
            if(isset($settings[$field]))
            {
                $text=MagicClickMisc::cleanContentString($settings[$field]);

                if(isset($settings['title']) and $settings['title']!='')
                    $admin_label=$settings['title'];
                elseif(isset($settings['admin_label']) and $settings['admin_label']!='')
                    $admin_label=$settings['admin_label'];
                else
                    $admin_label='Addon';

                if(is_array($find_what))
                {
                    $no_match=false;

                    foreach($find_what as $f)
                    {
                        if(strpos($text,$f)===false)
                            $no_match=true;
                    }

                    if(!$no_match)
                        return array($addon['id'],$admin_label,80);
                }
                else
                {
                    if(strpos($text,$find_what)!==false)
                        return array($addon['id'],$admin_label,80);
                }

            }


    return null;
}

function magicclick_check_sppagebuilder_find_by_content($content)
{
    $db = JFactory::getDBO();
    $field='text';

    if($content=='')
        return array();

    $new_parts=array();
    $where=MagicClickMisc::MySQLWhereString($field,$content,$new_parts);

    $records=magicclick_check_sppagebuilder_find($where);
    $records=magicclick_check_sppagebuilder_records_($records,$new_parts);
    return $records;
}


function magicclick_check_sppagebuilder_find($where)
{
    $db = JFactory::getDBO();
    $query = 'SELECT * FROM #__sppagebuilder WHERE  '.$where.' ';

    $db->setQuery($query);

    $recs=$db->loadObjectList();
    return $recs;
}
