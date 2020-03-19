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

class MagicClickMisc
{
    public static function csv_explode($delim=',', $str, $enclose='"', $preserve=false)
	{
		$resArr = array();
		$n = 0;
		$expEncArr = explode($enclose, $str);
		foreach($expEncArr as $EncItem)
		{
			if($n++%2){
				array_push($resArr, array_pop($resArr) . ($preserve?$enclose:'') . $EncItem.($preserve?$enclose:''));
			}else{
				$expDelArr = explode($delim, $EncItem);
				array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
			    $resArr = array_merge($resArr, $expDelArr);
			}
		}
	return $resArr;
	}

    public static function MySQLInstrString($field,$str)
    {
        $db = JFactory::getDBO();
        $w='INSTR(replace(replace(replace(replace('.$db->quoteName($field).',UNHEX("C2A0")," "),"\n",""),"\r",""),"&nbsp;"," "),'.$db->quote($str).')';
        return $w;
    }


    public static function MySQLWhereString($field,$content,&$new_parts)
    {
        $parts=MagicClickMisc::explodeByTags($content);
        $new_parts=array();

        if(count($parts)==0)
            return '';

        $wheres1=array();

        foreach($parts as $part_original_)
        {
            $part_original=str_replace('"','',$part_original_);
            $part_original=str_replace("'",'',$part_original);

            $decoded=html_entity_decode($part_original);
            $new_parts[]=$decoded;

            $part_special=htmlentities($decoded);
            $part_unicode=json_encode($decoded);

            $w=array();
            $w[]=MagicClickMisc::MySQLInstrString($field,$decoded);
            $w[]=MagicClickMisc::MySQLInstrString($field,$part_special);
            $w[]=MagicClickMisc::MySQLInstrString($field,$part_unicode);

            $wheres1[]='('.implode(' OR ',$w).')';
        }

        $where='('.implode(' AND ',$wheres1).')';
        return $where;
    }

    public static function getSRC($src='')
    {
        //this function retrievs mc_src - "image path" query parameter value
        // then it cleans it to be used in database search
        if($src=='')
        {
            $jinput=JFactory::getApplication()->input;
            $src=$jinput->getString('mc_src');
        }

        $src=str_replace('"','',$src);//just to make it more secure
        $src=str_replace("'",'',$src);//just to make it more secure

        $uri = JURI::root(false);
        $src=str_replace($uri,'',$src); //here we delete website domain name etc. to hav erelative path only

        if(strlen($src)>1)
        {
            if($src[0]=='/')
                $src=substr($src,1);//here we delete leading slash, because the image link storen without it usually
        }

        return $src;
    }

    public static function cleanContentString($content,$clean_quotes=true)
    {
        //this function deletes unnessesary characters, same will happen in MySQL query
        if($clean_quotes)
        {
            $content=str_replace('"','',$content);
            $content=str_replace("'",'',$content);
        }

        $content=str_replace("&nbsp;",' ',$content);
        $content=str_replace("\n",'',$content);
        $content=str_replace("\r",'',$content);
        $content=str_replace("\t",'',$content);
        $content=trim($content);

        return html_entity_decode($content);
    }

    public static function getContent()
    {
        //this function retrievs mc_content query parameter value
        // then it cleans it to be used in database search
        $jinput=JFactory::getApplication()->input;

        $content=$jinput->getString('mc_content');
		$content=str_replace(' ','+',$content);
		$content=base64_decode($content);

        return MagicClickMisc::cleanContentString($content,false);
    }

    public static function getURLQueryOption($urlstr, $opt)
	{

		$params = array();

		$query=explode('&',$urlstr);

		$newquery=array();

		for($q=0;$q<count($query);$q++)
		{
			$p=strpos($query[$q],$opt.'=');
			if($p!==false)
			{
				$parts=explode('=',$query[$q]);
				if(count($parts)>1)
					return $parts[1];
				else
					return '';
			}
		}
		return '';
	}

	public static function deleteURLQueryOption($urlstr, $opt)
	{

		$params = array();

		$query=explode('&',$urlstr);

		$newquery=array();

		for($q=0;$q<count($query);$q++)
		{
			$p=strpos($query[$q],$opt.'=');
			if($p===false or ($p!=0 and $p===false))
				$newquery[]=$query[$q];
		}
		return implode('&',$newquery);
	}


