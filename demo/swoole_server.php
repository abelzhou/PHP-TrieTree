<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/7/31
 * Time: 16:23
 */

require_once "../src/TrieTree.php";
require_once "../vendor/autoload.php";

$http = new swoole_http_server("127.0.0.1", 8080);
$pinyin = new \Overtrue\Pinyin\Pinyin();
$replace_tree = new AbelZhou\Tree\TrieTree();
$pinyin_tree = new AbelZhou\Tree\TrieTree();
//prepared data
$name_string = file_get_contents("name.txt");
$name_array = explode("\n", $name_string);
$i = 0;
foreach ($name_array as $name) {
    if (empty($name)) {
        continue;
    }
    $name_pinyin = strtolower(implode("", $pinyin->convert($name)));
    $replace_tree->append($name, array("replace" => str_pad("", mb_strlen($name), "*")));
    $pinyin_tree->append($name_pinyin, [], true, $name);
    echo "LOAD->" . ++$i . ":" . $name . "[{$name_pinyin}]" . PHP_EOL;
}

$http->on('request', function ($request, $response) {
    global $pinyin_tree, $replace_tree;

    switch ($request->server["request_uri"]) {
        case "/pinyin":
            header('content-type:application/json;charset=utf-8');
            $keyword = strtolower($request->get["term"]);
            if (empty($keyword)) {
                $response->end(json_encode([]));
            }
            $result = array();
            $pinyin_res = $pinyin_tree->getTreeWord($keyword);
            foreach ($pinyin_res as $word) {
                $result[] = $word["word"];
            }
            $response->end(json_encode($result));
            break;
        case "/replace":
            header('content-type:application/json;charset=utf-8');
            $content = $request->post["data"];
            $keywords = $replace_tree->search($content);
            foreach ($keywords as $keyword) {
                $content = str_replace($keyword["word"], $keyword["data"]["replace"], $content);
            }
            $result["keywords"] = $keywords;
            $result["res"] = $content;
            $response->end(json_encode($result));
            break;
        default:
            $html = <<<Eof
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Hello tried tree.</title>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
        <script src="//code.jquery.com/jquery-1.9.1.js"></script>
        <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
        <link rel="stylesheet" href="http://jqueryui.com/resources/demos/style.css">
    </head>
    <body>
        <label>拼音检索:</label><input id="pinyin" value="" /><br><br><br>
        <lable>提取替换:</lable><textarea id="in" style="width: 500px;height: 300px;"></textarea><input id="commit" type="button" value="提交" /><br>
        <div id="show"></div>
        
        <script>
                $( "#pinyin" ).autocomplete({
                    source: "/pinyin",
                    delay:10
                });
                $("#commit").click(function(){
                    var txt = $("#in").val();
                    if (txt==""){
                        return;
                    }
                    $.ajax({url:"/replace",type:"POST",dataType:"JSON",data:{data:txt},
                    success:function(data){
                        console.log(data);
                        $("#show").html(data.res);
                    }});
                    
                    
                });
        </script>
    </body>
</html>
Eof;
            $response->end($html);
    }


});
$http->start();




