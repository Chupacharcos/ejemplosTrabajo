<?php
class herramientas_html
{


    public function split_css_html($html)
    {
        $result = array(
            "html" => $html,
            "css" => "",
            "html_processed" => "",
            "text" => ""
        );

// encontramos etiqueta STYLE
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $encuentros);

// concatenamos todos las estiquetas style encontradas
        foreach ($encuentros[0] as $encuentro) {
            $result["css"] .= $encuentro;
//$result["html_processed"] = str_replace($encuentro,'',$result["html_processed"]);
        }
//var_dump($result["html"]);
        $result["html_processed"] = preg_replace('/<style[^>]*>(.*?)<\/style>/is', '', $result["html"]);
// ELIMINAR ETIQUETAS STYLE EN EL CSS
        $result["css"] = preg_replace('/<style([^>]*?)>/si', '', $result["css"]);
        $result["css"] = preg_replace('/<\/style([^>]*?)>/si', '', $result["css"]);
//var_dump($result);
        return $result;
    }

    public function removeComments($html)
    {
        return $html = preg_replace('/<!--(.*?)-->/si', '', $html);
    }

    public function removeCenterTag($html)
    {
//var_dump($html);
        preg_match_all('~<center.*?>(.*?)</center>~si', $html, $center);
//var_dump($center);
        for ($i = 0; $i < sizeof($center[0]); $i++) {
            $html = str_replace($center[0][$i], $center[1][$i], $html);
        }

        return $html;
    }

    function mantener_simbolos($html)
    {
        $html = str_replace("%5D", "]", $html);
        $html = str_replace("%5B", "[", $html);

        $html = str_replace("%7B", "{", $html);
        $html = str_replace("%7D", "}", $html);
        return $html;
    }

    public function change_padding_table($html)
    {
        $pattern = '~<table.*(style=".*(padding:.*;){1,}.*".*).?>~';

        $doc = new DOMDocument();
//$internalErrors = libxml_use_internal_errors(true);
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
//libxml_use_internal_errors($internalErrors);
        $xpath = new DomXPath($doc);
        $xpath->registerNamespace("php", "http://php.net/xpath");
        $xpath->registerPHPFunctions('preg_match_all');
        $nodes = $xpath->query('//table[php:functionString("preg_match_all", "~padding~U", @style)]');


        foreach ($nodes as $node) {

            //BUSCAMOS EL STYLE DEL NODO PARA ENCONTRAR EL PADDING
            //NO ENTRA PORQUE LOS PADDING-XXX NO LOS ENCUENTRA
            //var_dump(strpos($node->getAttribute('style'),"padding"));
            if (strpos($node->getAttribute('style'), "padding")) {
                //var_dump(strpos($node->getAttribute('style'),"padding"));
                $padding = substr($node->getAttribute('style'), strpos($node->getAttribute('style'), "padding"));
                //var_dump($node->getAttribute('style'));


                //VAMOS A BUSCAR A SU PRIMER HIJO TD Y VER SI TIENE STYLE
                // $node->getNodePath() devuelve la ruta al elemento
                // Con la ruta, podemos añadirle que busque a su primer elemento hijo que aparezca
                //var_dump($node->getNodePath());
                $td = $xpath->query($node->getNodePath() . '//td[1]');
                //PUEDO COGER TODO EL STYLE QUE TUVIESE Y AÑADIRLE EL NUEVO
                //var_dump($td->item(0)->setAttribute("style",'padding: 20px;'));
                //var_dump($td->item(0)->getAttribute("style"));
                //$node->firstChild->firstChild->getAttribute('style');
//var_dump($node);

                if ($td->item(0) != null && !strpos($td->item(0)->getAttribute('style'), "padding")) {
                    //SI YA TIENE PADDING EL TD, ELIMINAMOS EL PADDING DEL TABLE
//$node->removeAttribute("style");
                    //preg_match_all('/(padding(-[a-z]*)*:\s[0-9]{1,3}(px)*;)+?/i',$node->getAttribute('style'),$matches);
                    preg_match_all('/(padding(-[a-z]*)*:(\s[0-9]{1,3}(px)*)*;)+?/i', $node->getAttribute('style'), $matches);

                    //var_dump($matches);
                    $new_padding = "";
                    foreach ($matches[0] as $match) {

                        $new_padding .= $match;
                    }
                    //echo $new_padding;
                    $td->item(0)->setAttribute("style", $td->item(0)->getAttribute("style") . $new_padding);
                }
                //BORRAR LOS PADDING DE LA TABLE
                //$node->setAttribute("style",preg_replace('/(padding(-[a-z]*)*:\s[0-9]{1,3}(px)*;)+?/i','',$node->getAttribute('style'))) ;
                $node->setAttribute("style", preg_replace('/(padding(-[a-z]*)*:(\s[0-9]{1,3}(px)*)*;)+?/i', '', $node->getAttribute('style')));
                //var_dump(preg_replace('/(padding(-[a-z]*)*:\s[0-9]{1,3}(px)*;)+?/i','',$node->getAttribute('style')));

            }

        }
        //var_dump($doc->saveHTML());
        return urldecode($doc->saveHTML());
//var_dump($html);
    }

    public function change_height_td($html)
    {
        /* CREAR PATTERN PARA LOCALIZAR HEIGHT EN TD */
        $pattern = '~<td.{0,}[^-]((height)((=")|:)\s{0,}([0-9]{1,2})("|(px;))).*>+?~U';
        //$pattern = '~<td.+(([^-]height)((=")|:)\s{0,}([0-9]{1,2})("|(px;))).*>+?~U';
        preg_match_all($pattern, $html, $heights);
        $i = 0;

        foreach ($heights[0] as $td) {

            /* SI TIENE PADDING, LE DEJAMOS EL PADDING Y BORRAMOS EL HEIGHT */
            if (strpos($td, 'padding') != 0) {
                $heights[0][$i] = str_replace($heights[1][$i], '', $td);
                $html = str_replace($td, $heights[0][$i], $html);
            } else {
                if (strrpos($td, 'style') != 0) {
                    $array = explode('style="', $td);
                    /* POSIBIBLEMNTE SE VAYA UN NIVEL ARRIBA */

                    $padding = ceil(intval($heights[5][$i]) / 4);
                    $new_td = $array[0] . 'style="padding:' . $padding . 'px;' . $array[1];
                    $new_td = str_replace($heights[1][$i], '', $new_td);

                    $html = str_replace($td, $new_td, $html);


                    /* PREG_MATCH DEL STYLE EN EL TD,
                     * EXTRAEMOS STYLE
                     * METEMOS EL NUEVO PADDING
                     * CAMBIAMOS STYLE NUEVO POR VIEJO EN EL TD
                     * CAMBIAMOS EL TD EN EL HTML
                     */
                } else {
                    /*
                     * SUSTITUIR EL HEIGHT QUE TUVIESE POR EL STYLE CON EL NUEVO PADDING
                     */
                    $padding = ceil(intval($heights[5][$i]) / 2);
                    $style = 'style="padding:' . $padding . 'px;"';
                    $new_td = str_replace($heights[1][$i], $style, $td);

                    $html = str_replace($td, $new_td, $html);
                }
            }
            $i++;
        }

        preg_match_all($pattern, $html, $td_restantes);
        $t = 0;
        if (!empty($td_restantes)) {
            foreach ($td_restantes[0] as $td) {
                $td_restantes[0][$t] = str_replace($td_restantes[1][$t], '', $td);
                $html = str_replace($td, $td_restantes[0][$t], $html);
                $t++;
            }
        }
        return $html;
    }

    public function removeElementsOcultos($html)
    {
//SEPARA LAS TABLAS
//MISMO PROBLEMA QUE AL COGER DIVS, PARA DE BUSCAR EN LA PRIMERA TABLA QUE SE CIERRA
//HACER UN WHILE PARA QUE ENCUENTRE LA TABLA MAS ADENTRO
//echo DOMDocument::loadHTML($html);
        //$pattern = "/(<)(?!.*\1).*display:\s{0,}none.*>/";

        $doc = new DOMDocument();
        //$internalErrors = libxml_use_internal_errors(true);
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        //libxml_use_internal_errors($internalErrors);
        $xpath = new DomXPath($doc);
        $xpath->registerNamespace("php", "http://php.net/xpath");
        $xpath->registerPHPFunctions('preg_match');
        $nodes_display = $xpath->query("//*[php:functionString('preg_match', '~\bdisplay\s*:\s*none\b~i', @style) = 1]");


        foreach ($nodes_display as $node) {

            if ($node->tagName != "img") {
                $pattern = preg_quote("~" . $node->C14N() . "~");

                $html = preg_replace($pattern, '', $html);

                //$node->parentNode->removeChild($node);
            }
        }

        $nodes_visibility = $xpath->query("//*[php:functionString('preg_match', '~[^-](\bvisibility\s*:\s*hidden\b)~i', @style) = 1]");
        foreach ($nodes_visibility[1] as $node) {

            if ($node->tagName != "img") {
                $pattern = preg_quote("~" . $node->C14N() . "~");
                $html = preg_replace($pattern, '', $html);

                //$node->parentNode->removeChild($node);
            }
        }


        //DEJA SOLO LOS HIJOS QUE DEBE BORRAR
        //$html1 = trim($tmp_dom->saveHTML());

        return $html;

    }

    public function removeClasses($html)
    {
//SI LA class="" ESTA VACIA, NO LA BORRABA
        $html = preg_replace("/class=\n*\s*[\"'][\"']/si", '', $html);
        $html = preg_replace("/class=\n*\s*[\"'][^\[\"'\]]*?[\"']/si", '', $html);
//$html = preg_replace("/class.?=.?[\"'](?::[^\[\"'\]]*?)[\"']/si",'',$html);
        return $html;
    }

    public function removeId($html)
    {
        $html = preg_replace("/id=\n*\s*[\"'][\"']/si", '', $html);
        //ELIMINA variable id de los href si esta vacia
        $html = preg_replace("/id=\n*\s*[\"']([a-zA-Z0-9]*?)[\"']/si", '', $html);


        return $html;
    }

    public function removeRol($html)
    {
        $html = preg_replace("/role=\n*\s*[\"'][\"']/si", '', $html);
        $html = preg_replace("/role=\n*\s*[\"']([^\[\"'\]]*?)[\"']/si", '', $html);
        return $html;
    }

    public function textoIzquierda($html)
    {
//ANTIGUA EXPRESION, QUE QUITABA LOS ESPACIOS A LA IZDA CUANDO HABIA UNA ETIQUETA DE ESTILO COMO <b> o <strong>
//$html = preg_replace("/[ ]+[<]/si", '<', $html);
        $html = preg_replace("/[ ]{2,}[<]/si", '<', $html);
//$html = preg_replace("/\s\s+/si",' ',$html);
        return $html;
    }

    public function translateAccents($html)
    {
        $trans = get_html_translation_table(HTML_ENTITIES);
        unset($trans['<']);
        unset($trans['>']);
        unset($trans['&']);
        unset($trans['"']);
        unset($trans["'"]);
        return strtr($html, $trans);
    }

    public function removeGenericTags($html)
    {
// We delete those tags as we are going to replace them the right way at the end of the process
// Notice: we do not delete <body> (in order to keep body inline style) and <meta> (to keep content type)
        $html = preg_replace('/<!DOCTYPE([^>]*?)>/si', '', $html);
        $html = preg_replace('/<htm([^>]*?)>/si', '', $html);
        $html = preg_replace('/<\/htm([^>]*?)>/si', '', $html);
        //$html = preg_replace('/<\/bod([^>]*?)>/si', '', $html);
        $html = preg_replace('/<hea([^>]*?)>/si', '', $html);
        $html = preg_replace('/<\/hea([^>]*?)>/si', '', $html);
// meta
        $html = preg_replace('/<.?meta[^>]*\/?>/is', '', $html);
        $html = preg_replace('/<.?link[^>]*\/?>/is', '', $html);
        return str_replace("\\\"", "\"", trim($html, "0x20"));
    }

    public function alignFirstTable($html)
    {
        preg_match_all("/<table(.*)/xm", $html, $firstTable);

        $i = 0;
        $done = false;
        foreach ($firstTable[1] as $table) {

            if ($done) {
                break;
            }
            if (!strpos($table, 'width="100%"')) {
                if (!strpos($table, 'align="center"')) {
                    /*
                     * SI NO ES LA TABLA DEL WIDTH=100%, Y ADEMAS NO TIENE YA ALIGN CENTER
                     *  INTRODUCIREMOS UN ALIGN CENTER
                     */
                    $firstTable[1][$i] = '<table align="center" ' . $firstTable[1][$i];
                    $html = str_replace($firstTable[0][$i], $firstTable[1][$i], $html);
                    $done = true;
                }
            }
            $i++;
        }
        return $html;
    }

    public function addcellpadding_cellspadding_tables($html)
    {
        $html = preg_replace('/cellspacing.?=.?\".?0.?\"/si', '', $html);
        $html = preg_replace('/cellpadding.?=.?\".?0.?\"/si', '', $html);
        $html = preg_replace('/<.?table\s/si', '<table cellspacing="0" cellpadding="0" ', $html);
        return $html;
    }

    public function htmlFormat($html)
    {

        $config = array(
            'indent' => true,
            //'output-xhtml' => true,

            'doctype' => 'loose',
            //CUIDADO CON ESTAS DE ABAJO
            'output-html' => true,
            'drop-empty-elements' => true,
            'drop-empty-paras' => true,
            //'enclose-block-text' => true, METE LOS ELEMENTOS QUE NO ESTAN EN TAGS EN <p>
            'fix-uri' => true,
            'literal-attributes' => true,
            'logical-emphasis' => true,
            'merge-divs' => true,
            'merge-spans' => true,
            'quote-ampersand' => false,
            'repeated-attributes' => 'keep-first',
            'break-before-br' => true,
            'clean' => true,
            //wrap CAMBIADO A 0 PARA QUE NO METIESE LOS SALTOS DE LINEA
            'wrap' => 200);
// Tidy
        $tidy = new tidy();
        $tidy->parseString($html, $config, 'UTF8');
        $tidy->repairString($html, $config, 'UTF8');
        //$tidy->cleanRepair();
//$tidy->repairString($html, $config);

        $tidy = str_replace("&amp;", "&", $tidy);
        //$tidy = str_replace("&amp;", "&", $tidy);
        return $tidy;
    }

    /* BUSCAR LA ULTIMA OCURRENCIA
     *
     * (<tr>)(?!.*\1).*{{unsubs_link}}.*</tr>
     *
     * ELIMINAR ETIQUETAS VACIAS
     * <([^<\/>]*)([^<\/>]*)>([\s]*?|(?R))<\/\1>
     */
    public function localizar_divs($html)
    {
        //var_dump($html);
        $doc = new DOMDocument();
        //$internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        $xpath = new DomXPath($doc);
        //BUSCAMOS LOS DIVS QUE TENGAN TEXT
        $nodes_divs = $xpath->query("//div(not[normalize-space(text())])");
        $nodes_divs = $xpath->query("//div[not([normalize-space(text())])]");
//ENCONTRAR LOS QUE NO TENGAN TEXTO PARA LUEGO BUSCARLOS Y BORRARLOS
        foreach ($nodes_divs as $div) {
            //ESTOS SON SOLO LOS DIVS QUE SERVIRIAN

//var_dump($div->C14N());
            $pattern = preg_quote("~" . $div->C14N() . "~");
            preg_match($pattern, $html, $match);
            //var_dump($match);
        }

        return;
    }

    public function cambiar_div_por_table($html)
    {

//PATTERN QUE RECOGE LO QUE HAY ENTRE <DIV> Y </DIV>
//SI HAY UN DIV DENTRO DE OTRO, SOLO COGE EL PRIMER CIERRE

        $pattern_div = '/<div\s{0,}(style=\".*?\"*?)*?>(.*?)<\/div>/is';


        $div_exp_reg = '/<div\s{0,}.*(style?=\".*?\"*?)*?>/iU';

// ENCONTRAR LO SIGUIENTE AL <DIV> PARA COMPROBAR SI SE DEBE CREAR TABLE O NO
        /* $div_exp_reg = '/(<div\s{0,}(style?=\".*?\"*?)*?>)/i'; */
//$not_match = '?!div';
        preg_match_all($div_exp_reg, $html, $coincidencias);


//SI $coincidencias[3] TIENE DIV, SE BORRA TODO ESE DIV JUNTO A SU FINAL DE \/DIV
        //var_dump($coincidencias);
        for ($i = 0; $i < sizeof($coincidencias[0]); $i++) {


            $tabla_inicio = '<table align="center" cellspacing="0" cellpadding="0" ' . $coincidencias[1][$i] . '><tr><td>';
            $html = str_replace($coincidencias[0][$i], $tabla_inicio, $html);
            $html = str_replace('</div>', '</table></td></tr>', $html);
            if (empty($coincidencias[$i][$i])) {
//SI NO TIENE STYLE
//COMPROBAR SI  TIENE TEXTO
                $html = str_replace($coincidencias[0][$i], '<table align="center" cellspacing="0" cellpadding="0" ' . $coincidencias[1][$i] . ' ><tr><td', $html);
                $html = str_replace('</div>', '</table></td></tr>', $html);
            } else {

//CREAR TABLA CON EL STYLE DEL DIV
//preg_match_all($style_exp_reg, $coincidencias[$i][$i],$style);
//var_dump($style[0]);
//var_dump($coincidencias[$i][$i]);
                $tabla_inicio = '<table align="center" cellspacing="0" cellpadding="0" ' . $coincidencias[1][$i] . '><tr><td';
                $html = str_replace($coincidencias[0][$i], $tabla_inicio, $html);
                $html = str_replace('</div>', '</table></td></tr>', $html);
            }
        }
        return $html;
    }

    function callHrefSrc($m)
    {

        return $this->cutImage($m[0], true);
    }

    function callSrc($m)
    {

        return $this->cutImage($m[0], false);
    }


    function correctImages($html)
    {

        $html = preg_replace_callback('/<a([^>]*?)>([^<]*?)<img([^>]*?)>([^<]*?)<\/a>/is', array(get_class($this), 'callHrefSrc'), $html);

        $html = preg_replace_callback('/<img([^>]*?)>/is', array(get_class($this), 'callSrc'), $html);
        
        return $html;
    }


    function getImgSrc($html)
    {
        preg_match("/src.?=.*?'([^']*?)'/is", $html, $src);
        if (empty($src[1]))
            preg_match('/src.?=.*?"([^"]*?)"/is', $html, $src);
        return $src[1];
    }

    function getAHref($html)
    {
        $html = tidyHTML($html);
        
        preg_match("/href.?=.?'([^']+?)'/is", $html, $href);

        if (empty($href[1])) {
            preg_match('/href.?=.?"([^"]+?)"/is', $html, $href);
        }
        
        if (empty($href[1])) {
            preg_match('/"(http.*?)"/is', $html, $href);
        }
        
        return $href[1];
    }

    function getImgWidth($html)
    {

        preg_match("/width.?=.?'([^']*?)'/is", $html, $width);
        if (empty($width[1])) {
            preg_match('/width.?=.?"([^"]*?)"/is', $html, $width);

        }

        return @$width[1];
    }

    function getImgHeight($html)
    {
        preg_match("/height.?=.?'([^']*?)'/is", $html, $height);
        if (empty($height[1]))
            preg_match('/height.?=.?"([^"]*?)"/is', $html, $height);
        return @$height[1];
    }

    function setTransparency($new_image, $image_source)
    {

        $transparencyIndex = imagecolortransparent($image_source);
        $transparencyColor = array('red' => 255, 'green' => 255, 'blue' => 255);

        if ($transparencyIndex >= 0) {
            $transparencyColor = imagecolorsforindex($image_source, $transparencyIndex);
        }

        $transparencyIndex = imagecolorallocate($new_image, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
        imagefill($new_image, 0, 0, $transparencyIndex);
        imagecolortransparent($new_image, $transparencyIndex);
        //------------------------------------------------------------------------------------------------------------//

    }

    function cutImage($buffer, $hasHref = false)
    {

        global $totindex;
        global $country;
        $buffer = str_replace("\\'", "'", $buffer);
        $buffer = str_replace('\\"', '"', $buffer);

        $src = $this->getImgSrc($buffer);
        $width = $this->getImgWidth($buffer);
        $height = $this->getImgHeight($buffer);
        $href = $this->getAHref($buffer);
     
        $arrContextOptions = array(
            'socket' => array(
                'bindto' => '0:0'
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ));

        $config = stream_context_create($arrContextOptions);

        if (!empty($src) && (strpos(strtolower($src), '.jpg') || strpos(strtolower($src), '.jpeg') || strpos(strtolower($src), '.png'))) {

            if (strpos(strtolower($src), '.jpg') || strpos(strtolower($src), '.jpeg')) {
                $ext = '.jpg';
            } else {
                $ext = '.png';
            }
            $src = str_replace('&amp;', '&', $src);
            $src = str_replace('/&#39;', '', $src);
            $href = str_replace('&amp;', '&', $href);
            $href = str_replace('/&#39;', '', $href);

            $fi = fopen('/tmp/img.' . $country . $ext, 'w');

            $im = file_get_contents_curl($src);
            //$im = file_get_contents($src, true, $config);
            fwrite($fi, $im);
            fclose($fi);


            list($orgw, $orgh) = @getimagesize('/tmp/img.' . $country . $ext);

            if ($orgw <= 180 && $orgh <= 80 && $orgh >= 20) {

                if (!empty($width))
                    $width = ' width="' . $width . '"';
                if (!empty($height))
                    $height = ' height="' . $height . '"';
                $ret = "<img src='" . $src . "' border='0'" . $width . $height . " style='display:block;' />";
                if ($hasHref && !empty($href))
                    $ret = '<a href="'.$href.'">'.$ret.'</a>';
                    //$ret = "<a href='" . $href . "'>" . $ret . "</a>";
                return $ret;
            }

            $divh = $orgh / 80;

            $divw = $orgw / 180;
            if (!is_int($divh)) {
                $divtoth = ceil($divh);
                $divokh = floor($divh);
                $resteh = $orgh % 80;
            } else {
                $divtoth = $divh;
                $divokh = $divh;
                $resteh = 0;
            }
            if (!is_int($divw)) {
                $divtotw = ceil($divw);
                $divokw = floor($divw);
                $restew = $orgw % 180;
            } else {
                $divtotw = $divw;
                $divokw = $divw;
                $restew = 0;
            }

            $index = $totindex;
            $ts = time();
            @mkdir(IMG_BASE_DIR . '/' . $ts);
            if ($ext == '.jpg' or $ext == '.jpeg') {
                $im_src = @imagecreatefromjpeg('/tmp/img.' . $country . $ext);
            } /*
            elseif ($ext == '.gif') {
                $im_src = imagecreatefromgif('/tmp/img.' . $country . $ext);
            }
            */
            else if ($ext == '.png') {
                $im_src = @imagecreatefrompng('/tmp/img.' . $country . $ext);

            }
            for ($i = 1; $i <= $divtoth; $i++) {
                for ($j = 1; $j <= $divtotw; $j++) {
                    if ($i <= $divokh)
                        $newh = 80;
                    else
                        $newh = $resteh;
                    if ($j <= $divokw)
                        $neww = 180;
                    else
                        $neww = $restew;

                    if ($newh < 20) {
                        $newh = 20;
                    }
                    $im_dest = imagecreatetruecolor($neww, $newh);

                    //------------------  Hacemos la imagen transparente  <-- Add by Mari! ------------------------//
                    //$image_source = imagecreatefrompng('test.png'); ==>> $im_src = imagecreatefrompng('/tmp/img.'.$country.$ext); (esta arriba)
                    //$new_image = imagecreatetruecolor($width, $height); ==>> $im_dest = imagecreatetruecolor($neww, $newh); (esta arriba)

                    $this->setTransparency($im_dest, $im_src); //Llamamos a la función setTransparency

                    //imagecopyresampled($new_image, $image_source, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height); ==>> imagecopyresampled($im_dest,$im_src,0,0,$left,$top,$neww,$newh,$neww,$newh); (esta debajo)
                    //------------------------------------------------------------------------------------------------------------//


                    $left = ($j - 1) * 180;
                    $top = ($i - 1) * 80;
                    imagecopyresampled($im_dest, $im_src, 0, 0, $left, $top, $neww, $newh, $neww, $newh);
                    if ($ext == '.jpg' or $ext == '.jpeg') {
                        imagejpeg($im_dest, IMG_BASE_DIR . '/' . $ts . '/img_' . $index . $ext, 100);
                    } /*
                    elseif ($ext == '.gif') {
                        imagegif($im_dest, IMG_BASE_DIR . '/' . $ts . '/img_' . $index . $ext);
                    }
                    */
                    else {
                        imagepng($im_dest, IMG_BASE_DIR . '/' . $ts . '/img_' . $index . $ext);
                    }
                    $index++;
                }
            }

            $index = $totindex;
            if ($hasHref) {
                $prefhref = '<a href="' . $href . '">';
                $sufhref = '</a>';
            } else {
                $refhref = '';
                $sufhref = '';
            }

            $html_table = "<table border='0' cellpadding='0' cellspacing='0' align='center'>\n";
            for ($i = 1; $i <= $divtoth; $i++) {

                $html_table .= "<tr>\n";
                for ($j = 1; $j <= $divtotw; $j++) {
                    if ($i <= $divokh)
                        $newh = 80;
                    else

                        $newh = $resteh;
                    if ($j <= $divokw)
                        $neww = 180;
                    else
                        $neww = $restew;
                    $html_table .= "<td>" . $prefhref . "<img src='" . IMG_BASE_URL . "/" . $ts . "/img_" . $index . $ext . "' border='0' style='display: block; text-decoration: none;' />" . $sufhref . "</td>\n";
                    //$html_table .= '<td>' . $prefhref . '<img src="' . IMG_BASE_URL . '/' . $ts . '/img_' . $index . $ext . '" />' . $sufhref . '</td>\n';
                    $index++;

                }
                $html_table .= "</tr>\n";
            }
            $html_table .= "</table>\n";
            $html_table = str_replace("'", '"', $html_table);

            $buffer = $html_table;

            $totindex = $index;
        }

        return $buffer;
    }

    public
    function corregir_width_img($html)
    {

        $pattern = ' /<img(.*)>/siU';
        preg_match_all($pattern, $html, $imagenes);
        $i = 0;
        foreach ($imagenes[0] as $imagen) {

//max-width en style con %
            $imagenes[0][$i] = preg_replace("/max-width:\s{0,}[0-9]{2,3}%;{0,1}+/siU", '', $imagenes[0][$i]);
//max-width en style con px
            $imagenes[0][$i] = preg_replace("/max-width:\s{0,}[0-9]{2,3}px;{0,1}+/siU", '', $imagenes[0][$i]);
//max-height en style con px
            $imagenes[0][$i] = preg_replace("/max-height:\s{0,}[0-9]{2,3}px;{0,1}+/siU", '', $imagenes[0][$i]);
//max-height en style con %
            $imagenes[0][$i] = preg_replace("/max-height:\s{0,}[0-9]{2,3}%;{0,1}+/siU", '', $imagenes[0][$i]);

//min-width en style con %
            $imagenes[0][$i] = preg_replace("/min-width:\s{0,}[0-9]{2,3}%;{0,1}+/siU", '', $imagenes[0][$i]);
//min-width en style con px
            $imagenes[0][$i] = preg_replace("/min-width:\s{0,}[0-9]{2,3}px;{0,1}+/siU", '', $imagenes[0][$i]);
//min-height en style con px
            $imagenes[0][$i] = preg_replace("/min-height:\s{0,}[0-9]{2,3}px;{0,1}+/siU", '', $imagenes[0][$i]);
//min-height en style con %
            $imagenes[0][$i] = preg_replace("/min-height:\s{0,}[0-9]{2,3}%;{0,1}+/siU", '', $imagenes[0][$i]);

//width en style con %
            $imagenes[0][$i] = preg_replace("/width:\s{0,}[0-9]{2,4}%;{0,1}+/siU", '', $imagenes[0][$i]);
//width en style con px
            $imagenes[0][$i] = preg_replace("/width:\s{0,}[0-9]{2,4}px;{0,1}+/siU", '', $imagenes[0][$i]);
//width fuera style con %
            $imagenes[0][$i] = preg_replace("/width=\"\s{0,}[0-9]{2,4}%\"{0,1}+/siU", '', $imagenes[0][$i]);
//height fuera style con %
            $imagenes[0][$i] = preg_replace("/height=\"\s{0,}[0-9]{2,4}%\"{0,1}+/siU", '', $imagenes[0][$i]);
//width fuera style con px
            $imagenes[0][$i] = preg_replace("/width=\"\s{0,}[0-9]{2,4}px\"{0,1}+/siU", '', $imagenes[0][$i]);
//height fuera style con px
            $imagenes[0][$i] = preg_replace("/height=\"\s{0,}[0-9]{2,4}px\"{0,1}+/siU", '', $imagenes[0][$i]);
//width fuera style sin medida
            $imagenes[0][$i] = preg_replace("/width=\"\s{0,}[0-9]{2,4}+\"{0,1}+/siU", '', $imagenes[0][$i]);
//height fuera style sin medida
            $imagenes[0][$i] = preg_replace("/height=\"\s{0,}[0-9]{2,4}+\"{0,1}+/siU", '', $imagenes[0][$i]);
            $html = str_replace($imagen, $imagenes[0][$i], $html);
            $i++;
        }

        /*
          $pattern = ' /<img .*\n * \s * (style = \n * \s * \".*(width:\s{0,}[0-9]{2,4}.[px|%][;|"]{
            1}).*\").*\n*\s*\/>/siU';
          preg_match_all($pattern, $html, $coincidencias);
          var_dump($coincidencias);
          //SUSTITUIR EL GROUP DEL WIDTH EN EL GROUP ENTERO DE IMG, Y LUEGO EL GROUP DE IMG EN HTML
          $coincidencias_img = array();
          $i = 0;
          foreach ($coincidencias[2] as $width) {
          $replace = str_replace($width, "", $coincidencias[0][$i]);
          array_push($coincidencias_img, $replace);
          $i++;
          }
          $j = 0;
          foreach ($coincidencias[0] as $img) {
          $html = str_replace($img, $coincidencias_img[$j], $html);
          $j++;
          }
         * */

        return $html;
    }

    public
    function corregir_width($html)
    {
//var_dump($html);
        if (preg_match_all('/<table.{0,}style.{0,}width:\s{0,}[0-9]{2,4}.[px|%][;|"]{1}.{0,}(?=>|$)/i', $html, $coincidencias)) {
            /*
             * QUEDARSE CON EL WIDTH QUE TENGA LA MEDIDA EN PIXELS
             */
//var_dump($coincidencias);

            foreach ($coincidencias[0] as $fila) {
//CASO PARA CUANDO TIENE WIDTH FUERA DEL STYLE, SE QUEDARA CON EL WIDTH DE FUERA DEL STYLE

                $fila_original = $fila;
                preg_match(' /width.?=?\"[0-9]{2,4}.?\"/', $fila, $width_viejo);


//IF !WIDTH = 100%: PREG_REPLACE DE AQUI ABAJO; ELSE: A BUSCAR OTRO WIDTH EN EL STYLE
                if (!empty($width_viejo) && strpos($width_viejo[0], "100% ") == 0) {
                    $fila = preg_replace('/(width:\s{0,}[0-9]{2,4}(px|%){1};{0,1})+/', '', $fila);
                } else {

//CASO PARA CUANDO NO TIENE WIDTH, HABRA QUE AISLAR EL WIDTH DEL STYLE, E INTRODUCIRLO DESPUES DE <TABLE
                    /* FALLA LA ESTRUCTURA DE LOS IFS  */
                    if (!preg_match_all('/[^max-|min-](width:\s{0,}[0-9]{2,4}(px|%){1};{0,1})/', $fila, $widths)) {

                        if (!preg_match_all('/max-(width:\s{0,}[0-9]{2,4}(px|%){1};{0,1})/', $fila, $widths)) {

                            preg_match_all('/min-(width:\s{0,}[0-9]{2,4}(px|%){1};{0,1})/', $fila, $widths);
                        }

//de $fila hay que sacar  preg_replace('/(width:\s{0,}[0-9]{2,4}(px|%){1};)+/', '', $fila), comparar cual es mayor, y ponerla en la posicion tras <table
//BORRAR WIDTH FUERA DEL STYLE SI TUVIESE
                    }

                    $i = 0;
                    $t = 0;
                    $width_casi_definitivo = 0;
                    $width_bueno = 0;
                    $width_definitivo = 0;

                    foreach ($widths[1] as $width) {

                        if (strpos($width, "%") != 0) {
//CUIDADO SI TUVIESE VARIOS WIDTH CON %crea
                            $resultado = intval(preg_replace('/[^0-9]+/', '', $width));

                            if ($resultado > $t) {
                                $width_casi_definitivo = $width;
                                $t = $resultado;
                            }
                        } else {
                            $resultado = intval(preg_replace('/[^0-9]+/', '', $width));
                            if ($resultado > $i) {

                                $width_bueno = $width;
                                $i = $resultado;
                            }
                        }
                        if ($width_bueno !== 0) {
                            $width_definitivo = $width_bueno;
                        } else {
//echo "NO DEBERIA ENTRAR AQUI SI EL width_casi_definitivo TIENE VALOR";
                            $width_definitivo = $width_casi_definitivo;
                        }
                    }
                    $fila = preg_replace('/(width:\s{0,}[0-9]{2,4}(px|%){1};{0,1})/', '', $fila);
                    preg_match('/[0-9]{2,4}(px|%)/', $width_definitivo, $cantidad);
                    $width_definitivo = 'width="' . $cantidad[0] . '"';
                    if (strpos($width_definitivo, 'px')) {
                        $width_definitivo = str_replace("px", "", $width_definitivo);
                    }
                    $fila = str_replace($width_viejo, "", $fila);

                    $fila = str_replace(" <table", " <table " . $width_definitivo, $fila);
                }
                $html = str_replace($fila_original, $fila, $html);
            }
        }
//var_dump($html);
        return $html;
    }

    public
    function comprobar_width($html)
    {
//BUSCAR TODAS LAS TABLE
//SI NO TIENEN WIDTH, AÑADIRLE 100%
        if (preg_match_all('/<table.*?>/i', $html, $coincidencias)) {
//COMPROBAR SI TIENE WIDTH="X"

            foreach ($coincidencias[0] as $fila) {

                $fila_original = $fila;
                if (!preg_match('/width.?=?\"[0-9]{2,4}.?\"/i', $fila)) {
//SI NO TIENE WIDTH, HAY QUE METER EN LA FILA EL NUEVO WIDTH


                    $fila = str_replace(" < table ", '<table width="100% " ', $fila);
                    //$fila = str_replace(" < table ", '<table width="auto" ', $fila);

                    $html = str_replace($fila_original, $fila, $html);
                }
            }
        }
        return $html;
    }

    public
    function borrar_etiquetas_vacias($html)
    {
//REVISAR
        //HACER BUCLE PARA QUE BORRE DESDE TD A TABLE
        $pattern = '~<([^<\/>]*)([^<\/>]*)>([\s]*?|(?R))<\/\1>~isUm';
        //$html = preg_replace($pattern, '', $html);
        /* CUIDADO QUE NO ESTA COMPROBADO LO DEL PADDING */
        //var_dump($html);
        while (preg_match_all($pattern, $html, $coincidencias)) {
            //var_dump($coincidencias);
            foreach ($coincidencias[0] as $element) {
                //AL NO REEMPLAZAR LOS QUE TIENEN PADDING, EL BUCLE SE VUELVE INFINITO

                if (!strpos($element, "padding") != 0) {
                    $html = preg_replace($pattern, '', $html);
                } else {
                    $posicion = strpos($element, " </td > ");
                    //var_dump(substr_replace($element," & nbsp;</td > ",$posicion));
                    $elem = substr_replace($element, " & nbsp;</td > ", $posicion);
                    $html = str_replace($element, $elem, $html);
                    //PREG_REPLACE DE $elemento PARA PONER EL &nbsp;

                }
                //PODRIA METER UN &nbsp; para que ya no estuviese vacio el td con padding
            }
        }
        //preg_match_all($pattern, $html, $borrados);
        return $html;
//CON ESTA LO QUE HACEMOS ES ENCONTRAR UN GRUPO DE TABLAS HASTA QUE SE CIERRA UNA DE ELLAS.
    }

    public
    function borrar_tables($html)
    {
//REVISAR
//var_dump($html);
        $exp_reg_encontrar_tables = '~(<table)(?!.*\1)\s{0,}(style?=\".*?\"*?)*?.*><tr><td>.?</td></tr></table>*?~siU';
        while (preg_match($exp_reg_encontrar_tables, $html, $tables)) {
            $html = str_replace($tables[0], '', $html);
        }


        /*
          $i = 0;
          $string = "";
          var_dump($tables);
          foreach ($tables[0] as $fila) {
          preg_match('~<table\s{0,}(style?=\".*?\"*?)*?.*><tr><td>.+</td></tr></table>+?~iU', $fila, $table);
          var_dump($table);

          $string .= $table[0];
          $i++;
          }

          preg_match_all("~<body>(.+)</body > ~is", $html, $body);
          if (!empty($string)) {
          $html = str_replace($body[1], $string, $html);
          }
         */
//$html = str_replace('</td></tr></table>', '', $html);
        /*
          while($i > 0){
          $exp = '~.*?</td></tr></table>~ig';
          //ENCUENTRA TODO LO QUE ACABE </td></tr></table>
          //SOLO NECESITO LAS FILAS QUE TENGAN ESO LITERALMENTE
          preg_match_all($exp, $html, $borrados);
          if ($borrados === '</td></tr></table>'){

          }
          var_dump($borrados);
          }
         *
         */
        return $html;
//CON ESTA LO QUE HACEMOS ES ENCONTRAR UN GRUPO DE TABLAS HASTA QUE SE CIERRA UNA DE ELLAS.
    }

    /* public function addcellpadding_cellspadding_tables2($html)
      {
      // añadimos cellspacing y cellpadding a todas las tablas que no lo tengan .
      preg_match_all(" /<table[^>]*>{
            0}/is", $html, $tables);
      $tables[0] = array_unique($tables[0]);
      foreach ($tables[0] as $key=>$table)
      {
      echo "..............................\n";
      $newtable = $table;
      preg_match(" / cellspacing .?=.?\".?0.?\"/is", $table, $cellspacing);
      if (empty($cellspacing[0]) || $cellspacing[0] == "NULL") {
          $newtable .= ' cellspacing ="0" ';
      }

      preg_match("/cellpadding.?=.?\".?0.?\"/is", $newtable, $cellpadding);
      if (empty($cellpadding[0]) || $cellpadding[0] == "NULL") {
          $newtable .= ' cellpadding ="0" ';
      }

      echo "\nA:" . $table . "\n";
      echo "\nB:" . $newtable . "\n";

      if ($table !== $newtable) {
          echo "\nHAY REMPLAZO!!\n";
          $html = str_replace($table, $newtable, $html);
      }


      }
sleep(50);
return $html;
}

*/

    /*  FORMATO BOTON QUE FUNCIONA
     *
     * <table cellspacing="0" cellpadding="0" align="center" style="">
      <tr style="">
      <td style=" border-radius: 3px; text-align: center; background: #ffc726;"><a href="" style="font-weight: bold; font-size: 18px; color: #000;  font-family: sans-serif; line-height: 1.1; text-align: center; text-decoration: none; display: block; border-radius: 3px; background: #ffc726; padding: 15px; border: 1px solid #ffc726;">Clicca qui</a></td>
      </tr>
      </table>
     *
     */

    public
    function removeImportantStyle($html)
    {
//return $html = str_replace(' !important', '', $html);
        return $html = preg_replace("/\s{0,}!important/", '', $html);
    }

    public
    function removeStuffs($html)
    {
        //$html = str_replace(' !important', '', $html);
        /*

       $html = str_replace('border-collapse: separate;', '', $html);
       $html = str_replace('border-collapse: unset;', '', $html);
       $html = str_replace('border-collapse:collapse;', '', $html);
       */
        /*
               $html = str_replace('border: 0;', '', $html);
               $html = str_replace('border: none;', '', $html);
               $html = str_replace('border: 0 none;', '', $html);
         */
        /*
        $html = str_replace('margin: 0 auto;', '', $html);
        $html = str_replace('margin: auto;', '', $html);
        $html = str_replace('margin: 0px;', '', $html);
        $html = str_replace('margin-left: auto;', '', $html);
        $html = str_replace('margin-right: auto;', '', $html);
        $html = str_replace('margin-bottom: 0px;', '', $html);
        $html = str_replace('margin-top: 0;', '', $html);
        $html = str_replace('margin-bottom: 0;', '', $html);
        $html = str_replace('margin-top: 0px;', '', $html);
        $html = str_replace('Margin: 0 auto;', '', $html);
        $html = str_replace('Margin: 0;', '', $html);
        $html = str_replace('Margin:0;', '', $html);
        $html = str_replace('float: none;', '', $html);
        $html = str_replace('float: left;', '', $html);
        $html = str_replace('float: right;', '', $html);
        $html = str_replace('border-spacing: 0;', '', $html);
        $html = str_replace('border-spacing:0;', '', $html);
        $html = str_replace('display: table;', '', $html);
        $html = str_replace('display:table;', '', $html);
        $html = str_replace('font-size: 0;', '', $html);
        $html = str_replace('font-size: 0px;', '', $html);
 $html = str_replace('word-break: break-word;', '', $html);
        $html = str_replace('word-break: keep-all;', '', $html);
        $html = str_replace('transition: all 0.7s ease-out;', '', $html);
        $html = str_replace('clear: both;', '', $html);
        $html = str_replace('word-wrap: break-word;', '', $html);
          $html = str_replace('hyphens: auto;', '', $html);
        $html = str_replace('hyphens: none;', '', $html);
          $html = str_replace('box-sizing: border-box;', '', $html);
        $html = str_replace('outline: 0;', '', $html);
        $html = str_replace('outline: none;', '', $html);
         $html = str_replace('list-style: none;', '', $html);
          $html = str_replace('height: auto;', '', $html);
        $html = str_replace('height: 0px;', '', $html);
        $html = str_replace('height: inherit;', '', $html);
         $html = str_replace('width: 0;', '', $html);
        $html = str_replace('width: 0px;', '', $html);
         $html = str_replace('overflow-wrap: break-word;', '', $html);
         $html = str_replace('opacity: 0;', '', $html);
        $html = str_replace('overflow: hidden;', '', $html);
        $html = str_replace('visibility: hidden;', '', $html);
         $html = str_replace('overflow: visible; ', '', $html);
$html = str_replace('font-size:1px;', '', $html);
$html = str_replace('text-resize: 100%;', '', $html);
        $html = str_replace('color:trasparent;', '', $html);
        $html = str_replace('border-spacing:0px;', '', $html);
        $html = str_replace('overflow-x: hidden;', '', $html);
        $html = str_replace('font-smoothing: antialiased;', '', $html);
        $html = str_replace('text-rendering: optimizeLegibility;', '', $html);
        $html = str_replace('object-fit: contain;', '', $html);
         */

        $html = str_replace('min-', '', $html);
        $html = str_replace('max-', '', $html);

        $html = preg_replace("/mso-[a-z-]{0,}:.*;/iU", '', $html);
        $html = preg_replace("/-webkit-[a-z-]{0,}:.*;/iU", '', $html);
        $html = preg_replace("/-ms-[a-z-]{0,}:.*;/iU", '', $html);
        $html = preg_replace("/-moz-[a-z-]{0,}:.*;/iU", '', $html);
        $html = preg_replace("/-o-[a-z-]{0,}:.*;/iU", '', $html);

        $html = preg_replace("/border-collapse:.*;/Ui", '', $html);
        $html = preg_replace("/border:.*;/Ui", '', $html);
        $html = preg_replace("/border-spacing:.*;/Ui", '', $html);
        $html = preg_replace('/background-color:.*transparent;/Ui', '', $html);
        $html = preg_replace('/background:.*transparent;/Ui', '', $html);
        $html = preg_replace('/background-repeat:.*repeat;/Ui', '', $html);
        $html = preg_replace('/margin(-top|-left|-bottom|-right)*?:.*;/Ui', '', $html);
        $html = preg_replace("/direction:.*;/Ui", '', $html);
        $html = preg_replace("/xline-height:.*;/Ui", '', $html);
        /* LINE-HEIGHT: SELECCIONAR TODOS MENOS LOS QUE SEAN xxpx */
        $html = preg_replace("/line-height:.*;/Ui", '', $html);
        $html = preg_replace("/float:.*;/Ui", '', $html);


        $html = preg_replace('/height:.*;/Ui', '', $html);
        $html = preg_replace('/width:.*;/Ui', '', $html);
        $html = preg_replace("/position:.*;/Ui", '', $html);
        $html = preg_replace("/display:.*table.*;/Ui", '', $html);
        $html = preg_replace("/font-size:.*(?:(?<![\d])[0|1][px]*?;)/Ui", '', $html);
        $html = preg_replace('/letter-spacing:.*0px;/Ui', '', $html);
        $html = preg_replace('/text-align:.*inherit;/Ui', '', $html);
        $html = preg_replace('/table-layout:.*;/Ui', '', $html);
        $html = preg_replace('/word-break:.*;/Ui', '', $html);
        $html = preg_replace('/transition:.*;/Ui', '', $html);
        $html = preg_replace('/clear:.*;/Ui', '', $html);
        $html = preg_replace('/word-wrap:.*;/Ui', '', $html);
        $html = preg_replace('/hyphens:.*;/Ui', '', $html);
        $html = preg_replace('/box-sizing:.*;/Ui', '', $html);
        $html = preg_replace('/outline:.*;/Ui', '', $html);
        $html = preg_replace('/list-style:.*;/Ui', '', $html);
        $html = preg_replace('/overflow-wrap:.*;/Ui', '', $html);
        $html = preg_replace('/opacity:.*;/Ui', '', $html);
        $html = preg_replace('/overflow:.*;/Ui', '', $html);
        $html = preg_replace('/visibility:.*;/Ui', '', $html);
        $html = preg_replace('/text-resize:.*;/Ui', '', $html);
        $html = preg_replace('/color:.*trasparent;/Ui', '', $html);
        $html = preg_replace('/border-spacing:.*;/Ui', '', $html);
        $html = preg_replace('/overflow-x:.*hidden;/Ui', '', $html);
        $html = preg_replace('/font-smoothing:.*antialiased;/Ui', '', $html);
        $html = preg_replace('/text-rendering:.*optimizeLegibility;/Ui', '', $html);
        $html = preg_replace('/object-fit:.*contain;/Ui', '', $html);
        //PROBAR CON padding: 0 0 5px 10px
        $html = preg_replace('/padding(?:-right|-left|-top|-bottom){0,1}:.*(?:(?<![\d])0[px]*?;)/Ui', '', $html);
        //NO FUNCIONA TODAVIA LA DE LOS BORDER-TOP,LEFT,RIGHT,BOTTOM
        //$html = preg_replace('/border(?:-right|-left|-top|-bottom){0,1}:.*(?:(?<![\d])0[px]*?;)/Ui', '', $html);


        $html = str_replace('border="0"', '', $html);
        $html = str_replace('center center / cover', '', $html);
        $html = str_replace('marginwidth="0"', '', $html);
        $html = str_replace('marginheight="0"', '', $html);
        $html = str_replace('topmargin="0" ', '', $html);
        $html = str_replace('leftmargin="0"', '', $html);
        $html = str_replace('target="_blank"', '', $html);
        $html = str_replace('background-color: Transparent;', '', $html);


        $html = str_replace('border-top: 0px solid transparent;', '', $html);
        $html = str_replace('border-left: 0px solid transparent;', '', $html);
        $html = str_replace('border-bottom: 0px solid transparent;', '', $html);
        $html = str_replace('border-right: 0px solid transparent;', '', $html);

        $html = str_replace('padding:0;', '', $html);
        $html = str_replace('padding: 0;', '', $html);
        $html = str_replace('padding:0px', '', $html);
        $html = str_replace('padding-left: 0px;', '', $html);
        $html = str_replace('padding-right: 0px;', '', $html);
        $html = str_replace('padding-bottom: 0px;', '', $html);
        $html = str_replace('padding-top: 0px;', '', $html);
        $html = str_replace('padding: 0px;', '', $html);
        $html = str_replace('padding-top: 0;', '', $html);
        $html = str_replace('padding-bottom: 0;', '', $html);
        $html = str_replace('padding-left: 0;', '', $html);
        $html = str_replace('padding-right: 0;', '', $html);
        $html = str_replace('padding: 0 0px;', '', $html);

        //$html = str_replace('xline-', '', $html);
        //$html = str_replace('vw;', '', $html);
        $html = preg_replace('/style="\s{0,}"/is', '', $html);

//$html = str_replace('display:none;', '', $html);


        //PONER EL PREG_REPLACE DE FORMA QUE NO COJA EL DE LAS IMAGENES
        //$html = str_replace('display: block;', '', $html);


        //$html = str_replace('width: 100%;', '', $html);
        //$html = str_replace('width: 600px;', '', $html);
        /* FLOATS */
        /* MARGINS */
        /* MSO */
        /*
          $html = str_replace('mso-table-lspace: 0pt;', '', $html);
          $html = str_replace('mso-table-lspace: 0;', '', $html);
          $html = str_replace('mso-table-rspace: 0pt;', '', $html);
          $html = str_replace('mso-table-rspace: 0;', '', $html);
          $html = str_replace('mso-line-height-rule:exactly;', '', $html);
          $html = str_replace('mso-line-height-rule: exactly;', '', $html);
          $html = str_replace('mso-hide: all;', '', $html);
          $html = str_replace('mso-margin-top-alt: 0px;', '', $html);
          $html = str_replace('mso-margin-bottom-alt: 0px;', '', $html);
          $html = str_replace('mso-padding-alt: 0px;', '', $html);
          $html = str_replace('mso-table-lspace: 0px;', '', $html);
          $html = str_replace('mso-table-rspace: 0px;', '', $html);
          $html = str_replace('mso-border-alt: none;', '', $html);
          $html = str_replace('mso-cellspacing: 0px;', '', $html);
          $html = str_replace('mso-padding-alt: 0px 0px 0px 0px;', '', $html);
          $html = str_replace('mso-height-rule: exactly;', '', $html);
          $html = str_replace('mso-hide:all;', '', $html);
          $html = str_replace('mso-table-rspace:0pt;', '', $html);
          $html = str_replace('mso-table-lspace:0pt;', '', $html);
         */

        /* WEBKIT */
        /*
          $html = str_replace('-webkit-hyphens: auto;', '', $html);
          $html = str_replace('-webkit-box-sizing: border-box;', '', $html);
          $html = str_replace('-webkit-text-size-adjust: 100%;', '', $html);
          $html = str_replace('-webkit-font-smoothing: antialiased;', '', $html);
          $html = str_replace('-webkit-text-size-adjust: none;', '', $html);
          $html = str_replace('-webkit-hyphens: none;', '', $html);
          $html = str_replace('-webkit-margin-after: 0;', '', $html);
          $html = str_replace('-webkit-margin-before: 0;', '', $html);
          $html = str_replace('-webkit-text-resize: 100%;', '', $html);
          $html = str_replace('-webkit-text-resize: 100%;', '', $html);
         */


        /* MS */
        /*
          $html = str_replace('-ms-text-size-adjust: 100%;', '', $html);
          $html = str_replace('-ms-interpolation-mode: bicubic;', '', $html);
          $html = str_replace('-ms-box-sizing: border-box;', '', $html);
          $html = str_replace('-ms-hyphens: none;', '', $html);
          $html = str_replace('-ms-text-size-adjust: none;', '', $html);
         */

        /* MOZ */
        /*
          $html = str_replace('-moz-box-sizing: border-box;', '', $html);
          $html = str_replace('-moz-hyphens: auto;', '', $html);
          $html = str_replace('-moz-hyphens: none;', '', $html);
          $html = str_replace('-moz-text-size-adjust: none;', '', $html);
         */

        /* OPERA */
        /*
          $html = str_replace('-o-box-sizing: border-box;', '', $html);
         */

        /*
          $html = str_replace('line-height: 0;', '', $html);
          $html = str_replace('line-height: 0px;', '', $html);

          $html = str_replace('line-height: inherit;', '', $html);
          $html = str_replace('line-height: 100%;', '', $html);
          $html = str_replace('line-height: 1;', '', $html);
          $html = str_replace('line-height: normal;', '', $html);
          $html = str_replace('line-height:1px;', '', $html);
         *
         */


        return $html;
    }

    public
    function correctFontTags($html)
    {
        /* PODRIAMOS HACER PREG_MATCH DE LAS FONT Y LUEGO ANALIZAR SU STYLE
         * SI ES UN STYLE NORMAL, PUES CAMBIAMOS SENCILLAMENTE POR SPAN
         * SI CONTIENE FACE=, COLOR= O SIZE= ENTONCES LOS CONVERTIMOS A IDIOMA CSS
         */
        /*
                  $infont = false;
                  $inface = false;
                  $instyle = false;
                  $incolor = false;
                  $insize = false;
                  $hasquotes = false;
                  $size = '';
                  $face = '';
                  $color = '';
                  $style = '';
                  $new_html = '';
                  $i = 0;

                  while ($i < strlen($html)) {
                  if (substr(strtolower($html), $i, 5) == '<font') {
                  $new_html .= "<font ";
                  $infont = true;
                  $i = $i + 4;
                  }
                  if ($infont) {
                  if (substr($html, $i, 1) == '>') {
                  //insert style
                  $mystyle = " style='";
                  if (!empty($size)) {
                  if ($size == 4)
                  $size = "17px";
                  if ($size == 3)
                  $size = "15px";
                  if ($size == 2)
                  $size = "12px";
                  if ($size == 1)
                  $size = "11px";
                  $mystyle .= 'font-size:' . $size . ';';
                  }
                  if (!empty($color))
                  $mystyle .= 'color:' . $color . ';';
                  if (!empty($face))
                  $mystyle .= 'font-family:' . $face . ';';
                  if (!empty($style))
                  $mystyle .= $style;
                  $mystyle .= "'";
                  $new_html .= $mystyle . '>';
                  $infont = false;
                  $face = '';
                  $color = '';
                  $size = '';
                  $style = '';
                  $inface = false;
                  $instyle = false;
                  $incolor = false;
                  $insize = false;
                  $hasquotes = false;
                  }
                  if (((substr($html, $i, 1) == "'" || substr($html, $i, 1) == '"') && $hasquotes) || (substr($html, $i, 1) == " " && !$hasquotes)) {
                  $inface = false;
                  $instyle = false;
                  $incolor = false;
                  $insize = false;
                  $hasquotes = false;
                  }
                  // ALGUN PROBLEMA EN LA PARTE DEL FONT-FAMILY PORQUE NO LO DEVUELVE VACIO
                  if ($inface && (($hasquotes && substr($html, $i, 1) != "'" && substr($html, $i, 1) != '"') || (!$hasquotes && substr($html, $i, 1) != ' ')))
                  $face .= substr($html, $i, 1);
                  if ($insize && (($hasquotes && substr($html, $i, 1) != "'" && substr($html, $i, 1) != '"') || (!$hasquotes && substr($html, $i, 1) != ' ')))
                  $size .= substr($html, $i, 1);
                  if ($incolor && (($hasquotes && substr($html, $i, 1) != "'" && substr($html, $i, 1) != '"') || (!$hasquotes && substr($html, $i, 1) != ' ')))
                  $color .= substr($html, $i, 1);
                  if ($instyle && (($hasquotes && substr($html, $i, 1) != "'" && substr($html, $i, 1) != '"') || (!$hasquotes && substr($html, $i, 1) != ' ')))
                  $style .= substr($html, $i, 1);

                  if (substr(strtolower($html), $i, 6) == 'face="' || substr(strtolower($html), $i, 6) == "face='") {
                  $inface = true;
                  $hasquotes = true;
                  $i = $i + 5;
                  } else if (substr(strtolower($html), $i, 6) == 'size="' || substr(strtolower($html), $i, 6) == "size='") {
                  $insize = true;
                  $hasquotes = true;
                  $i = $i + 5;
                  } else if (substr(strtolower($html), $i, 7) == 'color="' || substr(strtolower($html), $i, 7) == "color='") {
                  $incolor = true;
                  $hasquotes = true;
                  $i = $i + 6;
                  } else if (substr(strtolower($html), $i, 7) == 'style="' || substr(strtolower($html), $i, 7) == "style='") {
                  $instyle = true;
                  $hasquotes = true;
                  $i = $i + 6;
                  } else if (substr(strtolower($html), $i, 5) == 'face=') {
                  $inface = true;
                  $hasquotes = false;
                  $i = $i + 4;
                  } else if (substr(strtolower($html), $i, 5) == 'size=') {
                  $insize = true;
                  $hasquotes = false;
                  $i = $i + 4;
                  } else if (substr(strtolower($html), $i, 6) == 'color=') {
                  $incolor = true;
                  $hasquotes = false;
                  $i = $i + 5;
                  } else if (substr(strtolower($html), $i, 6) == 'style=') {
                  $instyle = true;
                  $hasquotes = false;
                  $i = $i + 5;
                  }
                  } else {
                  $new_html .= substr($html, $i, 1);
                  }
                  $i++;
                  }



                  $html = $new_html;
               */
        $html = str_replace('<font ', '<span ', $html);
        $html = str_replace('</font>', '</span>', $html);
        $html = str_replace('<FONT ', '<span ', $html);
        $html = str_replace('</FONT>', '</span>', $html);

        return $html;
    }

    public
    function correctStyleTags($html)
    {
        return preg_replace("/<styl(.*?)<!--(.*?)-->(.*?)\/style>/si", "<styl$1$2$3\/style>", $html);
    }

    public
    function removeXMLTag($html)
    {
        return preg_replace('/<\?xml ([^>]*?)>./si', '', $html);
    }

    public
    function correctChars($html)
    {
        $html = str_replace('ä', '&auml;', $html);
        $html = str_replace('à', '&agrave;', $html);
        $html = str_replace('á', '&aacute;', $html);
        $html = str_replace('â', '&acirc;', $html);
        $html = str_replace('ã', '&auml;', $html);
        $html = str_replace('å', '&aring;', $html);
        $html = str_replace('è', '&egrave;', $html);
        $html = str_replace('é', '&eacute;', $html);
        $html = str_replace('ê', '&ecirc;', $html);
        $html = str_replace('ë', '&euml;', $html);
        $html = str_replace('ì', '&igrave;', $html);
        $html = str_replace('í', '&iacute;', $html);
        $html = str_replace('î', '&icirc;', $html);
        $html = str_replace('ï', '&iuml;', $html);
        $html = str_replace('ò', '&ograve;', $html);
        $html = str_replace('ó', '&oacute;', $html);
        $html = str_replace('ô', '&ocirc;', $html);
        $html = str_replace('õ', '&otilde;', $html);
        $html = str_replace('ö', '&ouml;', $html);
        $html = str_replace('ù', '&ugrave;', $html);
        $html = str_replace('ú', '&uacute;', $html);
        $html = str_replace('û', '&ucirc;', $html);
        $html = str_replace('ü', '&uuml;', $html);
        $html = str_replace('À', '&Agrave;', $html);
        $html = str_replace('Á', '&Aacute;', $html);
        $html = str_replace('Â', '&Acirc;', $html);
        $html = str_replace('Ã', '&Atilde;', $html);
        $html = str_replace('Ä', '&Auml;', $html);
        $html = str_replace('Å', '&Aring;', $html);
        $html = str_replace('È', '&Egrave;', $html);
        $html = str_replace('É', '&Eacute;', $html);
        $html = str_replace('Ê', '&Ecirc;', $html);
        $html = str_replace('Ë', '&Euml;', $html);
        $html = str_replace('Ì', '&Igrave;', $html);
        $html = str_replace('Í', '&Iacute;', $html);
        $html = str_replace('Î', '&Icirc;', $html);
        $html = str_replace('Ï', '&Iuml;', $html);
        $html = str_replace('Ò', '&Ograve;', $html);
        $html = str_replace('Ó', '&Oacute;', $html);
        $html = str_replace('Ô', '&Ocirc;', $html);
        $html = str_replace('Õ', '&Otilde;', $html);
        $html = str_replace('Ö', '&Ouml;', $html);
        $html = str_replace('Ù', '&Ugrave;', $html);
        $html = str_replace('Ú', '&Uacute;', $html);
        $html = str_replace('Û', '&Ucirc;', $html);
        $html = str_replace('Ü', '&Uuml;', $html);
        $html = str_replace('ñ', '&ntilde;', $html);
        $html = str_replace('Ñ', '&Ntilde;', $html);
        $html = str_replace('æ', '&aelig;', $html);
        $html = str_replace('Æ', '&AElig;', $html);
        $html = str_replace('Ǽ', '&#808;', $html);
        $html = str_replace('ǽ', '&#809;', $html);
        $html = str_replace('Œ', '&OElig;', $html);
        $html = str_replace('œ', '&oelig;', $html);
        $html = str_replace('ç', '&ccedil;', $html);
        $html = str_replace('Ç', '&Ccedil;', $html);
        $html = str_replace('ø', '&oslash;', $html);
        $html = str_replace('Ø', '&Oslash;', $html);
        $html = str_replace('Ý', '&Yacute;', $html);
        $html = str_replace('ý', '&yacute;', $html);
        $html = str_replace('ÿ', '&yuml;', $html);
        $html = str_replace('Ÿ', '&Yuml;', $html);
        $html = str_replace('Š', '&Scaron;', $html);
        $html = str_replace('š', '&scaron;', $html);
        $html = str_replace('Ň', '&#327;', $html);
        $html = str_replace('ň', '&#328;', $html);
        $html = str_replace('þ', '&thorn;', $html);
        $html = str_replace('Þ', '&THORN;', $html);
        $html = str_replace('ß', '&szlig;', $html);
        $html = str_replace('Ð', '&ETH;', $html);
        $html = str_replace('ð', '&eth;', $html);
        $html = str_replace('µ', '&micro;', $html);
        $html = str_replace('ƒ', '&fnof;', $html);
        $html = str_replace('¹', '&sup1;', $html);
        $html = str_replace('²', '&sup2;', $html);
        $html = str_replace('³', '&sup3;', $html);
        $html = str_replace('°', '&deg;', $html);
        $html = str_replace('º', '&ordm;', $html);
        $html = str_replace('ª', '&ordf;', $html);
        $html = str_replace('´', "'", $html);
        $html = str_replace('»', '&raquo;', $html);
        $html = str_replace('«', '&laquo;', $html);
        $html = str_replace('‹', '&lsaquo;', $html);
        $html = str_replace('›', '&rsaquo;', $html);
        $html = str_replace('¿', '&iquest;', $html);
        $html = str_replace('¡', '&iexcl;', $html);
        $html = str_replace('©', '&copy;', $html);
        $html = str_replace('®', '&reg;', $html);
        $html = str_replace('™', '&trade;', $html);
        $html = str_replace('¢', '&cent;', $html);
        $html = str_replace('£', '&pound;', $html);
        $html = str_replace('¤', '&curren;', $html);
        $html = str_replace('¥', '&yen;', $html);
        $html = str_replace('¼', '&frac14;', $html);
        $html = str_replace('½', '&frac12;', $html);
        $html = str_replace('¾', '&frac34;', $html);
        $html = str_replace('÷', '&divide;', $html);
        $html = str_replace('•', '&bull;', $html);
        $html = str_replace('…', '&hellip;', $html);
        $html = str_replace('′', '&prime;', $html);
        $html = str_replace('″', '&Prime;', $html);
        $html = str_replace('‾', '&oline;', $html);
        $html = str_replace('⁄', '&frasl;', $html);
        $html = str_replace('·', '&middot;', $html);
        $html = str_replace('±', '&plusmn;', $html);
        $html = str_replace('◊', '&loz;', $html);
        $html = str_replace('♠', '&spades;', $html);
        $html = str_replace('♣', '&clubs;', $html);
        $html = str_replace('♥', '&hearts;', $html);
        $html = str_replace('♦', '&diams;', $html);
        $html = str_replace('–', '&ndash;', $html);
        $html = str_replace('—', '&mdash;', $html);
        $html = str_replace('‘', '&lsquo;', $html);
        $html = str_replace('’', '&rsquo;', $html);
        $html = str_replace('‚', '&sbquo;', $html);
        $html = str_replace('“', '&ldquo;', $html);
        $html = str_replace('”', '&rdquo;', $html);
        $html = str_replace('„', '&bdquo;', $html);
        $html = str_replace('†', '&dagger;', $html);
        $html = str_replace('‡', '&Dagger;', $html);
        $html = str_replace('‰', '&permil;', $html);
        $html = str_replace('α', '&alpha;', $html);
        $html = str_replace('β', '&beta;', $html);
        $html = str_replace('γ', '&gamma;', $html);
        $html = str_replace('δ', '&delta;', $html);
        $html = str_replace('ε', '&epsilon;', $html);
        $html = str_replace('ζ', '&zeta;', $html);
        $html = str_replace('η', '&eta;', $html);
        $html = str_replace('θ', '&theta;', $html);
        $html = str_replace('ι', '&iota;', $html);
        $html = str_replace('κ', '&kappa;', $html);
        $html = str_replace('λ', '&lambda;', $html);
        $html = str_replace('μ', '&mu;', $html);
        $html = str_replace('ν', '&nu;', $html);
        $html = str_replace('ξ', '&xi;', $html);
        $html = str_replace('ο', '&omicron;', $html);
        $html = str_replace('π', '&pi;', $html);
        $html = str_replace('ρ', '&rho;', $html);
        $html = str_replace('ς', '&sigmaf;', $html);
        $html = str_replace('σ', '&sigma;', $html);
        $html = str_replace('τ', '&tau;', $html);
        $html = str_replace('υ', '&upsilon;', $html);
        $html = str_replace('φ', '&phi;', $html);
        $html = str_replace('χ', '&chi;', $html);
        $html = str_replace('ψ', '&psi;', $html);
        $html = str_replace('ω', '&omega;', $html);
        $html = str_replace('ϑ', '&thetasym;', $html);
        $html = str_replace('ϒ', '&upsih;', $html);
        $html = str_replace('ϖ', '&piv;', $html);
        $html = str_replace('Ą', '&#260;', $html);
        $html = str_replace('ą', '&#261;', $html);
        $html = str_replace('Ć', '&#262;', $html);
        $html = str_replace('ć', '&#263;', $html);
        $html = str_replace('Ę', '&#280;', $html);
        $html = str_replace('ę', '&#281;', $html);
        $html = str_replace('Ł', '&#321;', $html);
        $html = str_replace('ł', '&#322;', $html);
        $html = str_replace('Ń', '&#323;', $html);
        $html = str_replace('ń', '&#324;', $html);
        $html = str_replace('Ś', '&#346;', $html);
        $html = str_replace('ś', '&#347;', $html);
        $html = str_replace('Ź', '&#377;', $html);
        $html = str_replace('ź', '&#378;', $html);
        $html = str_replace('Ż', '&#379;', $html);
        $html = str_replace('ż', '&#380;', $html);
        $html = str_replace('Ğ', '&#286;', $html);
        $html = str_replace('ğ', '&#287;', $html);
        $html = str_replace('Ş', '&#380;', $html);
        $html = str_replace('ş', '&#351;', $html);
        $html = str_replace('ı', '&#305;', $html);
        $html = str_replace('€', '&#8364;', $html);
        $html = str_replace('\&#39;', "'", $html);
        $html = str_replace("\'", "'", $html);
        //$html = str_replace('&euro;', 'euro(s)', $html);
        $html = str_replace('&euro;', '€', $html);
        //Codificacion Francia
        $html = str_replace('â‚¬', '€', $html);

        return $html;
    }

    /* 	public function addCenter($html) {
      $html = str_replace('</body>',"<!--</center>-->\n</body>",$html);
      return preg_replace("/<body(.*?)>/si", "<body$1><!--<center>-->", $html);
      } */

    public
    function removeBadQuotes($html)
    {
        $html = str_replace('"/&#39;', '"', $html);
        $html = str_replace('/&#39;"', '"', $html);
        $html = str_replace('"&quot;', '"', $html);
        $html = str_replace('&quot;"', '"', $html);
        $html = str_replace("'&quot;", "'", $html);
        $html = str_replace("&quot;'", "'", $html);
        $html = str_replace("\\'", "'", $html);
        $html = str_replace('\\"', '"', $html);
        $html = str_replace('\&#39;', '&#39;', $html);
        return $html;
    }


    public
    function removeScript($html)
    {
        //var_dump($html);
        $html = preg_replace("/<script[^>]*>.*?<\/script>/", "", $html);
        //preg_match_all("/<script[^>]*>.*?<\/script>/", $html, $script);
        //var_dump($script);
        return $html;
    }

    public
    function postProcessHtml($html, $subject, $pre_header)
    {
// Removes unaccepted table tags.

        $html = preg_replace('/<[^\/]?th/si', '<td', $html);
        $html = preg_replace('/<[\/]{1}\s?th\s?>/si', '</td>', $html);
        $html = preg_replace('/<thea([^>]*?)>/si', '', $html);
        $html = preg_replace('/<\/thea([^>]*?)>/si', '', $html);
        $html = preg_replace('/<tbod([^>]*?)>/si', '', $html);
        $html = preg_replace('/<\/tbod([^>]*?)>/si', '', $html);
        $html = preg_replace('/<tfoo([^>]*?)>/si', '', $html);
        $html = preg_replace('/<\/tfoo([^>]*?)>/si', '', $html);
        //$html = preg_replace('/<title>([^<]*?)<\/title>/si', '<title>' . $subject . '</title>', $html);
        $html = preg_replace("/<!--<li([^<]*?)<\/li>-->/si", "<li$1</li>", $html);


//<td width="15" >&nbsp;</td> ---> <td style="padding:7px;"></td>
        /* $html = preg_replace('/<td.?width.?=.?\d+".?>.?&nbsp;.?<\/td>/si', '<td style="padding:7px;"></td>', $html);*/
// height="numero" --> QUITARLO
        /* SI VIENE CON BACKGROUND, EL HEIGHT HACE DE ESPACIADOR O BORDER */
        //$html = preg_replace('/height=".?"/si', '', $html);

        $cabecera = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>' . $subject . '</title>
        </head><body>';


        //$bodytable_header = '<body>' . $pre_header;
        //DESPUES DE ABRIR EL BODY SE METERA EL PRE-HEADER

        /* CON ESTA BORRA LOS SCRIPT DEL FINAL
         * $html = preg_replace('/<[^\/]*body.*?>/is', $bodytable_header, $html);
         * */

        $html = preg_replace('/.*?<body.*?>/is', $cabecera, $html);

        $html = preg_replace('/<div.*?style="display:none;font-size:1px;color:#FFFFFF;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">.*?<\/div>/is', '', $html);
        $html = preg_replace('/<body.*?>/is', '<body>' . $pre_header, $html);

// AÑADIMOS TR y TD despues del table.
        /*
          preg_match('/<.?table(.*?)>/is', $html, $table);
          $table[0] .= '
          <tr>
          <td>';

          $html = preg_replace('/<.?table(.*?)>/is', $table[0], $html, 1);
         */
        /*
                $pie = '
        </body>
        </html>';
        */
        //$html .= $cabecera .= $html .= $pie;

        return $html;
    }

    function mount_preheader($pre_header)
    {

        $preheader_montado = '<div style="display:none;font-size:1px;color:#FFFFFF;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
' . $pre_header . '
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
</div>';
        return $preheader_montado;
    }

    function tidyClean($html)
    {

        $config = array('output-xhtml' => TRUE,
            'doctype' => 'transitional',
            'drop-proprietary-attributes' => TRUE,
            'preserve-entities' => TRUE,
            'quote-marks' => TRUE,
            'clean' => TRUE);

        $tidy = tidy_parse_string($html, $config, 'ISO 8859-15');
        $tidy->CleanRepair();

        return $tidy;
    }

    public
    function getTextPlain($html)
    {
        $html = strip_tags($html);
        $html = html_entity_decode($html);
        $html = str_replace('\&#39;', "'", $html);
        $html = str_replace("\'", "'", $html);
        $html = str_replace('&euro;', 'euro(s)', $html);
        $html = preg_replace("/[\r\n|\n|\r]+/", PHP_EOL, $html);

        return $html;
    }

// TRABAJAMOS CON LAS URLS!!!!

    public
    function getAllHref($html)
    {

//$pattern = "/href.?=.?\".?([^\s\"]+)/is";
        $pattern = "/[^.-]href=\n*\s*\"([^\"'].{1,})\"/isU";
        preg_match_all($pattern, $html, $matches, PREG_PATTERN_ORDER);

        return array_unique($matches[1]);
    }

    public
    function decodeHrefs($html)
    {

//$pattern = "/href.?=.?\".?([^\s\"]+)/is";
        $pattern = "/[^.-]href=\n*\s*\"([^\"'].{1,})\"/isU";
        preg_match_all($pattern, $html, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[0] as $href) {
            $html = str_replace($href, urldecode($href), $html);
        }
        return $html;
    }

    public
    function check_blacklist_url($url)
    {
        // FUNCIONES WS
// Remove www. from domain (but not from www.com)
        //$url = str_replace("image.", "", $url);
        $url = preg_replace('/^www\.(.+\.)/ix', '$1', $url);
        var_dump($url);
        $blacklists = array(
            'spamhaus' => 'dbl.spamhaus.org',
            'surbl' => 'multi.surbl.org',
            'uribl' => 'black.uribl.com'
        );

        $records = array();
        $result = array();
// Check against each black list, exit if blacklisted


        foreach ($blacklists as $blname => $blacklist) {
            $auxiliar = array(
                'blacklisted' => FALSE,
                'delist' => "",
                'name_blacklist' => "",
            );
            $domain = $url . '.' . $blacklist . '.';
            $records = @dns_get_record($domain, DNS_A);
            //var_dump($domain);
            //var_dump($records);

            foreach ($records as $record) {

                if ($record["type"] == "A") {
                    if ($record["ip"] == "127.0.1.2" || $record["ip"] == "127.0.0.2") {
                        $auxiliar["blacklisted"] = TRUE;
                    } else {
                        $auxiliar["blacklisted"] = FALSE;
                        $auxiliar["delist"] = "";
                    }
                }
                if ($record["type"] == "TXT") {
                    $auxiliar["delist"] = $record["txt"];
                }
            }
            $auxiliar["name_blacklist"] = $blname;

            array_push($result, $auxiliar);
        }

// All clear, probably not spam
        return $result;
    }

    public
    function getDomain($url)
    {

        $url = parse_url($url);

        isset($url['host']) ? preg_match('/[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $url['host'], $m) : null;
        isset($m[0]) ? $url["domain"] = $m[0] : $url["domain"] = substr($url["domain"], strpos($url["domain"], ".") + 1, strlen($url["domain"]));
        //$url["domain"] = $url["host"];
        //$url["domain"] = substr($url["domain"], strpos($url["domain"], ".") + 1, strlen($url["domain"]));

        return $url;
    }

    public
    function removeSpaceSrc($html)
    {
//ELIMINADOR DE ESPACIOS DE LAS IMAGENES
        $pattern = "/src.?=\".*\"/isU";
        preg_match_all($pattern, $html, $srcs, PREG_PATTERN_ORDER);
        foreach ($srcs as $src) {
            $src_original = $src;
            $src = str_replace(" ", "%20", $src);
            $html = str_replace($src_original, $src, $html);
        }
        return $html;
    }

    public
    function getAllSrc($html)
    {

//$pattern = "/src.?=.?\".?([^\s\"]+)/is";
//$pattern = "/src.?=\"(.*)\"/isU";
        $pattern = "/(src.?=\s{0,}\".*)\"/isU";
//REEMPLAZAR LOS ESPACIOS EN EL HTML
        preg_match_all($pattern, $html, $matches, PREG_PATTERN_ORDER);
//FALLO CUANDO EN EL SRC HAY NOMBRES DE ARCHIVOS CON ESPACIOS
//ELIMINAR ESPACIOS SRC
//var_dump($matches[1]);
        return $matches[1];
    }

    public
    function checkSrc($srcs)
    {
//var_dump($srcs);
        $result = array(
            array(
                "type" => "",
                "src" => "",
                "size" => array(
                    "width" => "",
                    "heigth" => ""
                ),
                "domain" => "",
                "scheme" => ""
            )
        );
        $arrContextOptions = array(
            'socket' => array(
                'bindto' => '0:0'
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ));
        $config = stream_context_create($arrContextOptions);
        //timeout => 10checked

        foreach ($srcs as $key => $src) {
            preg_match("~(http.?)://(.+)~is", $src, $link);
            /*
              $src_pass = "http://" . $link[2];
              echo "XXXXXX " . $src_pass . "\n";
              $img = $this->resize_image($src_pass, 300, 100);
             *
             */
//SI NO LO PUEDE COGER CON HTTPS, LO COGEMOS POR HTTP
//var_dump($link);
            /*
             * COMPROBAMOS EL TIPO DE IMAGEN
             * SI ES PNG, HAY QUE RECREARLA CON OTRA FUNCION
             */
            /*
                        $file_info = new finfo(FILEINFO_MIME_TYPE);
                        $mime_type = $file_info->buffer(file_get_contents($link[0], false, stream_context_create($arrContextOptions)));
                        */

            $tiempo_inicial = microtime(true);
            $im = file_get_contents_curl($link[0]);
            //$im = file_get_contents($link[0], true, $config);


            /* SVG IMAGE -> El imagecreatefromstring no sirve para SVG */

            $imagen = imagecreatefromstring($im);
            $width = imagesx($imagen);
            $heigth = imagesy($imagen);
            $tiempo_final = microtime(true);
            $tiempo = $tiempo_final - $tiempo_inicial;
            echo "El tiempo de ejecución del file_get_contents de checkImages ha sido de " . $tiempo . " segundos</br>";

            $result[$key]["size"]["width"] = $width;
            $result[$key]["size"]["heigth"] = $heigth;


            if ($width == null || $heigth == null) {
                $result[$key]["type"] = "error";
            } elseif ($width < 10 && $heigth < 10) {
                $result[$key]["type"] = "pixel";
            } else {
                $result[$key]["type"] = "img";
            }
            $result[$key]["src"] = $link[0];
            /*
              if (!$src_details = getimagesize($imagen)) {
              $src_details = getimagesize($imagen);
              }
             */
            /*
              if (!$src_details = getimagesize($link[1] . "://" . $link[2])) {
              $src_details = getimagesize("http://" . $link[2]);
              }
             *
             */
            /*
              if (!$img = $this->resize_image($src_pass, 300, 100)) {
              $src_pass = "http://" . $link[2];
              $src_details = getimagesize($link[1] . "://" . $link[2]);
              $img = $this->resize_image($src_pass, 300, 100);
              //$src_details = getimagesize($link[1] . "://" . $link[2]);
              } else {
              //$imagen = file_get_contents($link[1] . "://" . $link[2], false, stream_context_create($stream_opts));
              //$src_details = getimagesize("http://" . $link[2]);
              //$save = file_put_contents("http://img.deliverability.es/1517158994/imagenpruebasubida.png", $imagen);
              }

              $imagen = file_get_contents($img);

              $src_details = getimagesize($imagen);
             */
// comprobar tamaño de la imagen $link
//var_dump($src_details);

            /*
             * LOCALIZAR LOS WIDTH E HEIGHT QUE TIENEN ESTAS IMAGENES EN SU ETIQUETA IMG
             * CAMBIAR AMBOS ATRIBUTOS Y

              preg_match("/width.?=.?\"[1|2|3|4|5|6|7|8|9]\"/is", $src_details[3], $width);
              //preg_match("/width.?=.?\".?1.?\"/is", $src_details[3], $width);
              preg_match("/height.?=.?\"[1|2|3|4|5|6|7|8|9]\"/is", $src_details[3], $height);
              //preg_match("/height.?=.?\".?1.?\"/is", $src_details[3], $height);
              //LOS PIXELES NO SIEMPRE TIENE DIMESION DE 1X1
              if (!empty($width) || !empty($height)) {
              $result[$key]["type"] = "pixel";
              } elseif (!empty($src_details)) {
              $result[$key]["type"] = "img";
              } else {
              $result[$key]["type"] = "error";
              }


              if (!empty($src_details[3])) {
              $result[$key]["size"] = $src_details[3];
              } else {
              $result[$key]["size"] = "error";
              }
              $result[$key]["src"] = $link[0];
             */
//var_dump($link[0]);
//COMPROBAMOS SI ESTA BLACKLISTADA
            $url_aux = $this->getDomain($link[0]);
            $result[$key]["host"] = $url_aux["host"];
            $result[$key]["path"] = $url_aux["path"];
            $result[$key]["domain"] = $url_aux["domain"];
            $result[$key]["scheme"] = $url_aux["scheme"];
            //$result[$key]["blacklist"] = $this->check_blacklist_url($result[$key]["domain"]);

        }


        return $result;
    }

    public
    function corregir_botones($html)
    {
//PODRIA METERLE AL PATTERN AL PRINCIPIO QUE EMPEZASE EN TD Y TUVIESE LO QUE FUESE ENTRE TD Y a
        $pattern = '/<a.*(style=".*(?:(?:background\-color)|(?:padding)|(?:border)).*").*>.*<\/a>/U';
        preg_match_all($pattern, $html, $botones, PREG_SET_ORDER);

        foreach ($botones as $boton) {
            /*
             * PARA CADA <a> COGEMOS SU STYLE Y LO GUARDAMOS EN UNA VARIABLE
             * ELIMINAMOS TODO MENOS COLOR Y TEXT-DECORATION PREG_REPLACE Y LO DEJAMOS EN VARIABLE
             * CREAMOS SU NUEVO STYLE CON LAS VARIABLES DE COLOR Y TEXT-DECORATION
             * REEMPLAZAMOS EL <a> POR LA ESTRUCTURA TABLE DEL BOTON
             */
            $boton_original = $boton[0];
            $style = $boton[1];
            preg_match('/[^-](color:.*;)/U', $style, $color);
            $style = str_replace($color[1], '', $style);
            preg_match('/(text-decoration:.*;)/U', $style, $text_decoration);
            $style = str_replace($text_decoration[1], '', $style);
            $style_a = 'style="' . $color[1] . $text_decoration[1] . '"';
            $boton[0] = str_replace($boton[1], $style_a, $boton[0]);
            $new_boton = '<table cellpadding="0" cellspacing="0">
     <tr>
       <td ' . $style . '>' . $boton[0] . '</td>
     </tr>
   </table>';
            $html = str_replace($boton_original, $new_boton, $html);
        }

        return $html;
    }

    public
    function corregir_columns($html)
    {

        $pattern = '~(<td.*>\s*)((<table.*>\s*.*</table>\s*)*?)\s*</td>~siU';
        preg_match_all($pattern, $html, $matchs, PREG_SET_ORDER);
        //COINCIDENCIA[0][0],COINCIDENCIA[1][0],COINCIDENCIA[2][0]
        //var_dump($matchs);
        $i = 0;

        foreach ($matchs as $match) {

//problem? SACA TODAS LAS QUE ESTAN SEGUIDAS SIEMPRE QUE ESTEN DENTRO DE UN TD
            if ($match[2] != $match[3]) {
                echo "XXXX NO COINCIDEN" . "\n";
                //var_dump($match[2]);
                //var_dump($match[3]);

            }

        }
        die();
        return true;
    }

    /*
            foreach ($matchs as $match) {
                var_dump($match);
                if ($match[2] != $match[3]) {
                    echo "XXX SON DISTINTOS";
                }
                break;
            }
    */


    public
    function corregir_columns_original($html)
    {
        //BUSCAR CON EXP REG TODAS LAS TABLAS CUYO WIDTH SEA MENOR AL WIDTH TOTAL
        //MIRAR SI ESAS TABLE TIENEN UN HERMANO QUE SEA TABLE Y ADEMAS SU WIDTH SEA MENOR AL WIDTH TOTAL

        $pattern = "/width=\"([0-9]{3})\"/";
        preg_match_all($pattern, $html, $maximos);

        $width_max = max($maximos[1]);
        //var_dump($width_max);
        $doc = new DOMDocument();
        //$internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        //libxml_use_internal_errors($internalErrors);
        $xpath = new DomXPath($doc);

        $nodes_tables = $xpath->query("//td/table[@width < 600]");
        // PODRIA BUSCAR TODAS LAS TABLE CON PREG_MATCH, GUARDARME SU WIDTH Y SI ES MENOR AL TOTAL MANTENERLA EN EL ARRAY, SINO SACARLA
        // $nodes_tables = $xpath->query("//table/following-sibling::table");
        /* BUSCAR TODAS LAS TABLES */
        /* FOREACH DE LAS TABLES, COMPROBAR SI SU NEXTSIBING ES TABLE */
        /* SI EL NEXTSIBING ES TABLE, BUSCAR EN EL HTML Y METER EL TD */


        //var_dump($nodes_tables);
        foreach ($nodes_tables as $node) {
//LO SACA DOS VECES PORQUE ENCUENTRA LA MISMA TABLA DOS VECES AL TENER MAS DE 2 COLUMNAS
            //var_dump($node);
            $node->parentNode->childNodes;
            //var_dump($node->parentNode->childNodes);
            foreach ($node->parentNode->childNodes as $hijos) {
                foreach ($hijos->parentNode->childNodes as $hijo) {

                    $hijo->parentNode->setAttribute("class", "columna");
                }

                //$hijo->parentNode->setAttribute("class", "columna");

            }
            /* original que le mete la padre la clase */
            //$node->parentNode->setAttribute("class", "columna");
//var_dump($fathers);


            //$td = $doc->createElement('td','');
            //$doc->appendChild($father);
            //var_dump($td);
            //var_dump($father->nextSibling->nodeName);
            //$node->insertBefore($new_td, $node);
            //var_dump($node);
            //var_dump($node->nextSibling);
            //var_dump($node->previousSibling);
        }


//DEJA SOLO LOS HIJOS QUE DEBE BORRAR
//$html1 = trim($tmp_dom->saveHTML());
//var_dump($doc->saveHTML());
        return $doc->saveHTML();

    }

    public
    function checkDimensionSrc($srcs, $html)
    {
        /*
         * CONSEGUIR LOS WIDTHS QUE TENGAN LAS <IMG>
         * SUSTITUIRLOS EN EL SIZE DEL ARRAY srcs
         */

        /*
         * PONER COMO TAMAÑO MAXIMO EL WIDTH DE LA PRIMERA TABLE QUE ES NUMERICO
         * BUSCAR EL MAS ALTO EN WIDTH="" DE TODO EL DOCUMENTO?
         */

        $pattern = "/width=\"([0-9]{3})\"/";
        preg_match_all($pattern, $html, $maximos);

        $width_max = max($maximos[1]);
        $width_resultado = 0;
        $height_resultado = false;

        foreach ($srcs as $key => $src) {

            if ($src["type"] == "img") {

//$src["src"] = str_replace("%20", " ", $src["src"]);
//var_dump($src["src"]);
                //SI LAS ETIQUETAS DE IMAGEN ESTAN SEGUIDAS, COGE LAS DOS CON EL PREG_MATCH
                //O METERLE SALTO DE LINEA, O BUSCAR NUEVA EXPRESION REGULAR

                preg_match('~<img.+' . preg_quote($src["src"]) . '.*?>~ixs', $html, $imagenes);
//var_dump($imagenes);
                /* POR DEFECTO, SI NO ENCUENTRA WIDTHS EN IMG, EL TAMAÑO SERA MAXIMO WIDTH ENCONTRADO EN LAS TABLE DEL HTML */

                if (preg_match('~[^max-|min-]width="\s{0,}[0-9]{1,3}[%|px]{0,2}"~', $imagenes[0], $width) && !strpos($width[0], "100%") != 0 && $width[1] <= $width_max) {

                    $width_resultado = intval(preg_replace('/[^0-9]+/', '', $width[0]), 10);
                    if (strpos($width[0], "%")) {

                        $width_resultado = $width_resultado * $src["size"]["width"] / 100;
                    }

                } else {

                    if (preg_match('~[^max-|min-]width:\s{0,}[0-9]{1,3}.[px|%][;|"]{1}~', $imagenes[0], $width) && !strpos($width[0], "100%") != 0 && $width[1] <= $width_max) {

                        $width_resultado = intval(preg_replace('/[^0-9]+/', '', $width[0]), 10);

                        if (strpos($width[0], "%")) {

                            $width_resultado = $width_resultado * $src["size"]["width"] / 100;
                        }

                    } else {

                        if (preg_match('~max-width:\s{0,}([0-9]{1,3}).[px|%][;|"]{1}~', $imagenes[0], $width) && !strpos($width[0], "100%") != 0 && $width[1] <= $width_max) {


                            $width_resultado = intval(preg_replace('/[^0-9]+/', '', $width[0]), 10);
                            if (strpos($width[0], "%")) {
                                $width_resultado = $width_resultado * $src["size"]["width"] / 100;
                            }
                        }
                    }
                }


                if (preg_match('~[^max-|min-]height="\s{0,}[0-9]{1,4}[%|px]{0,2}"~', $imagenes[0], $height) && !strpos($height[0], "100%") != 0) {

                    $height_resultado = intval(preg_replace('/[^0-9]+/', '', $height[0]), 10);
                    if (strpos($height[0], "%")) {

                        $height_resultado = $height_resultado * $src["size"]["heigth"] / 100;
                    }
                } elseif (preg_match('~[^max-|min-]height:\s{0,}[0-9]{1,4}.[px|%][;|"]{1}~', $imagenes[0], $height) && !strpos($height[0], "100%") != 0) {

                    $height_resultado = intval(preg_replace('/[^0-9]+/', '', $height[0]), 10);
                    if (strpos($height[0], "%")) {

                        $height_resultado = $height_resultado * $src["size"]["heigth"] / 100;
                    }
                } elseif (preg_match('~max-height:\s{0,}[0-9]{1,4}.[px|%][;|"]{1}~', $imagenes[0], $height) && !strpos($height[0], "100%") != 0) {

                    $height_resultado = intval(preg_replace('/[^0-9]+/', '', $height[0]), 10);
                    if (strpos($height[0], "%")) {

                        $height_resultado = $height_resultado * $src["size"]["heigth"] / 100;
                    }
                }
                if ($width_resultado > $width_max || $width_resultado == 0) {
                    $width_resultado = $src["size"]["width"];
                }

                $srcs[$key]["new_width"] = $width_resultado;
                $srcs[$key]["new_height"] = $height_resultado;
                $width_resultado = 0;

//$src["new_width"] = $width_resultado;
//$src["new_height"] = $height_resultado;
//preg_match_all('~width:\s{0,}[0-9]{1,4}.[px|%][;|"]{1}|width="\s{0,}[0-9]{1,4}[%|px]{0,2}"|max-width:\s{0,}[0-9]{1,4}.[px|%][;|"]{1}~', $imagenes[0], $widths);
//var_dump($widths);
                $html = str_replace($imagenes[0],"",$html);
                unset($imagenes);
                $imagenes = array();
            }

        }


        return $srcs;
    }

    public
    function alojarIMG($srcs)
    {
//ESTO HACE QUE NOS SALTEMOS EL SSL CERTIFICADO AL HACER EL file_get_contents (SECURITY HOLE)
        $arrContextOptions = array(
            'socket' => array(
                'bindto' => '0:0'
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ));
        $datenowUnix = time();
        $tiempo_inicial = microtime(true);
        foreach ($srcs as $key => $src) {
            if ($src["type"] == "img") {
                //var_dump($src);
// COMPROBAR QUE ES UNA IMAGEN ANTES DE NADA!!!!!!
//$imagen = file_get_contents($src["src"]);
//$imagen = imagecreatefromstring($imagen);
                $imagen = file_get_contents_curl($src["src"]);
                //$imagen = file_get_contents($src["src"], false, stream_context_create($arrContextOptions));


//var_dump($src["src"]);
// nombre de la imagen
//var_export($_FILES);
                $pathimg = pathinfo($src["src"]);

//var_dump($pathimg);
//resize_image($pathimg["basename"], 100, 100);
// crear carpeta si no existe
                $nueva_ruta = '/var/www/imagenes/' . $datenowUnix . '/';
                if (!is_dir($nueva_ruta)) {
// dir doesn't exist, make it
                    mkdir($nueva_ruta, 0777, TRUE);
                }
                $pathimg["basename"] = preg_replace("/(.*\.(png|jpg|gif|jpeg))(\?.*)/", "$1", $pathimg["basename"]);

//QUITARLE SI TUVIESE LO QUE HAYA DESPUES DEL ?
                $pathimg["basename"] = str_replace("%20", " ", $pathimg["basename"]);
                file_put_contents($nueva_ruta . $pathimg["basename"], $imagen);
                $srcs[$key]["src_ruta_alojada"] = $datenowUnix . '/' . $pathimg["basename"];
                $srcs[$key]["result"] = "OK";

                /*
                 * HACEMOS RESIZE DE LAS IMAGENES QUE YA ESTAN EN EL SERVER
                 */


                //ATENCION: COMPROBAR SI ES GIF, SI LO ES NO REDIMENSIONAR LA IMAGEN PORQUE PIERDE LA ANIMACION
                if ($srcs[$key]["new_width"] && $srcs[$key]["new_height"]) {

                    $this->resize_image("/var/www/imagenes/" . $srcs[$key]["src_ruta_alojada"], $srcs[$key]["new_width"], $srcs[$key]["new_height"]);
                } elseif ($srcs[$key]["new_width"] && $srcs[$key]["new_height"] == false) {

                    $this->resize_image("/var/www/imagenes/" . $srcs[$key]["src_ruta_alojada"], $srcs[$key]["new_width"]);
                } else {

                    $details = getimagesize("/var/www/imagenes/" . $srcs[$key]["src_ruta_alojada"]);
                    $this->resize_image("/var/www/imagenes/" . $srcs[$key]["src_ruta_alojada"], $details[0], $details[1]);
                }
            }
        }
        $tiempo_final = microtime(true);
        $tiempo = $tiempo_final - $tiempo_inicial;
        echo "El tiempo de ejecución de alojarIMG ha sido de " . $tiempo . " segundos";
        return $srcs;
    }

    public
    function resize_image($file, $w, $h = 0, $crop = FALSE)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        list($width, $height) = getimagesize($file);
        if ($h == 0) {
            $h = $height;
        }
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            } else {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w / $h > $r) {
                $newwidth = $w;
                //$newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
        }

        //var_dump($extension);
        switch ($extension) {

            case 'gif':
                $src = imagecreatefromgif($file);
                $dst = imagecreatetruecolor($newwidth, $newheight);
                break;
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($file);
                $dst = imagecreatetruecolor($newwidth, $newheight);
                break;
            case 'png':
                $src = imagecreatefrompng($file);
                $dst = imagecreatetruecolor($newwidth, $newheight);
                imagealphablending($dst, true);
                imagesavealpha($dst, true);
                $bgcolor = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefill($dst, 0, 0, $bgcolor);
                break;
        }


        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        switch ($extension) {

            case 'gif':
                imagegif($dst, $file);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($dst, $file);
                break;
            case 'png':
                imagepng($dst, $file);
                break;
        }
        return $dst;
    }

    public
    function replace_custom_domain($dominio, $html)
    {
        return $html = str_replace("{{DOMAIN}}", $dominio, $html);
    }

    public
    function reemplazarIMG($srcs, $dominio, $html)
    {
// CUSTOM !!!
//var_dump($srcs);

        foreach ($srcs as $key => $src) {
            if ($src["type"] == "img") {
//CUSTOM INTERNA --> SERA REMPLAZA POR EL DOMINIO FINAL . {{DOMAIN}}
//REEMPLAZAR EL MATCH POR http
//var_dump($src);
                $html = str_replace($src["src"], "https://image.emaker.es/" . $src["src_ruta_alojada"], $html);
                //$html = str_replace($src["src"], "https://{{IMAGE.DOMAIN}}/" . $src["src_ruta_alojada"], $html);
            }
// ELIMINADO PORQUE AL ESTAR VACIA LA SRC, EL PATTERN BORRABA TODA LA CABECERA HTML
            /*
              elseif ($src["type"] == "error") {
              // si la imagen ha dado error porque no carga , eliminamos todo. ( Un poco brusco tal vez? Se borran pixels fake u olvidados.)
              //var_dump($src);
              $pattern = '/<[^<]+' . str_replace('/', '\/', $src["src"]) . '[^>]+>/si';
              //var_dump($pattern);
              $html = preg_replace($pattern, '', $html);
              $srcs[$key]["result"] = "Removed";
              }
             *
             */

        }
        //CORTARLAS ESTA JODIENDO LOS HREF
        if (isset($_POST["cut_images"]) && $_POST["cut_images"] == 1) {
            $html = $this->correctImages($html);

        }
        
//ACTIVAR SOLO PARA EMAKER NUEVO
        $html = str_replace("https://image.emaker.es/", "https://{{IMAGE.DOMAIN}}/", $html);

        return $html;
    }

    static public function string_compare($str_a, $str_b)
    {
        $length = strlen($str_a);
        $length_b = strlen($str_b);

        $i = 0;
        $segmentcount = 0;
        $segmentsinfo = array();
        $segment = '';
        while ($i < $length) {
            $char = substr($str_a, $i, 1);
            if (strpos($str_b, $char) !== FALSE) {
                $segment = $segment . $char;
                if (strpos($str_b, $segment) !== FALSE) {
                    $segmentpos_a = $i - strlen($segment) + 1;
                    $segmentpos_b = strpos($str_b, $segment);
                    $positiondiff = abs($segmentpos_a - $segmentpos_b);
                    $posfactor = ($length - $positiondiff) / $length_b; // <-- ?
                    $lengthfactor = strlen($segment) / $length;
                    $segmentsinfo[$segmentcount] = array('segment' => $segment, 'score' => ($posfactor * $lengthfactor));
                } else {
                    $segment = '';
                    $i--;
                    $segmentcount++;
                }
            } else {
                $segment = '';
                $segmentcount++;
            }
            $i++;
        }

// PHP 5.3 lambda in array_map
        $totalscore = array_sum(array_map(function ($v) {
            return $v['score'];
        }, $segmentsinfo));
        return $totalscore;
    }

    public function extraer_customs($hrefs)
    {
        $variable = array();
        foreach ($hrefs as $href) {
            array_push($variable, parse_url($href, PHP_URL_QUERY));
        }

        //$href = explode("&",$href);
        return $variable;
    }

    public function compareHrefs($hrefs)
    {
        $max = 0;
        sort($hrefs);
        $datos = array();
        $j = 0;

        $hrefs_sin = array();
        foreach ($hrefs as $href) {

            preg_match('~(http[s]{0,1}://)(.+)~', $href, $href_sin);

            array_push($hrefs_sin, $href_sin[2]);

        }

        rsort($hrefs_sin, SORT_STRING);

        $count = 0;


        foreach ($hrefs_sin as $href) {
            $href_principal = $hrefs_sin[0];
            $porcentaje_comparador = 0.90;

            if (strlen($href) > $max) {
                $href_principal = $href;
                $max = strlen($href_principal);
            }
            if ($href_principal == $hrefs_sin[$count]) {
                $href_principal = $hrefs_sin[$count + 1];
                $porcentaje_comparador = 0.70;
            }
            $count++;

            $pos = array();
            $i = 0;

            if ($this->string_compare($href_principal, $href) > $porcentaje_comparador) {

                while ($i < strlen($href_principal)) {
                    if (@$href[$i] != @$href_principal[$i]) {
                        array_push($pos, $i);
                    }
                    $i++;
                }
                if (!$min = strrpos($href, "&", -(strlen($href) - min($pos)))) {
                    $min = strrpos($href, "?", -(strlen($href) - min($pos)));
                }


                if (min($pos) + 1 == strlen($href_principal)) {
                    $no_coincidencia = substr($href, strrpos($href_principal, "&"), min($pos));
                } else {
                    $no_coincidencia = substr($href_principal, min($pos), max($pos));
                }


                preg_match('~([&|/?|/][a-z_]*=)[0-9a-z_;]*' . preg_quote(strval($no_coincidencia)) . '~U', $href, $variable_get);

                $j++;


                array_push($datos, $no_coincidencia);
                unset($pos);
                unset($no_coincidencia);
            }
        }

        return array_unique($datos);


        /*
        $customs = array();
        foreach ($datos as $variable) {
            echo "LA VARIABLES ES " . $variable . "</br>";
            preg_match('/[&|\?].*=/U', $variable, $custom);

            foreach ($hrefs as $href) {
                echo "EL HREF ES " . $href . "</br>";
                if (preg_match('/' . $custom[0] . '[0-9a-z]{0,}/si', $href, $custom_ind)) {
                    array_push($customs, $custom_ind[0]);
                }
            }
        }

        return array_unique($customs);
        */
    }

    public
    function checkBlackList_domain($domain)
    {
        $blacklist = $this->check_blacklist_url($domain);
        return $blacklist;

    }

    public
    function checkBlackList_rest($html)
    {
        $pattern_src = "/src=\n*\s*\"(.*)\"/isU";
        $pattern_href = "/href=\n*\s*\"(.*)\"/isU";
        preg_match_all($pattern_src, $html, $coincidencias_srcs);
        preg_match_all($pattern_href, $html, $coincidencias_hrefs);


        $enlaces = array();
        foreach ($coincidencias_srcs[1] as $src) {

            $src = $this->getDomain($src);

            array_push($enlaces, $src["host"]);
        }

        foreach ($coincidencias_hrefs[1] as $href) {

            $href = $this->getDomain($href);

            array_push($enlaces, $href["host"]);
        }

        $enlaces = array_unique($enlaces);

        $info_blacklist = array();
        $info_dominio = array(
            'dominio' => '',
            'blacklist' => array()
        );
        $info_black = array(
            'blacklisted' => '',
            'name_blacklist' => ''
        );
        foreach ($enlaces as $dominio) {
            $black = false;

            $blacklist = $this->check_blacklist_url($dominio);


            $info_dominio['dominio'] = $dominio;
            //array_push($info_dominio['dominio'], $dominio);
            //var_dump($info_dominio['dominio']);
            //$info_dominio["blacklist"] = $blacklist;

            foreach ($blacklist as $value) {
                $info_black["blacklisted"] = $value["blacklisted"];
                $info_black["name_blacklist"] = $value["name_blacklist"];
                //array_push($info_black["blacklisted"], $value["blacklisted"]);
                //array_push($info_black["name_blacklist"], $value["name_blacklist"]);

                if ($value["blacklisted"] == true) {
                    array_push($info_dominio["blacklist"], $info_black);
                    $black = true;
                }
                //INTENTAR QUE HAYA

            }
            if ($black == true) {
                array_push($info_blacklist, $info_dominio);
            }

        }


        return $info_blacklist;

    }

    public
    function getLinks($html)
    {
        var_dump($html);
    }

    public
    function partirImagenes($buffer)
    {

        return $buffer;
    }

    public
    function checkHref($hrefs)
    {

        foreach ($hrefs as $key => $href) {

            $href = str_replace('href="', '', $href);
            $aux_url = $this->getDomain($href);
            $new_hrefs[$key]["href"] = $href;
            $new_hrefs[$key]["scheme"] = $aux_url["scheme"];
            $new_hrefs[$key]["domain"] = $aux_url["domain"];
            isset($aux_url["query"]) ? $new_hrefs[$key]["customs"] = $aux_url["query"] : $new_hrefs[$key]["customs"] = "";
            //$new_hrefs[$key]["customs"] = $aux_url["query"];
            isset($aux_url["path"]) ? $new_hrefs[$key]["path"] = $aux_url["path"] : $new_hrefs[$key]["path"] = "";
            $new_hrefs[$key]["host"] = $new_hrefs[$key]["scheme"] . "://" . $aux_url["host"];
            //PARA EL DOMINIO SOLO NECESITO LA $aux_url[domain]

            //$new_hrefs[$key]["blacklist"] = $this->check_blacklist_url($aux_url["domain"]);
            //SI ["blacklisted"] es true entonces esta blacklistada
            //QUE HACEMOS SI ESTA BLACKLIST
            //var_dump($new_hrefs[$key]["blacklist"]);
        }

        return $new_hrefs;
    }

    public
    function reemplazarHREFshorted($hrefs, $html, $customs = "", $domain_shorter)
    {
// CUSTOM !!!

        foreach ($hrefs as $key => $href) {
//CUSTOM INTERNA --> SEra REMPLAZAdo POR EL DOMINIO FINAL . {{DOMAIN}}
//var_dump($href["host"] . $href["path"] . "?" . $href["customs"]);
            //$html = str_replace($href["host"] . $href["path"] . "?" . $href["customs"], "http://go.{{DOMAIN}}/decoder.php?decode=" . $href["short_code"], $html);
            //NO TENDRIA QUE SUSTITUIR EL ENLACE POR EL SHORT_CODE, PERO PARA REGISTRAR LOS LINKS HABRIA QUE PASAR IGUALMENTE POR EL DECODER
            //PASAR ESAS CUSTOMS A LA TABLA DE VARIABLES PARA QUE SHORTEE EL RESTO DEL LINK, Y LAS OTRAS VARIABLES LAS DEJE DESPUES DEL SHORTEO
            if (!empty($href["customs"])) {

                $href["customs"] = "?" . $href["customs"];
                /*
                if (empty($href["new_customs"])) {
                    $href["new_customs"] = $href["customs"];
                } else {
                    $href["new_customs"] = "?" . $href["new_customs"];
                }
*/
            }

            //METER AQUI LAS VARIABLES TAMBIEN, PORQUE ESTA SUSTITUYENDO DESDE EL HTML, Y AHI NO EXISTEN.
            $html = str_replace($href["host"] . $href["path"] . $href["customs"], $href["host"] . $href["path"] . $href["customs"], $html);
            //$html = str_replace($href["host"] . $href["path"] . "?" . $href["customs"], "https://" . $domain_shorter . "/shorter/decoder.php?decode=" . $href["short_code"] . $customs, $html);

        }


        return $html;
    }

}

function file_get_contents_curl($url, $retries = 2)
{
    $ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.82 Safari/537.36';
    $arrContextOptions = array(
        'socket' => array(
            'bindto' => '0:0'
        ),
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ));
    if (extension_loaded('curl') === true) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url); // The URL to fetch. This can also be set when initializing a session with curl_init().
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // The number of seconds to wait while trying to connect.
        curl_setopt($ch, CURLOPT_USERAGENT, $ua); // The contents of the "User-Agent: " header to be used in a HTTP request.
        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE); // To fail silently if the HTTP code returned is greater than or equal to 400.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); // To follow any "Location: " header that the server sends as part of the HTTP header.
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE); // To automatically set the Referer: field in requests where it follows a Location: redirect.
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // The maximum number of seconds to allow cURL functions to execute.
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // The maximum number of redirects
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $result = curl_exec($ch);

        curl_close($ch);
    } else {

        $result = file_get_contents($url, true, stream_context_create($arrContextOptions));
    }

    if (empty($result) === true) {
        $result = false;

        if ($retries >= 1) {
            sleep(1);
            return file_get_contents_curl($url, --$retries);
        }
    }

    return $result;
}

?>

