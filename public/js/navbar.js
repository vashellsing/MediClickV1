// public/js/navbar.js - VERSIÓN CON ACTUALIZACIÓN REAL

document.addEventListener('DOMContentLoaded', function() {
    const notificationCount = document.getElementById('notificationCount');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    
    let notificationsMarkedAsRead = false;
    let updateInterval;

    // Iniciar actualización automática
    startAutoUpdate();

    // Eventos del dropdown
    if (notificationsDropdown) {
        // Cuando se ABRE el dropdown
        notificationsDropdown.addEventListener('show.bs.dropdown', function() {
            console.log('🔔 Dropdown abierto');
            
            // Pausar actualizaciones automáticas mientras el dropdown está abierto
            stopAutoUpdate();
            
            // Marcar como leídas después de un pequeño delay
            setTimeout(() => {
                if (!notificationsMarkedAsRead) {
                    markNotificationsAsRead();
                    notificationsMarkedAsRead = true;
                }
            }, 500);
        });

        // Cuando se CIERRA el dropdown
        notificationsDropdown.addEventListener('hidden.bs.dropdown', function() {
            console.log('🔔 Dropdown cerrado');
            
            // Reanudar actualizaciones automáticas
            startAutoUpdate();
            
            // Permitir marcar como leídas nuevamente la próxima vez
            setTimeout(() => {
                notificationsMarkedAsRead = false;
            }, 1000);
        });
    }

    function startAutoUpdate() {
        // Detener intervalo anterior si existe
        if (updateInterval) {
            clearInterval(updateInterval);
        }
        
        // Actualizar inmediatamente
        updateNotificationCount();
        
        // Configurar intervalo cada 20 segundos
        updateInterval = setInterval(updateNotificationCount, 20000);
        console.log('🔄 Iniciada actualización automática cada 20 segundos');
    }

    function stopAutoUpdate() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log('⏸️ Detenida actualización automática');
        }
    }

    async function updateNotificationCount() {
        try {
            console.log('🔄 Verificando nuevas notificaciones...');
            const response = await fetch('api/get_notification_count.php?t=' + Date.now());
            
            if (response.ok) {
                const data = await response.json();
                const currentCount = data.unread_count;
                
                // Solo actualizar si el conteo cambió
                const currentDisplayCount = notificationCount.style.display !== 'none' ? 
                    parseInt(notificationCount.textContent) : 0;
                
                if (currentCount !== currentDisplayCount) {
                    updateCounterDisplay(currentCount);
                    console.log(`📊 Contador actualizado: ${currentCount} notificaciones no leídas`);
                    
                    // Si hay nuevas notificaciones y el dropdown no está abierto, mostrar notificación
                    if (currentCount > currentDisplayCount && currentDisplayCount === 0) {
                        showNewNotificationAlert(currentCount);
                    }
                }
            }
        } catch (error) {
            console.log('❌ Error actualizando contador:', error);
        }
    }

    function updateCounterDisplay(count) {
        if (!notificationCount) return;
        
        if (count > 0) {
            notificationCount.textContent = count;
            notificationCount.style.display = 'block';
        } else {
            notificationCount.style.display = 'none';
        }
    }

    function showNewNotificationAlert(count) {
        // Crear una alerta visual de nuevas notificaciones
        const alert = document.createElement('div');
        alert.className = 'position-fixed top-0 end-0 m-3 p-3 bg-success text-white rounded shadow';
        alert.style.zIndex = '1060';
        alert.innerHTML = `
            <div class="d-flex align-items-center">
                <span class="me-2">🔔</span>
                <span>Tienes ${count} nueva(s) notificación(es)</span>
                <button type="button" class="btn-close btn-close-white ms-2" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(alert);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }

    async function markNotificationsAsRead() {
        try {
            console.log('📝 Marcando notificaciones como leídas...');
            const response = await fetch('api/mark_notifications_read.php');
            
            if (response.ok) {
                const result = await response.json();
                console.log('✅ Notificaciones marcadas como leídas');
                
                // Actualizar el contador localmente
                updateCounterDisplay(0);
            }
        } catch (error) {
            console.log('❌ Error marcando notificaciones como leídas:', error);
        }
    }
    
    // Función global para forzar actualización inmediata (desde otros scripts)
    window.forceNotificationUpdate = function() {
        console.log('🚀 Forzando actualización de notificaciones');
        updateNotificationCount();
    };
    
    // Función para notificar desde otros scripts (cuando se agenda una cita)
    window.notifyNewAppointment = function() {
        console.log('📅 Nueva cita agendada - forzando actualización');
        // Esperar un poco para que la notificación se guarde en la BD
        setTimeout(() => {
            updateNotificationCount();
        }, 1000);
    };
});