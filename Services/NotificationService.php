<?php

declare(strict_types=1);

namespace Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService
{
    /**
     * Envía un correo electrónico basado en un evento y una plantilla HTML.
     */
    public function sendEmailByEvent(string $event, array $data, array $recipients): bool
    {
        $mail = new PHPMailer(true);

        try {
            // 1. Configuración del Servidor SMTP
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            // Habilitar debug en entorno local
            if (defined('ENV_TYPE') && ENV_TYPE === 'local') {
                $mail->SMTPDebug = 2; // 2 = Mensajes del cliente y del servidor
                $mail->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug [$level]: $str");
                };
            }

            // 2. Destinatarios
            $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
            foreach ($recipients as $email) {
                $mail->addAddress($email);
            }

            // 3. Cargar y Renderizar Plantilla
            $html = $this->renderTemplate($event, $data);

            // 4. Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = $this->getSubjectByEvent($event);
            $mail->Body    = $html;

            $result = $mail->send();

            if (defined('ENV_TYPE') && ENV_TYPE === 'local') {
                error_log("Email enviado exitosamente a: " . implode(', ', $recipients));
            }

            return $result;

        } catch (Exception $e) {
            // Aquí puedes usar tu trait Loggable si lo necesitas
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            error_log("Event: $event, Recipients: " . implode(', ', $recipients));
            return false;
        }
    }

    /**
     * Lee el archivo HTML y reemplaza los placeholders {{variable}} por datos reales.
     * Soporta tanto sintaxis {{variable}} como sintaxis PHP <?= $data['variable'] ?>
     */
    private function renderTemplate(string $event, array $data): string
    {
        $path = __DIR__ . "/../Views/Emails/{$event}.php";
        if (!file_exists($path)) return "Template {$event} no encontrado.";

        // Verificar si el template usa sintaxis PHP
        $content = file_get_contents($path);
        $usesPhpSyntax = str_contains($content, '<?=');

        if ($usesPhpSyntax) {
            // Usar output buffer para procesar PHP
            ob_start();
            $dataCopy = $data; // Crear una copia para usar dentro del template
            extract($dataCopy);
            include $path;
            $html = ob_get_clean();
        } else {
            // Usar reemplazo de placeholders
            $html = $content;
            foreach ($data as $key => $value) {
                $html = str_replace("{{{$key}}}", (string)$value, $html);
            }
        }

        return $html;
    }

    private function getSubjectByEvent(string $event): string
    {
        return match($event) {
            'supplier_onboarding_complete' => 'Acción Requerida: Expediente de Proveedor para Validación',
            'bank_account_approved'        => 'Cuenta Bancaria Autorizada',
            'supplier_new_user'            => 'Bienvenido al Sistema de Proveedores - Credenciales de Acceso',
            default                        => 'Notificación de Sistema MRP'
        };
    }
}