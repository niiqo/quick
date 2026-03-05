<?php

// GUARDAR SERVICIO ===============================================================================================
if(isset($_POST["guardar-servicio"]))
{
    // INSERTAR TICKET
    $ticket = new Ticket();
    // HANDLE VARIABLES
    // Handle phone number with country code
    if (!empty($_POST["countryCode"])) {
        $cc = ($_POST["countryCode"][0] !== '+') ? '+' . $_POST["countryCode"] : $_POST["countryCode"];
        if (isset($_POST["tel"])) $ticket->telefono = $cc . $_POST["tel"];
    } else {
        if (isset($_POST["tel"])) $ticket->telefono = $_POST["tel"];
    }
    $ticket->servicio = $_POST["deviceType"] ?? null;
    $ticket->documento = $_POST["doc"] ?? null;
    $ticket->local = $_POST["local"] ?? null;
    $ticket->razon = $_POST["razon"] ?? null;
    $ticket->dept = $_POST["dept"] ?? null;
    $ticket->desc = $_POST["motivo"] ?? null;
    $ticket->nombre = $_POST["nombre"] ?? null;
    $ticket->direccion = $_POST["direccion"] ?? null;
    $ticket->cp = $_POST["cp"] ?? null;
    $ticket->email = $_POST["email"] ?? null;
    $ticket->metodo = $_POST["metodo"] ?? null;
    if(!empty($_POST["modelo_select"])) $ticket->nombre_dispositivo = $_POST["modelo_select"] ?? null;
    else $ticket->nombre_dispositivo = $_POST["otroDispositivo"] ?? null;
    $ticket->precio = $_POST["precio"] ?? null;
    $ticket->descuento = $_POST["descuento"] ?? null;
    $ticket->iva = $_POST["iva"] ?? null;
    $ticket->precio_final = $_POST["precio-final"] ?? null;
    $ticket->pagado = $_POST["pagado"] ?? null;
    $ticket->fecha = date("Y-m-d\TH:i");
    $ticket->pin = $_POST["pin"] ?? null;
    $ticket->fallo_reportado = $_POST["fallo_reportado"] ?? null;
    $ticket->tecnico_encargado = null;
    $ticket->motivo_devolucion = null;
    
    if(isset($_POST["parts"])){
        $partes = implode(", ", $_POST["parts"]);
        $ticket->partes = $partes;
    } else {
        $ticket->partes = null;
    }
    
    if(isset($_POST["deviceServiceCosts"]) && is_array($_POST["deviceServiceCosts"])){
        asort($_POST["deviceServiceCosts"], SORT_NUMERIC);
        $deviceServiceCosts = [];
        foreach ($_POST["deviceServiceCosts"] as $key) {
            if (isset($_POST["prices"][$key]) && is_numeric($_POST["prices"][$key])) {
                $deviceServiceCosts[] = $_POST["prices"][$key];
            }
        }
        if (!empty($deviceServiceCosts)) {
            $costes_partes = implode(", ", $deviceServiceCosts);
            $ticket->costes_partes = $costes_partes;
        } else {
            $ticket->costes_partes = null;
        }
    } else {
        $ticket->costes_partes = null;
    }
    
    if (isset($_POST["recurrente"])) {
        $ticket->recurrente = $_POST["n_recurrente"] ?? $_POST["nombre"];
    } else {
        $ticket->recurrente = null;
    }
    $ticket->estado = 0;
    $ticket->garantia = $_POST["garantia"] ?? 0;
    $ticket->avisos = $_POST["avisos"] ?? 0;
    if(!$db->isDuplicate($ticket)){
        $id = $db->insertTicket($ticket);
        
        // SUBIR FOTOS DEL FORM
        if(isset($_FILES['images'])) $db->insertPhotos($id, $_FILES['images']);
    
        // SUBIR FIRMA DEL FORM
        if(!empty($_POST["sign"])) $db->insertSignature($id, $_POST['sign']);
    
        // ENVIAR CORREO AL TERMINAR
        $ticket = $db->fetchId($id);
        if($ticket) $ticket->sendEmail();
        
        $db->logChange($_SESSION["nombre"], "Nuevo ticket (#$id)", $id);
    }
}


// ENVIAR AL CLIENTE ===============================================================================================
if(isset($_GET["enviar"])) {
    $ticket = $db->fetchId($_GET["id"]);
    $ticket->sendEmail();
    $db->logChange($_SESSION["nombre"], "Correo reenviado al cliente (". $ticket->email .")", $_GET["id"]);
}

