<?php
ini_set('display_errors', 1);
ob_start();


require_once("config/conexion.php");
require_once("lib/html_limpieza.php");
require_once("lib/shorter.php");
require_once("php/functions.php");
$miconexion = new DB_mysql;
$carpeta = getdate();
//print_r($carpeta[0]);
//$dominio = $_POST['domains'];
//$codigo = $_POST['codigo'];


function getCountrySigla($id_country)
{
    $miconexion = new DB_mysql;
    $query = "SELECT siglas FROM country WHERE id_country = " . $id_country;
    $result_con = $miconexion->consulta($query);
    $a = $miconexion->GetAllResults();
    return $a[0];
}

function getDomainName($id_domain)
{
    $miconexion = new DB_mysql;
    $query = "SELECT domain FROM domains WHERE id_domain = " . $id_domain;
    $result_con = $miconexion->consulta($query);
    //$a = $miconexion->GetAllResults();
    $a = $result_con->fetch_row()[0];
    return $a;
}

function getDomainShorter($id_domain)
{
    $miconexion = new DB_mysql;
    $query = "SELECT dom_shorted FROM domains WHERE id_domain = " . $id_domain;
    $result_con = $miconexion->consulta($query);
    //$a = $miconexion->GetAllResults();
    $a = $result_con->fetch_row()[0];
    return $a;
}

