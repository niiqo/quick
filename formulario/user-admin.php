<?php
/**
 * user-admin.php
 * Este archivo se carga mediante include en index.php?pag=user-admin
 */

// 1. CORRECCIÓN DE SEGURIDAD: 
// En index.php guardas $_SESSION["login"] = $row["tipo"] (ej: "administrador")
// Por tanto, la validación debe ser coherente con los roles de tu base de datos.
if (!isset($_SESSION["login"]) || !isUser(["superadmin", "administrador", "director"])) {
    header('Location: index.php');
    exit;
}

// 2. CONEXIÓN:
// No uses require_once "controller/functions.php" aquí si ya está en index.php
// Usamos la instancia de PDO que ya debería estar disponible o la creamos si es necesario
if (!isset($pdo)) {
    $db = new Database();
    $pdo = $db->pdo;
}

// 3. PROCESAR INSERCIÓN DE USUARIO
if (isset($_POST["insertuser"])) {
    $user = $_POST["newuser"];
    $pass = password_hash($_POST["newpass"], PASSWORD_DEFAULT);
    $loc = $_POST["local"];
    $tipo = $_POST["tipo"];

    // Especificamos las columnas para evitar errores con el ID autoincremental
    $stmt = $pdo->prepare("INSERT INTO `user` (username, password, local, tipo) VALUES (:user, :pass, :loc, :tipo)");
    
    try {
        $stmt->execute([
            ':user' => $user,
            ':pass' => $pass,
            ':loc'  => $loc,
            ':tipo' => $tipo
        ]);
        header("Location: index.php?pag=user-admin"); // Redirección limpia
        exit;
    } catch (PDOException $e) {
        // Si tienes la función logError disponible:
        if(function_exists('logError')) logError($e->getMessage());
        else echo "Error: " . $e->getMessage();
    }
}

// 4. PROCESAR CAMBIO DE CONTRASEÑA
if (isset($_POST["changePass"])) {
    $userId = $_POST["userId"];
    $hashedPassword = password_hash($_POST["newPassword"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE `user` SET `password` = :pass WHERE `id` = :userId");
    
    try {
        $stmt->execute([':pass' => $hashedPassword, ':userId' => $userId]);
        header("Location: index.php?pag=user-admin");
        exit;
    } catch (PDOException $e) {
        if(function_exists('logError')) logError($e->getMessage());
    }
}

// 5. CONSULTA PARA LA TABLA
$stmt = $pdo->prepare("SELECT * FROM user");
$stmt->execute();
?>

<div class="container bg-dark p-3 rounded">
    <h4 class="text-light mb-3">Gestión de Usuarios</h4>
    <form action="" method="POST">
        <div class="d-flex gap-2 mb-4">
            <input class="form-control" type="text" name="newuser" placeholder="Nombre usuario" required>
            <input class="form-control" type="password" name="newpass" placeholder="Contraseña" required>
            <select class="form-select" name="local">
                <option value="">(Sin local asignado)</option>
                <option value="Barcelona">Barcelona</option>
                <option value="Travessera">Travessera</option>
                <option value="Mataró">Mataró</option>
            </select>
            <select class="form-select" name="tipo">
                <option value="dependiente">Dependiente</option>
                <option value="tecnico">Técnico</option>
                <option value="administrativo">Administrativo</option>
                <option value="jefetecnico">Jefe Tecnico</option>
                <option value="administrador">Administrador</option>
                <option value="director">Director</option>
            </select>
            <button type="submit" name="insertuser" class="btn btn-info text-white">Añadir</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre usuario</th>
                    <th>Contraseña</th>
                    <th>Local</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row["id"]; ?></td>
                    <td><?php echo htmlspecialchars($row["username"]); ?></td>
                    <td>
                        <?php if(isUser(["superadmin","administrador","director"])): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary change-password-btn"
                                data-bs-toggle="modal" data-bs-target="#passwordModal" 
                                data-user-id="<?php echo $row["id"] ?>">
                                <i class="bi bi-key"></i> Cambiar
                            </button>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row["local"]; ?></td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($row["tipo"]); ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar contraseña: Usuario #<span id="userIdPlaceholder"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="passwordForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="userIdInput" name="userId">
                    <div class="mb-3">
                        <label class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" onclick="togglePassVis('newPassword')" id="toggle1">
                            <label class="form-check-label" for="toggle1"><small>Mostrar</small></label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repetir contraseña</label>
                        <input type="password" class="form-control" id="repeatPassword" required>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" onclick="togglePassVis('repeatPassword')" id="toggle2">
                            <label class="form-check-label" for="toggle2"><small>Mostrar</small></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" name="changePass">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Asignar ID al modal
        const buttons = document.querySelectorAll('.change-password-btn');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                document.getElementById('userIdPlaceholder').textContent = userId;
                document.getElementById('userIdInput').value = userId;
            });
        });

        // Validar contraseñas iguales
        document.getElementById('passwordForm').addEventListener('submit', function(event) {
            const newPassword = document.getElementById('newPassword').value;
            const repeatPassword = document.getElementById('repeatPassword').value;
            if (newPassword !== repeatPassword) {
                alert('Las contraseñas no coinciden');
                event.preventDefault();
            }
        });
    });

    function togglePassVis(id) {
        const x = document.getElementById(id);
        x.type = x.type === "password" ? "text" : "password";
    }
</script>