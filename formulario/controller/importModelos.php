<?php
// subirModelos.php

require_once "../model/Database.php";

// Database connection
$db =  new Database();
$pdo = $db->pdo;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, "r");

    if ($handle !== FALSE) {
        while (($data = fgetcsv($handle)) !== FALSE) {

            $marca = $data[0];
            $tipo = $data[1];
            $modelo = $data[2];
            $submodelo = $data[3];
            $parte = $data[4];
            $original = $data[5];
            $compatible = $data[6];
            $incel = $data[7];
            if($marca == "") continue;
            if($marca == "Column1") continue;

            // Check if the modelo already exists
            $stmt = $pdo->prepare("SELECT id FROM d_modelo WHERE submodelo = ?");
            $stmt->execute([$submodelo]);
            $modelo_row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($modelo_row) {
                $id_modelo = $modelo_row['id'];
            } else {
                // Insert into the first table
                $stmt = $pdo->prepare("INSERT INTO d_modelo (nombre_marca, nombre_tipo, modelo, submodelo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$marca, $tipo, $modelo, $submodelo]);
                $id_modelo = $pdo->lastInsertId();
            }

            // Insert into the second table
            $stmt = $pdo->prepare("INSERT INTO d_parte (nombre, original, compatible, incel, id_modelo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$parte, $original, $compatible, $incel, $id_modelo]);
        }
        fclose($handle);
        exit;
    } else {
        echo "Error opening the file.";
    }
}
?>