    public static function explodeByTags($content)
    {
        $parts=explode('<',$content);
        $new_parts=array();
        foreach($parts as $part)
        {
            $pos=strpos($part,'>');
            if($pos!==false)
            {
                $new_part=substr($part,$pos+1);
                if($new_part!='')
                    $new_parts[]=$new_part;
            }
            else
            {
                if($part!='')
                    $new_parts[]=$part;
            }
        }
        return $new_parts;
    }


    protected static function getDrivers_($dir)
	{
		$path=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'magicclick'.DIRECTORY_SEPARATOR.$dir;

		$drivers=array();

		if (!file_exists($path))
			return $drivers;

		if($dir=='drivers')
			$drivers[]=['menu.php','magicclick_check_menu',$path.DIRECTORY_SEPARATOR.'menu.php'];

		$driverfiles = scandir($path);

		foreach($driverfiles as $driverfile)
		{
			if($driverfile!='.' and $driverfile!='..')
			{
				$found=false;
				foreach($drivers as $driver)
				{
					if($driver[0]==$driverfile)
					{
						$found=true;
						break;
					}
				}

				$filename=$path.DIRECTORY_SEPARATOR.$driverfile;


				if(!$found and strpos($filename,'.php')!==false and file_exists($filename))
				{
					$functionname='magicclick_check_'.str_replace('.php','',$driverfile);
					$drivers[]=[$driverfile,$functionname,$filename];
				}
			}
		}
		return $drivers;
	}


    public static function setCSSStyleHeader($hotkeys)
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet(JURI::root(true).'/plugins/system/magicclick/includes/magicclick.css');
		$document->addScript(JURI::root(true).'/plugins/system/magicclick/includes/magicclick.js');
		$document->addScript(JURI::root(true).'/plugins/system/magicclick/includes/base64.js');

		$jinput=JFactory::getApplication()->input;
		$Itemid=$jinput->getInt('Itemid');



		$lang = JFactory::getLanguage();
		$lang->load('plg_system_magicclick', JPATH_ADMINISTRATOR);

