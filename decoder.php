<?php
require_once("../config/conexion.php");

$DB_CONN = new DB_mysql;

// RECUPERAMOS A QUE URL PERTENECE EL CODIGO SHORTEADO
//var_dump($DB_CONN);
//var_dump($_GET["decode"]);
$de = $DB_CONN->real_escape($_GET["decode"]);
//$de= $DB_CONN->escape("42NZU6lGpy6jYqFAPqOrKLHZ4");

if (!empty($_GET["decode"])) {
    $json = json_encode($_SERVER);
//BUSCAR LA ID DE LA CREA RELACIONADA AL SHORTER CODE
//COMPROBAR EL STATUS DE LA CREA
//SI STATUS = 2 BUSCAR LA URL DEL PAIS DE LA CREA
//BUSCAR EL SHORT CODE A TRAVES DE LA ID DE LA TABLA SHORTER QUE TIENE LA URL EN LA TABLA COUNTRY
//EL NUEVO $de SERA EL SACADO DE ESA CONSULTA

    $query_status = "SELECT creas.id_crea, creas.active, creas.id_country, country.url from creas JOIN country ON (creas.id_country = country.id_country) JOIN shorter ON (shorter.id_crea = creas.id_crea) WHERE shorter.short_code='" . $de . "'";

    $result_status = $DB_CONN->consulta($query_status);
    while ($row = $result_status->fetch_assoc()) {
        $status = $row["active"];
        if ($status == "2") {
            $id_shortcode = $row["url"];
            $query_code = "SELECT short_code from shorter where id =$id_shortcode";
            $result_code = $DB_CONN->consulta($query_code);
            while ($row = $result_code->fetch_assoc()) {
                $de = $row["short_code"];
            }
        }

    }


    $sql = "SELECT id,url,custom,added_var FROM shorter WHERE short_code='" . $de . "'";

    $result = $DB_CONN->consulta($sql);
    //si consulta vacia, meter un die

    if ($result->num_rows < 1) {
        die();
    }
    while ($row = $result->fetch_assoc()) {
        !empty($row["id"]) ? $id = $row["id"] : $id = "";
        !empty($row["url"]) ? $url = $row["url"] . "?" : $url = "";
        //$custom = $row["custom"];do
        !empty($row["custom"]) ? $custom = $row["custom"] : $custom = "";
        !empty($row["added_var"]) ? $added_var = $row["added_var"] : $added_var = "";
        //!empty($row["custom"]) ? $custom = $row["custom"] : $custom = "";
        //$id_crea = $row["id_crea"];

    }

    /*
    if ($id_crea == 2) {
        $sql = "SELECT country.siglas,country.url FROM creas NATURAL JOIN country WHERE id_crea ='" . $id_crea . "'";
        $result = $DB_CONN->consulta($sql);
        header("Location: " . $url_redireccion);
        //BASTA CON HACER REDIRECCION A PRELANDING PASANDO LA ID DE LA CREA
    }
    */
// TRACKEAMOS LOS CLICKS .
// GUARDAMOS EN QUE URL ( SHORT CODE ) Han clicado , cuando , y desde dónde.

    $ip = $_SERVER['REMOTE_ADDR'];
    $browser = $_SERVER['HTTP_USER_AGENT'];
    if (!isset($_SERVER['HTTP_REFERER'])) {
        $referrer = "This page was accessed directly";
    } else {
        $referrer = $_SERVER['HTTP_REFERER'];
    }


    $variables_get = "";
    $count_get = 1;
    foreach ($_GET as $nombre_campo => $valor) {
        $nombre_campo = str_replace("?", "", $nombre_campo);
        if ($nombre_campo != "decode") {

            if ($count_get == sizeof($_GET)) {
                $variables_get .= $nombre_campo . "=" . $valor;
            } else {
                $variables_get .= $nombre_campo . "=" . $valor . "&";
            }

        }
        $count_get++;
    }

// ENCONTRAR LAS CUSTOMS QUE SE ESTÁN ENVIANDO !
// Concatenarlas al final de la url .
$del = false;
    $vars = explode("&", $added_var);
    for ($i = 1; $i < sizeof($vars); $i++) {

        $fragment_vars = explode("=", $vars[$i]);
        $customs = explode("&", $custom);
        $count_customs = 0;
        $variables = explode("&", $variables_get);
        $count_variables = 0;
        foreach ($variables as $vars_get) {
            $fragment_var_get = explode("=", $vars_get);
            if ($fragment_var_get[0] == $fragment_vars[0]) {

                $pref = "";
                if ($count_variables > 0) {
                    $pref = "&";
                    $del = true;
                }

                $variables_get = str_replace($pref . $vars_get, $pref .$vars[$i], $variables_get);

            }
            $count_variables++;
        }

        foreach ($customs as $cust) {
            $fragment_cust = explode("=", $cust);


            if ($fragment_cust[0] == $fragment_vars[0]) {

                $pref = "";
                if ($count_customs > 0) {
                    $pref = "&";
                    $del = true;
                }

                $custom = str_replace($pref . $cust, $pref .$vars[$i], $custom);
                //$vars = str_replace($vars[$i], "", $vars);

            }
            $count_customs++;
        }
        if($del==true){
            $added_var = str_replace($vars[$i], "", $added_var);
        }

    }



    $consulta_plantilla = "SELECT id_plantilla FROM creas JOIN shorter ON creas.id_crea = shorter.id_crea WHERE shorter.id = $id";
    $DB_CONN->consulta($consulta_plantilla);
    $result_consulta_plantilla = $DB_CONN->GetAllResults();
    $id_plantilla = $result_consulta_plantilla[0]["id_plantilla"];


    $consulta_exist_plantilla ="SELECT COUNT(id_plantilla) as plantilla FROM data_templates WHERE id_plantilla = $id_plantilla";
    $DB_CONN->consulta($consulta_exist_plantilla);
    $result_consulta_exist_plantilla = $DB_CONN->GetAllResults();
if($result_consulta_exist_plantilla[0]["plantilla"] == 0){
//NO EXISTE REGISTRO TODAVIA
    $insert_data_template = "INSERT INTO data_templates (id_plantilla, click, click_unique) VALUES ($id_plantilla,1,1)";
}else{
    $consulta_clicks = "SELECT
          COUNT(
            id_click_tracking
          ) AS clicks_uniques
        FROM
          click_tracking
          JOIN shorter ON shorter.id = click_tracking.id_url
          JOIN creas ON shorter.id_crea = creas.id_crea
          JOIN plantillas ON creas.id_plantilla = plantillas.id
        WHERE
        JSON_CONTAINS(variables, '{\"REMOTE_ADDR\": \"$ip\"}') AND
          plantillas.id = $id_plantilla";
    $DB_CONN->consulta($consulta_clicks);
    $result_consulta_clicks = $DB_CONN->GetAllResults();

    if($result_consulta_clicks[0]["clicks_uniques"] == 0){
        $click_unique = 1;
    }else{
        $click_unique = 0;
    }
    $insert_data_template = "UPDATE data_templates SET click = click+1, click_unique = click_unique+$click_unique WHERE id_plantilla = $id_plantilla";
    }

    $DB_CONN->consulta($insert_data_template);

    $sql = "INSERT INTO click_tracking (id_url,customs,variables) VALUES (" . $id . ",'" . $DB_CONN->real_escape($custom . $variables_get . $added_var) . "', '" . $DB_CONN->real_escape($json) . "')";
    $DB_CONN->consulta($sql);
    //MONTAMOS LA URL
//$res = $result->row["url"];
//AÑADIR QUE ADEMAS NO ESTE VACIO PARA QUE NO META EL &
    if ($variables_get[0] != "&") {
        $variables_get = "&" . $variables_get;
    }

    $url_redireccion = $url . $custom . $variables_get . $added_var;
    
    while(substr($url_redireccion, -1) == "&"){
        $url_redireccion = substr_replace($url_redireccion ,"",-1);
    }

//echo 	header("location:".$res);
//DESCOMENTAR PARA QUE REDIRECCIONE
//var_dump($url_redireccion);
    header("Location: " . $url_redireccion);

}

?>

