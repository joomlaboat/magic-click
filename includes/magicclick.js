/**
* Magic Click Joomla! Plugin
*
* @author    Ivan Komlev
* @copyright Copyright (C) 2012-2018 Ivan Komlev. All rights reserved.
* @license	 GNU/GPL
*/

var MagicClick_Translations_description="Front-end Content Assistant";
var MagicClick_Translations_find="Find:";
var MagicClick_Translations_foundlocations="Found locations:";
var MagicClick_Translations_notfound="Not Found";
var MagicClick_Translations_searching="Searching";

var MagicClick_theKey="";

var MagicClickObjects=[];
var MagicClickObjects_Index=-1;

var MagicClickObjectInProcess=false;
var MagicClickObjectInFound=false;

function MagicClickObjectDisableProcess()
{
   MagicClickObjects_Index=-1;
   MagicClickObjects=[];

   MagicClickObjectInProcess=false;
}

function MagicClickGetSelectedText() {
    var text = "";
    if (typeof window.getSelection != "undefined") {
        text = window.getSelection().toString();
    } else if (typeof document.selection != "undefined" && document.selection.type == "Text") {
        text = document.selection.createRange().text;
    }
    return text;
}

function processMagicClick()
{

   if(MagicClickObjects.length==0)
   {
      MagicClickObjectDisableProcess();
      return;
   }

   MagicClickObjects_Index=MagicClickObjectsFindTag("IMG");
   if(MagicClickObjects_Index==-1)
      MagicClickObjects_Index=MagicClickObjectsFindTag("A");

   if(MagicClickObjects_Index==-1)
      MagicClickObjects_Index=0;

   var MagicClickObject=MagicClickObjects[MagicClickObjects_Index];
   var TagName=MagicClickObject.tagName;

   if(TagName=='DIV' && MagicClickObject.getAttribute("src")!=null)
        process_MG_Image(MagicClickObject);
   else if(TagName=='IMG')
        process_MG_Image(MagicClickObject);
   else if(TagName=='A')
        process_MG_Anchor(MagicClickObject);
   else
   {
        process_MG_Paragraph(TagName,MagicClickObject);
   }

}

function MagicClickObjectsFindTag(tag)
{
   for(var i=0;i<MagicClickObjects.length;i++)
   {
      var tagName=MagicClickObjects[i].tagName;

      if(tagName==tag)
         return i;
   }
   return -1;
}

function process_MG_Image(obj)
{
    var data=[
        "mc_tag=IMG",
        "mc_Itemid="+MagicClick_Itemid,
        "mc_src="+obj.getAttribute("src"),
        "mc_url="+Base64.encode(location.href)
    ];

    make_MG_request(data,obj.getAttribute("src"),"IMG");
}

function process_MG_Anchor(obj)
{
    var data=[
        "mc_tag=A",
        "mc_Itemid="+MagicClick_Itemid,
        "mc_href="+Base64.encode(obj.getAttribute("href")),
        "mc_content="+Base64.encode(obj.innerHTML),
        "mc_url="+Base64.encode(location.href)
    ];

    make_MG_request(data,obj.innerHTML,"A");
}

function process_MG_Paragraph(tag,obj)
{
   var find=obj.innerHTML;
   var clean_find=MagicClick_strip(find).trim();
   if(find.length>1024 || clean_find=="")
   {
      MagicClickObjects.splice(MagicClickObjects_Index, 1);

      if(MagicClickObjects.length==0)
      {
         var form_content='<div class="magicclick_error">'+MagicClick_Translations_notfound+'</div>';
         var obj2=document.getElementById("magicclick_suggestions");
         obj2.innerHTML=form_content;

         MagicClickObjectDisableProcess();
         return false;
      }

      processMagicClick();
      return false;
   }

    var data=[
        "mc_tag="+tag,
        "mc_Itemid="+MagicClick_Itemid,
        "mc_content="+Base64.encode(find),
        "mc_url="+Base64.encode(location.href),
        "mc_src="+obj.getAttribute("src"),
    ];

    make_MG_request(data,obj.innerHTML,tag);
}