		$js='
		<script>
			var MagicClick_Itemid='.$Itemid.';
			MagicClick_Translations_description="'.MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_PLUGIN_DESCRIPTION' ).'";
			MagicClick_Translations_find="'.MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_FIND' ).'";
			MagicClick_Translations_foundlocations="'.MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_FOUNDLOCATIONS' ).'";
			MagicClick_Translations_notfound="'.MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_NOT_FOUND' ).'";
			MagicClick_Translations_searching="'.MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_SEARCHING' ).'";
			MagicClick_theKey="'.$hotkeys.'";
			MagicClick_links=true;
		</script>
		';
		$document->addCustomTag($js);

	}

    public static function addDivs($content,$availabletoadmin,$hotkeys)
	{
		$lang = JFactory::getLanguage();
		$lang->load('plg_system_magicclick', JPATH_ADMINISTRATOR);

		$htmlresult='<div id="magicclick_Modal" class="magicclick_modal">
  <!-- Modal content -->
  <div class="magicclick_modal-content" id="magicclick_modalbox">
    <span class="magicclick_close">&times;</span>
	<div id="magicclick_modal_content_box">
    <p>Some text in the Modal..</p>
	</div>
  </div>

</div>';

if($hotkeys=='shiftalt')
	$activationkey=MagicClickMisc::JTextExtended('PLG_MAGICCLICK_HOTKEYS_SHIFT_ALT');
elseif($hotkeys=='shiftctrl')
	$activationkey=MagicClickMisc::JTextExtended('PLG_MAGICCLICK_HOTKEYS_SHIFT_CTRL');
else
	$activationkey=MagicClickMisc::JTextExtended('PLG_MAGICCLICK_HOTKEYS_CTRL_ALT');

		if($availabletoadmin)
			$msg=MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_FRONTEND_SUPER_ALERT' );
		else
			$msg=MagicClickMisc::JTextExtended( 'PLG_SYSTEM_MAGICCLICK_FRONTEND_ALERT' );

		$htmlresult.='<div class="magicclick_frontend_alert"><div>'.sprintf($msg,$activationkey).'</div></div>';

		$content=str_ireplace('</body>',$htmlresult.'</body>',$content);



		return $content;
	}

	public static function JTextExtended($text)
    {
        $new_text=JText::_($text);
        if($new_text==$text)
        {
            $parts=explode('_',$text);
            if(count($parts)>1)
            {
                $type=$parts[0];
                if($type=='PLG' and count($parts)>2)
                {
                    $extension=strtolower($parts[0].'_'.$parts[1].'_'.$parts[2]);
                }
                else
                    $extension=strtolower($parts[0].'_'.$parts[1]);

                $lang = JFactory::getLanguage();
                $lang->load($extension,JPATH_BASE);

                return JText::_($text);
			}
            else
                return $text;
        }
        else
            return $new_text;

    }



    protected static function getTagOptionValue($tag,$option)
    {
        $tag_new=trim($tag);
        if(strlen($tag_new)<8)
            return '';

        if($tag_new[0]=='<')
            $tag_new=substr($tag_new,1);

        if($tag_new[strlen($tag_new)-1]=='>')
            $tag_new=substr($tag_new,0,strlen($tag_new)-1);

        if($tag_new[strlen($tag_new)-1]=='/')
            $tag_new=substr($tag_new,0,strlen($tag_new)-1);

        $options=MagicClickMisc::csv_explode(' ', $tag_new,'"', true);

        $i=0;
        foreach($options as $option_)
        {
            if($i>0)
            {
                $option_parts=MagicClickMisc::csv_explode('=', $option_,'"', false);

                if(strtolower(trim($option_parts[0]))==strtolower($option))
                {
                    if(isset($option_parts[1]))
                        return trim($option_parts[1]);
                }
            }
            $i++;
        }
        return '';
    }

    public static function process_api_task($task,$driver_folders)
	{

		if($task=='find')
		{
			$jinput=JFactory::getApplication()->input;
			$tag=$jinput->getCmd('mc_tag');
			$Itemid=$jinput->getInt('mc_Itemid');

			$url=$jinput->getString('mc_url');
			$url=str_replace(' ','+',$url);
			$url=base64_decode($url);

			$content=MagicClickMisc::getContent();
			$src=MagicClickMisc::getSRC();

           //check if content is actual an image

            if(trim(strip_tags($content))=='' and $src=='')
            {

                preg_match_all('/<img[^>]+>/i',$content.$content, $image_tags);
                if(count($image_tags)>0)
                {
                    $tags=$image_tags[0];

                    if(count($tags)>0)
                    {
                        $src=MagicClickMisc::getSRC(MagicClickMisc::getTagOptionValue($tags[0],'src'));
                        if($src!='')
                            $tag='IMG';
                    }
                }
            }

			MagicClickMisc::process_drivers($driver_folders,$tag,$Itemid,$url,$content,$src);
		}

	}



    protected static function getDrivers($driver_folders)
	{
        $drivers=array();
        foreach($driver_folders as $folder)
        {
            $drivers_=MagicClickMisc::getDrivers_($folder);
            $drivers=array_merge($drivers,$drivers_);
        }

        return $drivers;
	}

    protected static function process_drivers($driver_folders,$tag,$Itemid,$url,$content,$src)
	{
		$drivers=MagicClickMisc::getDrivers($driver_folders);

		$results=array();
		foreach($drivers as $driver)
		{
			require_once($driver[2]);

			$result=call_user_func($driver[1],$tag,$Itemid,$url,$content,$src);

			if($result!=null and count($result)>0)
			{
				foreach($result as $r)
                {
                    if($driver[0]=='template.php' and count($driver_folders)==1)
                        $r['link']='';

					$results[]=$r;
                }
			}
		}

		//clean results
		$new_results=array();

		foreach($results as $result)
		{
			if($result['match']==100)
			{
				$new_results=array();

				$new_results[]=$result;
				break;
			}

			$new_results[]=$result;
		}

		if (ob_get_contents())
        ob_end_clean();

        header('Content-Disposition: attachment; filename="magicclick_suggestioins.json"');
        header('Content-Type: application/json; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");

		$content=['status'=>'ok','suggestions'=>$new_results];
        echo json_encode($content);
        die;
	}

}
