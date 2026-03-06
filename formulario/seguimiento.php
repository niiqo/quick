<?php
require_once "model/db_public.php"; 

$db = new DatabasePublic();
$pdo = $db->pdo;

$ticket = null;
$timeline = [];
$fotos = [];
$reportes = [];
$revisiones = [];
$error = null;
$ticket_id = null;

if (isset($_GET['ticket']) && !empty($_GET['ticket'])) {
    $ticket_id = intval($_GET['ticket']);
    
    try {
        // 1. Datos del Ticket
        $stmt = $pdo->prepare("SELECT * FROM info_orden WHERE id = ? LIMIT 1");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            $ticket['fecha_formateada'] = date('d/m/Y', strtotime($ticket['fecha_ingreso'] ?? 'now'));

            // 2. Historial Combinado (Incidencias + Cambios de Estado)
            $timeline = [];

            // A. Obtener Incidencias (Comentarios)
            $stmt_inc = $pdo->prepare("SELECT fecha, incidencia as descripcion, usuario, 'incidencia' as tipo FROM incidencias WHERE id_orden = ?");
            $stmt_inc->execute([$ticket_id]);
            $res_inc = $stmt_inc->fetchAll(PDO::FETCH_ASSOC);

            // B. Obtener Historial de Cambios (Estados)
            $stmt_hist = $pdo->prepare("SELECT fecha, descripcion, usuario, 'actualizacion' as tipo FROM historial_cambios WHERE id_orden = ?");
            $stmt_hist->execute([$ticket_id]);
            $res_hist = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

            // C. Fusionar y Ordenar
            $timeline = array_merge($res_inc, $res_hist);
            
            // Ordenar por fecha de la más reciente a la más antigua
            usort($timeline, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            // 3. Fotos
            $stmt_fotos = $pdo->prepare("SELECT * FROM foto WHERE id_orden = ? ORDER BY id DESC");
            $stmt_fotos->execute([$ticket_id]);
            $fotos = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);

            // 4. Reportes
            $stmt_rep = $pdo->prepare("SELECT * FROM reportes WHERE id_orden = ? ORDER BY id DESC");
            $stmt_rep->execute([$ticket_id]);
            $reportes = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

            // 5. Revisiones
            $stmt_rev = $pdo->prepare("SELECT * FROM revisiones WHERE id_orden = ? ORDER BY id DESC");
            $stmt_rev->execute([$ticket_id]);
            $revisiones = $stmt_rev->fetchAll(PDO::FETCH_ASSOC);
            
        } else {
            $error = "No se encontró el ticket #" . $ticket_id;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Servicio - QuickTR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .navbar,
        nav.container.rounded.sticky-top,
        nav.navbar-light,
        .dropdown-menu,
        .btn-group,
        .button.btn-dark,
        .logout,
        #announcementsPill,
        .position-relative.btn {
            display: none !important;
        }

        html, body, 
        div.container-main, 
        .container, .container-fluid, 
        .wrapper, .content-wrapper, .main-panel, .page-container {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }

        body { margin-top: 0 !important; padding-top: 0 !important; background: #f8f9fa; }
        .container-main { max-width: 100%; margin: 0 auto; padding: 20px 15px; }
        .search-card, .ticket-card { 
            background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); 
            padding: 2.5rem; margin-bottom: 2.5rem; 
        }
        .logo-container { text-align: center; margin: 2rem 0 3rem; }
        .logo-container img { max-width: 220px; }

        /* Timeline */
        .timeline { position: relative; padding: 1.5rem 0; }
        .timeline::before { 
            content: ''; position: absolute; left: 30px; top: 0; bottom: 0; width: 4px; 
            background: linear-gradient(180deg, #3bbcd7 0%, #764ba2 100%); 
        }
        .timeline-item { position: relative; padding-left: 80px; margin-bottom: 2.5rem; }
        .timeline-item::before { 
            content: ''; position: absolute; left: 22px; top: 8px; width: 20px; height: 20px; 
            border-radius: 50%; background: white; border: 4px solid #3bbcd7; z-index: 1; 
        }
        .timeline-content { 
            background: #f8f9fa; padding: 1.25rem 1.5rem; border-radius: 12px; 
            border-left: 5px solid #3bbcd7; box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
        }
        .timeline-item.incidencia .timeline-content { 
            border-left-color: #dc3545; background: #fff5f5; 
        }

        /* Galería fotos */
        .foto-card img { 
            height: 160px; object-fit: cover; border-radius: 10px; transition: transform 0.2s; 
        }
        .foto-card img:hover { transform: scale(1.03); }
        .foto-card .card-body { padding: 0.75rem; text-align: center; font-size: 0.9rem; }

        /* Listas de documentos */
        .document-item { 
            padding: 1rem; border-radius: 10px; background: #f1f3f5; margin-bottom: 1rem; 
            transition: background 0.2s; 
        }
        .document-item:hover { background: #e9ecef; }

        .status-badge { padding: 0.6rem 1.4rem; border-radius: 50px; font-weight: 600; font-size: 1.1rem; }

        @media (max-width: 576px) {
            .search-card, .ticket-card { padding: 1.5rem; }
            .timeline::before { left: 20px; }
            .timeline-item { padding-left: 60px; }
            .timeline-item::before { left: 12px; }
        }
    </style>
</head>
<body>

<div class="container-main">

    

    <!-- Buscador -->
    <div class="search-card">
        <form method="GET" action="">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-light"><i class="bi bi-ticket-perforated"></i></span>
                <input type="number" class="form-control" name="ticket" 
                       placeholder="Número de ticket (ej: 12345)" 
                       value="<?= htmlspecialchars($ticket_id ?? '') ?>" required min="1">
                <button class="btn btn-primary px-4" type="submit" style="background-color: #3bbcd7; border-color: #3bbcd7;">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
    <div class="ticket-card">
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($ticket): 
        $estados = ["Diagnóstico", "Aprobación", "Reparación", "Terminado", "Entregado", "Devuelto"];
        $colores = ["#cb4351", "#ce9c3b", "#529651", "#4c6ca4", "#4a5467", "#343a40"];
        $estado_actual = (int)$ticket['estado'];
        
    ?>
    
    
            
        
    
<div class="ticket-card">

<div class="d-flex justify-content-between align-items-start flex-wrap gap-4 mb-5">
    <!-- Izquierda -->
    <div>
        <h3 class="mb-3" style="color: #3bbcd7;">
            <i class="bi bi-ticket-perforated text-primary me-2"></i>
            Ticket #<?= htmlspecialchars($ticket['id']) ?>
        </h3>
        <div class="mb-2"><strong>Servicio:</strong> <?= htmlspecialchars($ticket['servicio']) ?></div>
        <div class="mb-2"><strong>Dispositivo:</strong> <?= htmlspecialchars($ticket['nombre_dispositivo']) ?></div>
        <div><strong>Fecha ingreso:</strong> <?= htmlspecialchars($ticket['fecha_formateada']) ?></div>
    </div>

    <!-- Derecha -->
    <div class="text-end">
        <div class="status-badge text-white px-4 py-3 fw-bold fs-5 shadow"
            style="background-color: <?= $colores[$estado_actual] ?>;
                    border-radius: 30px; display: inline-block; min-width: 110px; text-align: center;">
            <?= htmlspecialchars($estados[$estado_actual]) ?>
        </div>
    </div>
</div>



        <div class="progress mb-4" style="height: 25px;">
                    <?php
                    $porcentaje = (($estado_actual + 1) / 5) * 100;
                    ?>
                    <div class="progress-bar" 
                        role="progressbar" 
                        style="width: <?php echo $porcentaje; ?>%; background-color: <?php echo $colores[$estado_actual]; ?>;"
                        aria-valuenow="<?php echo $porcentaje; ?>" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        <?php echo ($estado_actual + 1); ?> / 5
                    </div>
        </div>
        <!-- Fotos -->
        <?php if (!empty($fotos)): ?>
        <h5 class="mb-4 mt-5"><i class="bi bi-images me-2"></i>Fotos del servicio</h5>
        <div class="row g-3">
            <?php foreach ($fotos as $foto): 
                $ruta = 'fotos/' . $foto['archivo'];
                if (!file_exists($ruta)) continue;
            ?>
            <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                <div class="card foto-card shadow-sm border-0 h-100">
                    <a href="<?= $ruta ?>" target="_blank" data-bs-toggle="tooltip" 
                    title="<?= htmlspecialchars($foto['estado'] ?? '') ?> - <?= htmlspecialchars($foto['fecha'] ?? '') ?>">
                        <img src="<?= $ruta ?>" class="card-img-top" alt="Foto del servicio" loading="lazy">
                    </a>
                    <div class="card-body">
                        <div class="small text-center text-muted">
                            <?= htmlspecialchars($foto['estado'] ?? 'Sin estado') ?><br>
                            <span class="text-secondary"><?= htmlspecialchars($foto['fecha'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Reportes -->
        <?php if (!empty($reportes) || !empty($revisiones)): ?>
        <h5 class="mb-4 mt-5"><i class="bi bi-file-earmark-pdf me-2"></i>Diagnostico</h5>
        
        <?php if (!empty($reportes)): ?>
        <div class="mb-4">
            
            <?php foreach ($reportes as $rep): ?>
            <div class="document-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                    <strong><?= htmlspecialchars($rep['archivo']) ?></strong>
                </div>
                <a href="reportes/<?= htmlspecialchars($rep['archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 50px; display: inline-block; min-width: 160px; text-align: center;">
                    <i class="bi bi-file-earmark-pdf"></i> Ver PDF
                </a>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($revisiones)): ?>
        <div>
            <h6 class="text-muted mb-3">Revisiones</h6>
            <?php foreach ($revisiones as $rev): ?>
            <div class="document-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                    <strong><?= htmlspecialchars($rev['fecha']) ?></strong>
                </div>
                <a href="revisiones/<?= htmlspecialchars($rev['archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> Ver PDF
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Timeline -->
        <h5 class="mb-4 mt-5"><i class="bi bi-clock-history me-2"></i>Historial</h5>
        <?php if (!empty($timeline)): ?>
        <div class="timeline">
            <?php foreach ($timeline as $item): 
                $esIncidencia = ($item['tipo'] === 'incidencia');
            ?>
                <?php if ($esIncidencia): ?>
                    <div class="timeline-item incidencia">
                        <div class="timeline-content">
                            <div class="small text-muted mb-2">
                                <?= date('d/m/Y H:i', strtotime($item['fecha'])) ?>
                            </div>
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                                <div>
                                    <strong class="text-danger">Incidencia:</strong>
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($item['descripcion'])) ?></p>
                                    <?php if (!empty($item['usuario'])): ?>
                                        <small class="text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($item['usuario']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-content py-2" style="border-left-color: #3bbcd7; background: #f8f9fa; box-shadow: none; border-bottom: 1px solid #d8d8d8;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-primary" style="font-size: 0.9rem;"></i>
                                    <strong class="text-primary">Actualizacion:</strong>
                                    <span class="text-dark"><?= htmlspecialchars($item['descripcion']) ?></span>
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">
                                    <?= date('d/m/Y H:i', strtotime($item['fecha'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-light border">
            <i class="bi bi-info-circle me-2"></i>Aún no hay movimientos registrados.
        </div>
        <?php endif; ?>

    </div>

    <?php endif; ?>

    <!-- Pie de contacto -->
    <div class="ticket-card text-center py-4">
        <h6 class="mb-3">¿Necesitas ayuda?</h6>
        
        <p class="mb-0"><i class="bi bi-envelope-fill me-2"></i><strong>info@quicktr.es</strong></p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ajuste de altura para iframe (si se usa en WordPress o similar)
function sendHeight() {
    window.parent.postMessage({ height: document.body.scrollHeight }, '*');
}
window.onload = sendHeight;
window.onresize = sendHeight;
</script>
</body>
</html>