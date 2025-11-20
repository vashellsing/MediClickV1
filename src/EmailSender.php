<?php
namespace MediClick;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Configuración del servidor SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'mediclickphp@gmail.com';
        $this->mailer->Password = 'aklenafxwusvhbwh';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        
        // Remitente
        $this->mailer->setFrom('no-reply@mediclick.com', 'MediClick');
        $this->mailer->isHTML(true);
    }
    
    public function enviarNotificacionCita($pacienteEmail, $pacienteNombre, $tipo, $citaData) {
        try {
            $this->mailer->addAddress($pacienteEmail, $pacienteNombre);
            
            switch($tipo) {
                case 'agendada':
                    $this->mailer->Subject = '✅ Cita Agendada - MediClick';
                    $this->mailer->Body = $this->getTemplateAgendada($citaData);
                    break;
                    
                case 'cancelada':
                    $this->mailer->Subject = '❌ Cita Cancelada - MediClick';
                    $this->mailer->Body = $this->getTemplateCancelada($citaData);
                    break;
                    
                case 'reprogramada':
                    $this->mailer->Subject = '🔄 Cita Reprogramada - MediClick';
                    $this->mailer->Body = $this->getTemplateReprogramada($citaData);
                    break;
            }
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Error enviando email: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    private function getTemplateAgendada($citaData) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Cita Agendada Exitosamente</h1>
                </div>
                <div class='content'>
                    <h3>Hola {$citaData['paciente_nombre']},</h3>
                    <p>Tu cita ha sido agendada exitosamente con los siguientes detalles:</p>
                    
                    <p><strong>Tipo de Cita:</strong> {$citaData['tipo_cita']}</p>
                    <p><strong>Médico:</strong> {$citaData['medico_nombre']}</p>
                    <p><strong>Fecha:</strong> {$citaData['fecha']}</p>
                    <p><strong>Hora:</strong> {$citaData['hora']}</p>
                    
                    <p>Por favor presenta puntualidad en tu cita.</p>
                </div>
                <div class='footer'>
                    <p>MediClick - Sistema de Agendamiento de Citas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTemplateCancelada($citaData) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Cita Cancelada</h1>
                </div>
                <div class='content'>
                    <h3>Hola {$citaData['paciente_nombre']},</h3>
                    <p>Tu cita ha sido cancelada:</p>
                    
                    <p><strong>Tipo de Cita:</strong> {$citaData['tipo_cita']}</p>
                    <p><strong>Médico:</strong> {$citaData['medico_nombre']}</p>
                    <p><strong>Fecha Original:</strong> {$citaData['fecha']}</p>
                    <p><strong>Hora Original:</strong> {$citaData['hora']}</p>
                    
                    <p>Si necesitas reagendar, puedes hacerlo desde tu historial de citas.</p>
                </div>
                <div class='footer'>
                    <p>MediClick - Sistema de Agendamiento de Citas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTemplateReprogramada($citaData) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ffc107; color: black; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔄 Cita Reprogramada</h1>
                </div>
                <div class='content'>
                    <h3>Hola {$citaData['paciente_nombre']},</h3>
                    <p>Tu cita ha sido reprogramada:</p>
                    
                    <p><strong>Nueva Fecha:</strong> {$citaData['nueva_fecha']}</p>
                    <p><strong>Nueva Hora:</strong> {$citaData['nueva_hora']}</p>
                    <p><strong>Médico:</strong> {$citaData['medico_nombre']}</p>
                    <p><strong>Tipo de Cita:</strong> {$citaData['tipo_cita']}</p>
                    
                    <p>Fecha anterior: {$citaData['fecha_anterior']} a las {$citaData['hora_anterior']}</p>
                </div>
                <div class='footer'>
                    <p>MediClick - Sistema de Agendamiento de Citas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}