// DEVOLUCION ===============================================================================================
if(isset($_GET["devolucion"])){
    $ticket = $db->fetchId($_GET["id"]);
    $ticket->estado = 5;
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Estado cambiado a ". $ticket->pasos[$ticket->estado], $_GET["id"]);
}
if(isset($_GET["deshacer"])){
    $ticket = $db->fetchId($_GET["id"]);
    $ticket->estado = 0;
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Devolución cancelada", $_GET["id"]);
}


// GUARDAR FOTOS ===============================================================================================
if(isset($_POST["guardar-fotos"])){
    $ticket = $db->fetchId($_POST["id"]);
    // UPDATE DESCRIPTION
    $ticket->desc = $_POST["desc"];
    $db->updateTicket($ticket);
    // UPDATE PHOTOS
    if(isset($_FILES['images'])) $db->insertPhotos($_POST["id"], $_FILES['images']);
    $db->logChange($_SESSION["nombre"], "Foto(s) Subida(s)", $_POST["id"]);
}

// GUARDAR REPORTE ===============================================================================================
if(isset($_POST["guardar-pdf"])){
    $ticket = $db->fetchId($_POST["id"]);
    // UPDATE PDF
    if(isset($_FILES['pdf'])) $db->insertPDF($_POST["id"], $_FILES['pdf']);
    $db->logChange($_SESSION["nombre"], "Reporte Subido", $_POST["id"]);
}

// GUARDAR REVISION ===============================================================================================
if(isset($_POST["guardar-revision"])){
    $ticket = $db->fetchId($_POST["id"]);
    // UPDATE PDF
    if(isset($_FILES['revision'])) $db->insertRevision($_POST["id"], $_FILES['revision']);
    $db->logChange($_SESSION["nombre"], "Revision Subida", $_POST["id"]);
}

// GUARDAR FIRMA ===============================================================================================
if (isset($_POST["guardar-firma"])) {
    if(isset($_POST["sign"])) $db->insertSignature($_POST["id"], $_POST['sign']);
    $db->logChange($_SESSION["nombre"], "Firma Subida", $_POST["id"]);
}

// CAMBIAR ESTADO GET (FLECHAS) ===============================================================================================
if(isset($_GET["estado"]))
{
    $ticket = $db->fetchId($_GET["id"]);
    $ticket->estado = $_GET["estado"];
    $db->updateTicket($ticket);

    $db->logChange($_SESSION["nombre"], "Cambio de estado a " . $ticket->pasos[$_GET["estado"]], $_GET["id"]);
}
// CAMBIAR ESTADO POST (FORM COBRAR) ===============================================================================================
if(isset($_POST["estado"]))
{
    $ticket = $db->fetchId($_POST["id"]);
    if(isset($_FILES['images'])) $db->insertPhotos($ticket->id, $_FILES['images'], "Final");
    $ticket->estado = $_POST["estado"];
    $ticket->metodo = $_POST["metodo"] ?? null;
    $ticket->fecha_pago = $_POST["fecha_pago"] ?? date("Y-m-d\TH:i");
    $db->updateTicket($ticket);
    $ticket->sendReviewEmail();

    $db->logChange($_SESSION["nombre"], "Ticket #".$_GET['id']." cerrado", $_GET["id"]);
}

// AVISO AL CLIENTE ===============================================================================================
if(isset($_POST["avisado"])){
    $ticket = $db->fetchId($_GET["id"]);
    $ticket->avisos = $_POST["avisado"];
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Cliente avisado (".$_POST["avisado"].")", $_GET["id"]);
}

