<?php

declare(strict_types=1);

//namespace Libraries\Core;

use Services\NotificationService;

/**
 * Trait Notifiable
 * Permite a cualquier clase disparar notificaciones por correo 
 * delegando la carga pesada al NotificationService.
 */
trait Notifiable
{
    /**
     * Dispara una notificación de correo electrónico.
     * 
     * @param string $event    Nombre del evento/template (ej: 'supplier_approved')
     * @param array  $data     Datos dinámicos para el cuerpo del correo
     * @param array  $to       Lista de correos destinatarios
     */
    public function sendNotification(string $event, array $data, array $to): void
    {
        // Instanciamos el servicio (puedes usar un Singleton si prefieres)
        $notificationService = new NotificationService();

        // Si tu trait Loggable está presente en la misma clase, lo aprovechamos
        if (method_exists($this, 'logMessage')) {
            $this->logMessage("Disparando notificación: {$event}", \LogLevel::INFO, ['to' => $to]);
        }

        $notificationService->sendEmailByEvent($event, $data, $to);
    }
}