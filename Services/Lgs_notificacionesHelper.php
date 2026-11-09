<?php

class Lgs_notificacionesHelper
{
    /**
     * Resuelve los correos del Aprobador Único (Javier Rodríguez Murrieta / lgs_aprobadores)
     */
    public static function getCorreosAprobadores(PDO $db): array
    {
        try {
            $sql = "
                SELECT DISTINCT u.email_user 
                FROM usuarios u
                LEFT JOIN lgs_aprobadores ap ON ap.id_usuario = u.idusuario AND ap.activo = 1
                LEFT JOIN permisos p ON p.rolid = u.rolid AND p.moduloid = 157 AND (p.u = 1 OR p.w = 1)
                WHERE (ap.id IS NOT NULL OR u.rolid = 56 OR u.idusuario = 72)
                  AND u.status = 1 
                  AND u.email_user IS NOT NULL
            ";
            $stmt = $db->query($sql);
            $correos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return self::filtrarCorreosValidos($correos);
        } catch (Throwable $e) {
            error_log("Error en getCorreosAprobadores: " . $e->getMessage());
            return self::filtrarCorreosValidos([]);
        }
    }

    /**
     * Resuelve los correos del equipo de Finanzas para avisos informativos (Contabilidad, Tesorería, Cobranza)
     * Regla Minuta: Finanzas NO aprueba, solo se le notifica a modo de previsión contable/flujo.
     */
    public static function getCorreosFinanzas(PDO $db): array
    {
        try {
            $sql = "
                SELECT DISTINCT u.email_user 
                FROM usuarios u
                WHERE u.rolid IN (57, 58, 73)
                  AND u.status = 1 
                  AND u.email_user IS NOT NULL
            ";
            $stmt = $db->query($sql);
            $correos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return self::filtrarCorreosValidos($correos);
        } catch (Throwable $e) {
            error_log("Error en getCorreosFinanzas: " . $e->getMessage());
            return self::filtrarCorreosValidos([]);
        }
    }

    /**
     * Resuelve los correos del área Comercial (Pedidos a Distribuidores y Clientes)
     * Regla Minuta Punto 2: Confirmación de entrega al área comercial cuando finaliza en destino.
     */
    public static function getCorreosComercial(PDO $db, ?int $idPedido = null): array
    {
        try {
            $sql = "
                SELECT DISTINCT u.email_user 
                FROM usuarios u
                WHERE u.rolid IN (60, 69)
                  AND u.status = 1 
                  AND u.email_user IS NOT NULL
            ";
            $stmt = $db->query($sql);
            $correos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            // Si hay un pedido asociado, agregar al vendedor o usuario que capturó el pedido
            if ($idPedido && $idPedido > 0) {
                $stmtPed = $db->prepare("
                    SELECT u.email_user 
                    FROM ped_pedidos p 
                    INNER JOIN usuarios u ON p.idusuario_acceso = u.idusuario 
                    WHERE p.idpedido = ? AND u.status = 1 AND u.email_user IS NOT NULL
                ");
                $stmtPed->execute([$idPedido]);
                $correoVendedor = $stmtPed->fetchColumn();
                if ($correoVendedor) {
                    $correos[] = $correoVendedor;
                }
            }

            return self::filtrarCorreosValidos($correos);
        } catch (Throwable $e) {
            error_log("Error en getCorreosComercial: " . $e->getMessage());
            return self::filtrarCorreosValidos([]);
        }
    }

    /**
     * Resuelve los correos del equipo de Logística (Planeadores, Planta, Admin Logística)
     */
    public static function getCorreosLogistica(PDO $db): array
    {
        try {
            $sql = "
                SELECT DISTINCT u.email_user 
                FROM usuarios u
                WHERE u.rolid IN (1, 65, 67, 70)
                  AND u.status = 1 
                  AND u.email_user IS NOT NULL
            ";
            $stmt = $db->query($sql);
            $correos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return self::filtrarCorreosValidos($correos);
        } catch (Throwable $e) {
            error_log("Error en getCorreosLogistica: " . $e->getMessage());
            return self::filtrarCorreosValidos([]);
        }
    }

    /**
     * Envío seguro de correos electrónicos vía PHPMailer o función nativa mail() con manejo de errores
     */
    public static function enviar(array $destinatarios, string $asunto, string $html): bool
    {
        $destinatarios = self::filtrarCorreosValidos($destinatarios);
        if (empty($destinatarios)) {
            error_log("Lgs_notificacionesHelper::enviar: No hay destinatarios válidos para el correo '{$asunto}'.");
            return false;
        }

        try {
            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('MAIL_HOST') && !empty(MAIL_HOST)) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USER;
                $mail->Password   = MAIL_PASS;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = MAIL_PORT;
                $mail->CharSet    = 'UTF-8';
                $fromUser         = defined('MAIL_USER') ? MAIL_USER : 'no-reply@ldrsolutions.com.mx';
                $mail->setFrom($fromUser, 'MRP Logística');

                foreach ($destinatarios as $correo) {
                    $mail->addAddress($correo);
                }

                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body    = $html;
                return $mail->send();
            } else {
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: no-reply@ldrsolutions.com.mx\r\n";
                return @mail(implode(',', $destinatarios), $asunto, $html, $headers);
            }
        } catch (Throwable $e) {
            error_log("Error al enviar correo en Lgs_notificacionesHelper: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Filtra correos únicos y válidos, aplicando fallback a MAIL_WEBMASTER en caso de estar vacío
     */
    private static function filtrarCorreosValidos(array $correos): array
    {
        $validos = array_filter(array_unique($correos), function ($e) {
            return filter_var(trim((string)$e), FILTER_VALIDATE_EMAIL);
        });

        if (empty($validos) && defined('MAIL_WEBMASTER') && filter_var(MAIL_WEBMASTER, FILTER_VALIDATE_EMAIL)) {
            $validos[] = MAIL_WEBMASTER;
        }

        return array_values($validos);
    }
}

