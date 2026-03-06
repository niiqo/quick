<?php
// Endpoint AJAX público para obtener datos del ticket
// No requiere autenticación

require_once '../model/Database.php';
header('Content-Type: application/json');

$db = new Database();

if (isset($_GET['ticket_id'])) {
    $ticket_id = intval($_GET['ticket_id']);
    
    // Obtener información básica del ticket
    $ticket = $db->fetchTicketPublico($ticket_id);
    
    if ($ticket) {
        // Obtener timeline completo
        $timeline = $db->fetchTimelineCompleto($ticket_id);
        
        // Preparar respuesta
        $response = [
            'success' => true,
            'ticket' => $ticket,
            'timeline' => $timeline
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Ticket no encontrado'
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'ID de ticket no proporcionado'
    ], JSON_UNESCAPED_UNICODE);
}
?>
