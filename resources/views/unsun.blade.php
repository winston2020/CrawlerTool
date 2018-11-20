<html>
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>


<div id="#ts"></div>
<script>
    var data = {!! $data !!}
    var count = data.length;

        newdata = [];
        for (var i = 0; i < count; i++) {
            setTimeout((function (i) {
                return function () {
                    href = data[i].href
                    host = data[i].host
                    url = unsuan(data[i].mark, href, host)
                    id = data[i].id
                    var item = new Object();
                    item.id= id
                    cuDomainNo = getUrlPar("d", href);
                    if (cuDomainNo == "") cuDomainNo = "0";
                    var sCuDomian = getDomain(parseInt(cuDomainNo), host)
                    item.comic_img_url = sCuDomian + url;
                    newdata[i] = item
                    return newdata
                }
            })(i), 0);
        }


    function insert(s) {
        $.ajax({
            type:'post',
            url:'/api/hh/savedata',
            data:{data:s},
            dataType:'json',
            success:function(data){
                alert(newdata)
            }
        });
    }







    function unsuan(s,href,host)
    {
        x = s.substring(s.length-1);
        w="abcdefghijklmnopqrstuvwxyz";
        xi=w.indexOf(x)+1;
        sk = s.substring(s.length-xi-12,s.length-xi-1);
        s=s.substring(0,s.length-xi-12);
        k=sk.substring(0,sk.length-1);
        f=sk.substring(sk.length-1);
        for(i=0;i<k.length;i++) {
            eval("s=s.replace(/"+ k.substring(i,i+1) +"/g,'"+ i +"')");
        }
        ss = s.split(f);
        s="";
        for(i=0;i<ss.length;i++) {
            s+=String.fromCharCode(ss[i]);
        }
        return s;
    }

    function getUrlPar(name,href)
    {
        var reg = new RegExp("(^|\\?|&)"+ name +"=([^&]*)(\\s|&|$)", "i");
        source = href;
        if (reg.test(source)) return RegExp.$2; return "";
    }

    function getDomain(s,host)
    {
        arrDS = host.split("|");
        if(arrDS.length==1) return arrDS[0];
        return arrDS[s];
    }

</script>
</html>