function magicclick_showModal_form(find,tag)
{
   var form_content='<div class="magicclick_suggestion">';
   form_content+='<h3>Magic Click - <span> '+MagicClick_Translations_description+'</span></h3>';

   form_content+=MagicClick_Translations_find;

    if(tag=="IMG")
        form_content+='<p><img src="'+find+'" class="magicclick_suggestion_image" /><br/>'+find+'</p>';
    else
        form_content+='<p>'+MagicClick_strip(find)+'</p>';


   form_content+='<div id="magicclick_suggestions"><div class="magicclick_searching">'+MagicClick_Translations_searching+'</div></div>';

   form_content+='</div>';

       var obj=document.getElementById("magicclick_modal_content_box");
    obj.innerHTML=form_content;

    magicclick_showModal();
}

function magicclick_showSuggestions(find,suggestions,tag)
{
   if(MagicClickObjects_Index==-1)
   {
      MagicClickObjectDisableProcess();
      return false;
   }

   var form_content='<div class="magicclick_error">'+MagicClick_Translations_notfound+'</div>';


   if(suggestions.length==0)
   {
      //delete element
      MagicClickObjects.splice(MagicClickObjects_Index, 1);

      if(MagicClickObjects.length==0)
      {

         form_content+='<div class="magicclick_error">Not found</div>';
         MagicClickObjectDisableProcess();
      }

      processMagicClick();
   }


   if(suggestions.length>0)
   {
      form_content='';

      form_content+=MagicClick_Translations_foundlocations;
      form_content+='<ol>';

      for(var i=0;i<suggestions.length;i++)
      {
        var s=suggestions[i];
        form_content+='<li>';
        if(s.link!="")
         form_content+='<a href="'+s.link+'" target="_blank">'+s.title+'</a>';
         else
         form_content+=s.title;

        //form_content+='<span class="magicclick_match">- '+s.match+'%</span>';
        form_content+='</li>';

      }
      form_content+='</ol>';
   }

    var obj=document.getElementById("magicclick_suggestions");
    obj.innerHTML=form_content;

    if(suggestions.length>0)
      MagicClickObjectDisableProcess();
}


function MagicClick_strip(html)
{
   var tmp = document.createElement("DIV");
   tmp.innerHTML = html;
   return tmp.textContent || tmp.innerText || "";
}

function make_MG_request(data,find,tag)
{
   magicclick_showModal_form(find,tag);

    data.push('magicclick_task=find');
    var url=MagicClick_PrepareLink(['magicclick_task'],data);
    var other_params={
        //headers:{"content-type":"application/json; charset=utf-8"},
        //body:data,
        mothod: "GET",
        //mode: "no-cors",
        //credentials: "same-origin"
    };

    fetch(url,other_params).then(function(response)
	{

			if(response.ok)
			{
				response.json().then(function(json_response)
				{
                    if(json_response.status=='ok')
                    {

                        magicclick_showSuggestions(find,json_response.suggestions,tag);

                    }

				});
			}
			else
			{
            alert("Error: "+response.Body+"\n"+url);
			}
	}).catch(function(err)
	{
			console.log('Fetch Error :', err);
	});
}

function isSpecialKey(event)
{
   var clicked=false;
   if(MagicClick_theKey=="shiftalt" && event.shiftKey && event.altKey)
      clicked=true;
   else if(MagicClick_theKey=="shiftctrl" && event.ctrlKey && event.shiftKey)
      clicked=true;
   else if(MagicClick_theKey=="ctrlalt" && event.ctrlKey && event.altKey)
      clicked=true;

      return clicked;
}

function doMagicClickOnAnchor(event)
{
   //This function prevents further popagation, nothing more.
   var clicked=isSpecialKey(event);

    if (clicked)
    {
        event.preventDefault();
        //event.stopPropagation();
        return false;
    }

}