function remove_utf8_bom($text)
{
    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

//check_words($_POST['name'], $_POST['subject']);

$var_domains = json_decode($_POST['var_domains_template']);
$html_domains = json_decode($_POST['html_domains_template'],true);
$count = 0;
foreach($html_domains as $key => $value){
    if($count == 0){
        $first_domain = $key;
    }
    $html_to_process = $html_domains[$first_domain];
    $count++;
}
$id_domains = $_POST['domains'];
sort($id_domains);
//post body sera el original
if (!empty($_POST['subject']) && !empty($html_domains) && !empty($_POST['domains']) && !empty($_POST['id_country'])) {
    /*$name = $_POST['name'];
    $subject = $_POST['subject'];
    $domain["domain"]s = $_POST['domains'];
    $html = $_POST['body'];
    $prioridad = $_POST['prioridad'];
    $comment = $_POST['comment'];*/
    $country = $_POST['id_country'];
    //recuperar siglas id_country
    //Hacemos Insert sin miramientos.

    if (!empty($_FILES["codigo"]["name"])) {
        $nombrearchivo = $_FILES['codigo']['tmp_name'];

        $contenidoarchivo = file_get_contents($nombrearchivo);
        switch (true) {
            case (substr($contenidoarchivo, 0, 3) == "\xef\xbb\xbf") :
                $contenidoarchivo = substr($contenidoarchivo, 3);
                break;
            case (substr($contenidoarchivo, 0, 2) == "\xfe\xff") :
                $contenidoarchivo = mb_convert_encoding(substr($contenidoarchivo, 2), "UTF-8", "UTF-16BE");
                break;
            case (substr($contenidoarchivo, 0, 2) == "\xff\xfe") :
                $contenidoarchivo = mb_convert_encoding(substr($contenidoarchivo, 2), "UTF-8", "UTF-16LE");
                break;
            case (substr($contenidoarchivo, 0, 4) == "\x00\x00\xfe\xff") :
                $contenidoarchivo = mb_convert_encoding(substr($contenidoarchivo, 4), "UTF-8", "UTF-32BE");
                break;
            case (substr($contenidoarchivo, 0, 4) == "\xff\xfe\x00\x00") :
                $contenidoarchivo = mb_convert_encoding(substr($contenidoarchivo, 4), "UTF-8", "UTF-32LE");
                break;
            default:
                $contenidoarchivo = iconv(mb_detect_encoding($contenidoarchivo, mb_detect_order(), true), "UTF-8", $contenidoarchivo);
        };
    } else if (!empty($html_to_process)) {
        $contenidoarchivo = $html_to_process;
    } else {
        //PASAR ERROR DE QUE NO EXISTE HTML
        header("Location: https://" . $_SERVER['SERVER_NAME'] . "/");
    }
/*
    if ($_FILES["imagen"]["size"][0] > 0) {
       
        $root = "/var/www/imagenes";


        $carpetafinal = $root . "/" . $carpeta[0];

        if (!file_exists($carpeta[0])) {
            //echo "La carpeta no existe\n";
            if (mkdir($carpetafinal, 0777, true)) {
                //echo "Creado con éxito $carpeta[0]\n";
                //echo $carpetafinal;
            } else {
                echo "Ha habido un problema durante la creación de $carpeta[0]\n";
                //echo $carpetafinal;
            }
        } else if (file_exists($carpeta)) {
            echo "La carpeta ya existe\n";
        }

        $count = 0;
        //if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        foreach ($_FILES['imagen']['name'] as $i => $name) {
            if (strlen($_FILES['imagen']['name'][$i]) > 1) {
                if (move_uploaded_file($_FILES['imagen']['tmp_name'][$i], "$carpetafinal/$name")) {
                    $count++;
                }
            }
        }
        // }


        
        $arrayimagenes = $_FILES['imagen']['name'];

        //print_r($arrayimagenes);

        foreach ($arrayimagenes as &$imagen) {

            $imgsrc = 'src="' . $imagen;

            $imgsrcimg = 'src="img/' . $imagen;

            $imgsrcimages = 'src="images/' . $imagen;

            $posicionimg = strpos($contenidoarchivo, $imgsrcimg);

            $posicionimages = strpos($contenidoarchivo, $imgsrcimages);

            $posicionsrc = strpos($contenidoarchivo, $imgsrc);

            if ($posicionimg !== false) {

                $replaceimgsrc = $imgsrcimg;
                echo $replaceimgsrc . '<br>';

            } elseif ($posicionimages !== false) {

                $replaceimgsrc = $imgsrcimages;
                echo $replaceimgsrc . '<br>';

            } elseif ($posicionsrc !== false) {

                $replaceimgsrc = $imgsrc;
                echo $replaceimgsrc . '<br>';

            }
            //MODIFICADO PARA LAS PRUEBAS
            //DEJAR SOLO LA VARIABLE DEL ELSE AL FINALIZARLAS
            //$dominioimagen = 'src="http://' . $domain["domain"] . "/" . $carpeta[0] . "/" . $imagen;
//MODIFICAR EL SRC DE LAS IMAGENES
            $dominioimagen = 'src="https://image.emaker.es/' . $carpeta[0] . '/' . $imagen;

            $abrirarchivo = fopen($nombrearchivo, 'w');
            $contenidoarchivo = str_replace($replaceimgsrc, $dominioimagen, $contenidoarchivo);
            fwrite($abrirarchivo, $contenidoarchivo);
            fclose($abrirarchivo);


        }
    }

    */
    
if($id_domains[0] == "all"){
    $id_domains = array();
    $query_id_domains = "SELECT id_domain FROM domains WHERE status = 'Active' and id_country = " . $country;
    $result_id_domains = $miconexion->consulta($query_id_domains);
    while ($row_id_domains = mysqli_fetch_array($result_id_domains)) {
        array_push($id_domains,$row_id_domains[0]);
    }
}

    $insert_plantilla = "INSERT INTO plantillas (html_original) VALUES('" . $miconexion->real_escape($contenidoarchivo) . "')";
    $miconexion->consulta($insert_plantilla);
    $id_plantilla = $miconexion->ultimo_id();
    
    $insert_data_plantilla = "INSERT INTO data_templates (id_plantilla) VALUES ($id_plantilla)";
    $miconexion->consulta($insert_data_plantilla);

    $tiempo_inicial = microtime(true);
  
    foreach ($id_domains as $id_domain) {

        $kitlimpieza = new herramientas_html();
        $countrySiglas = getCountrySigla($_POST["id_country"]);
        $country = $countrySiglas["siglas"];

        $domain = getDomainName($id_domain);
        $domain_shorter = getDomainShorter($id_domain);
        define('IMG_BASE_DIR', '/var/www/imagenes/');
        define("IMG_BASE_URL", "https://image.emaker.es");

        //ENCONTRAR FORMA DE QUE ENTIENDA QUE EXISTEN IMAGENES O NO


        //$cssToInlineStyles = new CssToInlineStyles();
        $shorter = new shorter();

        $subject = $_POST['subject'];

        $html = $kitlimpieza->removeComments($contenidoarchivo);
        $buffer = $kitlimpieza->split_css_html($html);
//CAMBIAR EL $buffer["html_processed"] DE ABAJO POR EL HTML_DOMAINS[KEY] DEL PRIMER DOMINIO
        $buffer["html_processed"] = $kitlimpieza->removeGenericTags($buffer["html_processed"]);

        if (!isset($_POST["no_process"]) && $_POST["no_process"] != "1") {


            $buffer["html_processed"] = $kitlimpieza->removeCenterTag($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->removeImportantStyle($buffer["html_processed"]);

            //CAMBIA SIMBOLOS EN removeElementsOcultos
            //PROBAR CON LA FUNCION C14N DE DOMDocument para buscarlos de forma manual, eliminarlos con str_replace y luego guardar el documento conservando el html que entra
            $buffer["html_processed"] = $kitlimpieza->removeElementsOcultos($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->removeClasses($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->removeRol($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->removeId($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->removeScript($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->translateAccents($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->correctChars($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->correctStyleTags($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->removeXMLTag($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->removeBadQuotes($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->addcellpadding_cellspadding_tables($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->textoIzquierda($buffer["html_processed"]);
            //$kitlimpieza->localizar_divs($buffer["html_processed"]);
//cambiar_div_por_table es la que genera tantos divs

            /*            
            $buffer["html_processed"] = $kitlimpieza->cambiar_div_por_table($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->change_padding_table($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->corregir_width($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->change_height_td($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->comprobar_width($buffer["html_processed"]);
            $buffer["html_processed"] = $kitlimpieza->alignFirstTable($buffer["html_processed"]);
            */

            $buffer["html_processed"] = $kitlimpieza->removeSpaceSrc($buffer["html_processed"]);
            //$buffer["html_processed"] = $kitlimpieza->tidyClean($buffer["html_processed"]);

            //$buffer["html_processed"] = $kitlimpieza->borrar_etiquetas_vacias($buffer["html_processed"]);
            //$buffer["html_processed"] = $kitlimpieza->mantener_simbolos($buffer["html_processed"]);

        }
        $buffer["text"] = $kitlimpieza->getTextPlain($buffer["html_processed"]);

        $blacklist_rest = $kitlimpieza->checkBlackList_rest($buffer["html_processed"]);
        $blacklist = $kitlimpieza->checkBlackList_domain($domain);
        foreach ($blacklist as $key => $value) {
            //if $value["blacklisted"] == 1, update campo blacklist y campo name_blacklist
            if ($value["blacklisted"] == true) {

                $query = "UPDATE domains SET blacklist = blacklist + 1, " . $value['name_blacklist'] . " = true, status = 'Blacklisted' WHERE id_domain = $id_domain";
                $miconexion->consulta($query);
            }
        }
//PROBLEMA EN LAS COMILLAS QUE SE QUITARON DEL CUTIMAGE AL HACER LA SUSTITUCION DEL HREF
//function cutImage LINEAS 577 Y 663

        //SI ESTA POR ENCIMA DEL PROCESAMIENTO DE LAS IMAGENES, NO COGE EL WIDTH

        if (!isset($_POST["no_process"]) && $_POST["no_process"] != "1") {

            $buffer["html_processed"] = $kitlimpieza->htmlFormat($buffer["html_processed"]);

            $buffer["html_processed"] = $kitlimpieza->removeStuffs($buffer["html_processed"]);
        }

        if (isset($_POST['pre_header']) && !empty($_POST['pre_header'])) {
            $pre_header = $kitlimpieza->mount_preheader($_POST['pre_header']);
        } else {
            $pre_header = "";
        }

        $buffer["html_processed"] = $kitlimpieza->postProcessHtml($buffer["html_processed"], $subject, $pre_header);
        // PROCESAMIENTO DE LINKS

       
       
        //SI $hrefs SIZE ES DE 1, NO EJECUTAR compareHrefs() y $variable_diff lo pondremos a vacio
        
        /*
        if (sizeof($hrefs) == 1) {
            $variable_diff = "";
        } else {
            $variable_diff = $kitlimpieza->compareHrefs($hrefs);

        }
*/

//METER EN hrefs[customs] las var_domains?


        //reemplazar las urls por las urls shorteadas httpSSSS://{{domain}}/
        //http://go.{{DOMAIN}}/  añadir S

        // INSERT PARA LA TABLA DE CREAS


        $blacklist_rest = json_encode($blacklist_rest);
       
        $query = "INSERT INTO creas(active, subject, status, name, id_country, id_company, id_user, comment, send_date, preheader, blacklist, id_plantilla)
                    VALUES(1, '" . $miconexion->real_escape($_POST['subject']) . "','Done','" . $miconexion->real_escape($_POST['name']) . "','" . $miconexion->real_escape($_POST['id_country']) . "','" . $miconexion->real_escape($_POST['id_company']) . "','" . $miconexion->real_escape($_POST['id_user']) . "','" . $miconexion->real_escape(htmlentities($_POST['comment'])) . "','" . $miconexion->real_escape($_POST['send_date2']) . "','" . $miconexion->real_escape($_POST['pre_header']) . "','" . $miconexion->real_escape($blacklist_rest) . "',$id_plantilla)";
        
        /*
        $query = "INSERT INTO creas(active, subject, html_original, text, status, name, id_country, id_company, id_user, comment, send_date, preheader, blacklist, id_plantilla)
                    VALUES(1, '" . $miconexion->real_escape($_POST['subject']) . "','" . $miconexion->real_escape($contenidoarchivo) . "','" . $miconexion->real_escape($buffer["text"]) . "','Done','" . $miconexion->real_escape($_POST['name']) . "','" . $miconexion->real_escape($_POST['id_country']) . "','" . $miconexion->real_escape($_POST['id_company']) . "','" . $miconexion->real_escape($_POST['id_user']) . "','" . $miconexion->real_escape(htmlentities($_POST['comment'])) . "','" . $miconexion->real_escape($_POST['send_date2']) . "','" . $miconexion->real_escape($_POST['pre_header']) . "','" . $miconexion->real_escape($blacklist_rest) . "',$id_plantilla)";
        */
      
        $result_con = $miconexion->consulta($query);
        //INSERT PARA LA TABLA DE RELACION ENTRE CREA, DOMINIO Y TRACKING


        $id_crea = $miconexion->ultimo_id();


        $query = "INSERT INTO crea_tiene_dominio(id_crea, id_domain, id_plantilla) VALUES(" . $id_crea . ", " . $miconexion->real_escape($id_domain) . ", $id_plantilla)";

        $miconexion->consulta($query);
        
        //die();

        $buffer["html_processed"] = $kitlimpieza->reemplazarHREFshorted($hrefs, $buffer["html_processed"], $customs, $domain_shorter);
        //REEMPLAZAR CUSTOMS IMAGES


        

//HACER UPDATE DE LA CREA PARA METERLE EL TRACKING
        /*
                $update = "UPDATE creas SET html_procesado = '" . $miconexion->real_escape($buffer['html_processed']) . "' WHERE id_crea = $id_crea";

                $miconexion->consulta($update);
        */
        /*
                $miconexion->commit();
                $miconexion->close();
        */

        foreach($html_domains as $key_html => $value_html){
            
            if($key_html == $id_domain){
                $html_domains[$key_html] = $kitlimpieza->removeComments($html_domains[$key_html]);
                $html_domains[$key_html] = $kitlimpieza->removeGenericTags($html_domains[$key_html]);
        //CREACION JSON HREFS
        preg_match_all('/href=[\"|\'](.*)[\"|\']/iU',$html_domains[$key_html],$hrefs);
        $hrefs[1] = array_unique($hrefs[1],SORT_STRING);
        //POR UN LADO LOS BUCLES PARA EL BUFFER Y POR OTRO SOLO SACAR EL ARRAY POR CADA HTML_DOMAINS[KEY]
        $array_hrefs = array();
        foreach($hrefs[1] as $key_href => $value_href){
            $array_hrefs["{{LINK_ID_".$key_href."}}"] = $value_href;
        }
        $array_hrefs = json_encode($array_hrefs);
       
        //CREACION JSON TRACKING
        $html = tidyHTML($html_domains[$key_html]);
        $preg_img = "~<img.*?(?:(?:width=[\"|\']1[px]{0,1}[\"|\']){1}|(?:height=[\"|\']1[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}1px){1}|(?:height:[\s]{0,}1px)|(?:width=[\"|\']0[px]{0,1}[\"|\']){1}|(?:height=[\"|\']0[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}0px){1}|(?:height:[\s]{0,}0px){1}){1,}.*(?:(?:/>)|>)~Ui";
        preg_match_all($preg_img,$html,$imgs);
        $imgs[0] = array_unique($imgs[0],SORT_STRING);
        $array_images = array();
        
        foreach($imgs[0] as $key_img => $value_img){
        
            $array_images["{{IMG_ID_".$key_img."}}"] = $value_img;
        }
      
        $array_images=json_encode($array_images);
        
        

        $insert_info = "UPDATE creas SET tracking = '" . $miconexion->real_escape($array_images) . "',links = '" . $miconexion->real_escape($array_hrefs) . "' WHERE id_crea = $id_crea";
       
        $miconexion->consulta($insert_info);

        $hrefs = $kitlimpieza->getAllHref($html_domains[$key_html]);

        if (isset($_POST['shortear']) && $_POST['shortear'] == "1") {
            $variable_diff = $kitlimpieza->extraer_customs($hrefs);
            $customs = "&";
        }
      

        

        $hrefs = $kitlimpieza->checkHref($hrefs);
        foreach ($var_domains as $key_domain => $var) {
            if ($key_domain == $id_domain) {
//INSERT DE LAS VARIABLES
                $added_var = $var;
                /*
                foreach ($hrefs as $key => $href) {
                    $href_html = $href;
                    $href .= $var;
                    $hrefs[$key] = $href;

                }
                */
            }
        }
        $hrefs = $shorter->lets_short($hrefs, $id_domain, $variable_diff, $id_crea, $added_var);
            }
         
        }
     
    }


  
    preg_match_all('/href=[\"|\'](.*)[\"|\']/iU',$buffer["html_processed"],$hrefs);
    foreach($hrefs[1] as $key => $value){
        $buffer["html_processed"] = str_replace($value, "{{LINK_ID_".$key."}}",$buffer["html_processed"]);
    }
    $buffer["html_processed"] = tidyHTML($buffer["html_processed"]);
    $preg_img = "~<img.*?(?:(?:width=[\"|\']1[px]{0,1}[\"|\']){1}|(?:height=[\"|\']1[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}1px){1}|(?:height:[\s]{0,}1px)|(?:width=[\"|\']0[px]{0,1}[\"|\']){1}|(?:height=[\"|\']0[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}0px){1}|(?:height:[\s]{0,}0px){1}){1,}.*(?:(?:/>)|>)~Ui";
    preg_match_all($preg_img,$buffer["html_processed"],$imgs);
    foreach($imgs[0] as $key => $value){
        
        $buffer["html_processed"] = str_replace($value, "{{IMG_ID_".$key."}}", $buffer["html_processed"]);
    }
    
/*
    $html_to_search = $contenidoarchivo;
    $html_to_search = $kitlimpieza->removeComments($html_to_search);
            $html_to_search = $kitlimpieza->removeGenericTags($html_to_search);
    
    preg_match_all('/href=[\"|\'](.*)[\"|\']/iU',$html_to_search,$hrefs);
    foreach($hrefs[1] as $key => $value){
        $buffer["html_processed"] = str_replace($value, "{{LINK_ID_".$key."}}",$buffer["html_processed"]);
    }
    $html_to_search = tidyHTML($html_to_search);
    $preg_img = "~<img.*?(?:(?:width=[\"|\']1[px]{0,1}[\"|\']){1}|(?:height=[\"|\']1[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}1px){1}|(?:height:[\s]{0,}1px)|(?:width=[\"|\']0[px]{0,1}[\"|\']){1}|(?:height=[\"|\']0[px]{0,1}[\"|\']){1}|(?:width:[\s]{0,}0px){1}|(?:height:[\s]{0,}0px){1}){1,}.*(?:(?:/>)|>)~Ui";
    preg_match_all($preg_img,$html_to_search,$imgs);
    foreach($imgs[0] as $key => $value){
        
        $buffer["html_processed"] = str_replace($value, "{{IMG_ID_".$key."}}", $buffer["html_processed"]);
    }
    var_dump($buffer["html_processed"]);
    */
    //CREAR ARRAYS NUMERICOS PARA CUANDO SOLO TIENEN 1 REGISTRO LOS JSON
    //SINO LOS INTERPRETA COMO STRINGS
    //var_dump($buffer["html_processed"]);
  
    



    $buffer["html_processed"] = $shorter->insertTracking($buffer["html_processed"]);
    //var_dump($buffer["html_processed"]);
    $tiempo_final = microtime(true);
    $tiempo = $tiempo_final - $tiempo_inicial;

    echo "El tiempo de ejecución hasta las imagenes ha sido de " . $tiempo . " segundos";
    //PROCESAMIENTO DE IMAGENES
    $srcs = $kitlimpieza->getAllSrc($buffer["html_processed"]);
    ;

    $tiempo_inicial = microtime(true);
    $srcs = $kitlimpieza->checkSrc($srcs);
    $tiempo_final = microtime(true);
    $tiempo = $tiempo_final - $tiempo_inicial;
    echo "El tiempo de ejecución de checkImages ha sido de " . $tiempo . " segundos";
/*
    if (!isset($_POST["no_process"]) && $_POST["no_process"] != "1") {
        $srcs = $kitlimpieza->checkDimensionSrc($srcs, $buffer["html_processed"]);
        $buffer["html_processed"] = $kitlimpieza->corregir_width_img($buffer["html_processed"]);

    }
*/

$srcs = $kitlimpieza->checkDimensionSrc($srcs, $buffer["html_processed"]);
$buffer["html_processed"] = $kitlimpieza->corregir_width_img($buffer["html_processed"]);
    $srcs = $kitlimpieza->alojarIMG($srcs);
   
    $tiempo_inicial = microtime(true);
    $buffer["html_processed"] = $kitlimpieza->reemplazarIMG($srcs, $domain, $buffer["html_processed"]);

    $tiempo_final = microtime(true);
    $tiempo = $tiempo_final - $tiempo_inicial;
    echo "El tiempo de ejecución de cutImage ha sido de " . $tiempo . " segundos";
    $buffer["html_processed"] = $kitlimpieza->decodeHrefs($buffer["html_processed"]);
    $update_plantilla = "UPDATE plantillas SET html_original = '" . $miconexion->real_escape($contenidoarchivo) . "', html_procesado = '" . $miconexion->real_escape($buffer['html_processed']) . "',text = '" . $miconexion->real_escape($buffer["text"]) . "' WHERE id = $id_plantilla";
    $miconexion->consulta($update_plantilla);
    //var_dump($buffer["html_processed"]);

    header("Location: https://" . $_SERVER['SERVER_NAME'] . "/?c=$country");
    die();

}
ob_end_flush();
?>