// EDIT IN PAGE
if (isset($_POST["guardarCliente"]))
{
    $id = $_POST["id"];
    $ticket = $db->fetchId($id);
    if (isset($_POST["nombre"])) $ticket->nombre = $_POST["nombre"];
    if (isset($_POST["documento"])) $ticket->documento = $_POST["documento"];
    if (isset($_POST["email"])) $ticket->email = $_POST["email"];
    if (isset($_POST["direccion"])) $ticket->direccion = $_POST["direccion"];
    if (isset($_POST["cp"])) $ticket->cp = $_POST["cp"];
    if (isset($_POST["telefono"])) $ticket->telefono = $_POST["telefono"];
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Cambio de datos cliente", $id);
    echo '<meta http-equiv="refresh" content="0"/>';
}
// EDIT MOTIVO DEVOLUCION
if (isset($_POST["guardarDev"]))
{
    $id = $_POST["id"];
    $ticket = $db->fetchId($id);
    $ticket->motivo_devolucion = $_POST["motivo_devolucion"];
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Motivo de devolución", $id);
    echo '<meta http-equiv="refresh" content="0"/>';
}
// EDIT PAGADO
if(isset($_POST["guardarPago"]))
{
    $id = $_POST["id"];
    $ticket = $db->fetchId($id);
    $ticket->pagado = $_POST["pagado"];
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Cambio de cantidad pagada", $id);
    echo '<meta http-equiv="refresh" content="0"/>';
}
// EDIT SERVICIO
if (isset($_POST["editar_insumo"]))
{
    $ticket = $db->fetchId($_GET["id"]);
    if (!empty($_POST["dispositivo"])) $ticket->nombre_dispositivo = $_POST["dispositivo"];
    if (!empty($_POST["desc"])) $ticket->desc = $_POST["desc"];
    if (!empty($_POST["desc_tecnico"])) {
        $ticket->desc_tecnico = $_POST["desc_tecnico"];
    }
    if (!empty($_POST["insumo_desc1"])) {
        $stmt = $db->pdo->prepare("DELETE FROM `insumos` WHERE id_orden = :id");
        $stmt->bindParam(":id", $_GET["id"]);
        $stmt->execute();
        $k = 1;
        while (isset($_POST["insumo_desc" . $k]) && $_POST["insumo_desc" . $k] != "") {
            $insumo = new Insumo($_POST["insumo_desc" . $k], $_POST["insumo_precio" . $k], $ticket->local, $_POST["insumo_estado" . $k], $_POST["servicio" . $k], $_GET["id"]);
            $db->insertInsumo($insumo);
            $k++;
        }
    }

    $db->updateTicket($ticket);
    if (isUser(["tecnico", "jefetecnico"])) {
        $stmt = $db->pdo->prepare("UPDATE `info_orden` SET `tecnico_encargado` = :tecnico WHERE `id` = :id");
        $stmt->bindParam(":tecnico", $_SESSION["nombre"]);
        $stmt->bindParam(":id", $ticket->id);
        $stmt->execute();
    }
    $db->logChange($_SESSION["nombre"], "Cambio de datos servicio", $ticket->id);
    echo '<meta http-equiv="refresh" content="0"/>';
}
// EDIT PRECIO
if (isset($_POST["guardarPrecio"]))
{
    $id = $_POST["id"];
    $ticket = $db->fetchId($id);
    $ticket->precio = $_POST["precio"];
    $ticket->descuento = $_POST["descuento"];
    $ticket->iva = $_POST["iva"];
    $ticket->precio_final = $_POST["precio_final"];
    $db->updateTicket($ticket);
    $db->logChange($_SESSION["nombre"], "Cambio de precio", $id);
    echo '<meta http-equiv="refresh" content="0"/>';
}

// AGREGAR INCIDENCIA ===============================================================================================
if(isset($_POST["agregar-incidencia"])) {
    $id = $_POST["id"] ?? null;
    $incidencia = trim($_POST["incidencia"] ?? '');
    $usuario = $_SESSION["nombre"] ?? null;
    
    if (empty($incidencia)) {
        $_SESSION['error_incidencia'] = "Por favor, complete la descripción de la incidencia.";
    } elseif (empty($id)) {
        $_SESSION['error_incidencia'] = "Error: ID de ticket no válido.";
    } else {
        $resultado = $db->insertIncidencia($id, $incidencia, $usuario);
        if ($resultado !== false) {
            $db->logChange($_SESSION["nombre"], "Incidencia agregada", $id);
            $_SESSION['success_incidencia'] = "Incidencia agregada correctamente.";
            // Redirigir para evitar reenvío del formulario
            header("Location: list&id=" . $id);
            exit();
        } else {
            $_SESSION['error_incidencia'] = "Error al guardar la incidencia. Verifica que la tabla 'incidencias' exista en la base de datos.";
        }
    }
}

/*// ELIMINAR INCIDENCIA ===============================================================================================
if(isset($_GET["eliminar_incidencia"])) {
    $id_incidencia = (int)$_GET["eliminar_incidencia"]; 
    $id_ticket = $_GET["id"];
    
    $resultado = $db->deleteIncidencia($id_incidencia);
    
    if ($resultado) {
        $db->logChange($_SESSION["nombre"], "Incidencia eliminada", $id_ticket);
    }
    
    // IMPORTANTE: Agregamos index.php?view= para que la ruta sea válida
    header("Location: index.php?view=list&id=" . $id_ticket);
    exit();
}*/

// PEDIR INSUMO
if(isset($_POST["pedirInsumo"])) {
    $id = $_POST["id"];
    $estado = 1;
    $db = new Database();
    $pdo = $db->pdo;
    $insumo = $db->fetchInsumoFromId($id);
    $stmt = $pdo->prepare("UPDATE insumos SET estado = :estado WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':estado', $estado);
    try {
        $stmt->execute();
        $db->logChange($_SESSION["nombre"], "Insumo pedido (".$insumo["nombre"].")", $insumo["id_orden"]);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}