function doMagicClick4SelectedText(event)
{
	var clicked=isSpecialKey(event);

    if (clicked)
    {
		var selectedText=MagicClickGetSelectedText();
		if(selectedText!='')
		{
			if(!MagicClickObjectInProcess)
			{
				MagicClickObjects.push(this);
				MagicClickObjects_Index=0;
			
				MagicClickObjectInProcess=true;
			
				var data=[
				"mc_tag=DIV",
				"mc_Itemid="+MagicClick_Itemid,
				"mc_content="+Base64.encode(selectedText),
				"mc_url="+Base64.encode(location.href),
				];

				let timerId = setTimeout(function() {
					make_MG_request(data,selectedText,'DIV');
				
				}, 500);
			}

			event.preventDefault();
		}
		
	}
}

function doMagicClick(event)
{
   var clicked=isSpecialKey(event);

    if (clicked)
    {
		
         if(!MagicClickObjectInProcess)
         {
			MagicClickObjects.push(this);
			 
            setTimeout(processMagicClick,500);
            MagicClickObjectInProcess=true;
         }

        event.preventDefault();
        //event.stopPropagation(); to let other events to thrugh
        return false;
    }
}

function mc_addClickEvents()
{
    var elements=['img','a','p','td','th','li','h1','h2','h3','h4','h5','h6','div','dt','dd','legend'];

    for(var e=0;e<elements.length;e++)
    {
        var matches = document.querySelectorAll(elements[e]);

        for (var i = 0; i < matches.length; i++)
        {
  //       var tagName=matches[i].tagName;

//         var src=matches[i].getAttribute("src");
            matches[i].addEventListener("click", doMagicClick, false);
			matches[i].addEventListener("mousedown", doMagicClick4SelectedText, false);
        }
    }


    var matches2 = document.querySelectorAll('a');
    for (var i2 = 0; i2 < matches2.length; i2++)
    {
        matches2[i2].addEventListener("click", doMagicClickOnAnchor, false);
    }

}

    function MagicClick_PrepareLink(deleteParams,addParams)
    {
        var link=window.location.href;

        var pair=link.split('#');
        link=pair[0];

        for(var i=0;i<deleteParams.length;i++)
        {
            link=MC_removeURLParameter(link, deleteParams[i]);
        }

        for(var a=0;a<addParams.length;a++)
        {

            if(link.indexOf("?")==-1)
                link+="?"; else link+="&";

            link+=addParams[a];
        }

        return link;
    }

function magicclick_showModal()
{


            // Get the modal
            var modal = document.getElementById('magicclick_Modal');

            // Get the <span> element that closes the modal
            var span = document.getElementsByClassName("magicclick_close")[0];

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = "none";
            };

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            };

            var box=document.getElementById("magicclick_modalbox");



            modal.style.display = "block";

            var d = document;
            e = d.documentElement;

            var doc_w=e.clientWidth;
            var doc_h=e.clientHeight;

            var w=box.offsetWidth;
            var h=box.offsetHeight;

            var x= (doc_w/2)-w/2;
            if(x<10)
                x=10;

            if(x+w+10>doc_w)
                x=doc_w-w-10;

            var y=(doc_h/2)-h/2;


            if(y<50)
                y=50;


            if(y+h+50>doc_h)
            {
                y=doc_h-h-50;
            }

            box.style.left=x+'px';
            box.style.top=y+'px';
}

    function MC_removeURLParameter(url, parameter)
    {
        //prefer to use l.search if you have a location/link object
        var urlparts= url.split('?');
        if (urlparts.length>=2)
        {

            var prefix= encodeURIComponent(parameter)+'=';
            var pars= urlparts[1].split(/[&;]/g);

            //reverse iteration as may be destructive
            for (var i= pars.length; i-- > 0;) {
                //idiom for string.startsWith
                if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                    pars.splice(i, 1);
                }
            }

            url= urlparts[0] + (pars.length > 0 ? '?' + pars.join('&') : "");
            return url;
        } else {
            return url;
        }
    }


document.addEventListener("DOMContentLoaded",function(){mc_addClickEvents();})