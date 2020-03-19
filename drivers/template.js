var magicclick_template_params="";
function magicclick_template_movecursor()
{
    var cm=Joomla.editors.instances['jform_source'];
    var cr=cm.getCursor();
    //alert(JSON.stringify(cr));
    //alert(magicclick_template_params);
    if(magicclick_template_params!='')
    {
        var parts=magicclick_template_params.split("a");
        if(parts.length==4)
        {
            var line1=parseInt(parts[0]);
            var ch1=parseInt(parts[1]);
            //var position1={line:), ch:};
            var line2=parseInt(parts[2]);
            var ch2=parseInt(parts[3]);

            //alert(JSON.stringify( position1));
            var l=line1;
            if(l>25)
                l+=25;
            cm.setCursor({line: l, ch: ch1});

            cm.setSelection({line: line1, ch: ch1},{line: line2, ch: ch2});

        }
    }

}


function magicclick_template_do(params)
{
    magicclick_template_params=params;
    setTimeout(magicclick_template_movecursor,0.5);
}

