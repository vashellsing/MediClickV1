// public/js/navbar.js - VERSIÓN SIMPLIFICADA Y CORREGIDA

document.addEventListener('DOMContentLoaded', function() {
    const notificationCount = document.getElementById('notificationCount');
    const notificationsList = document.getElementById('notificationsList');
    
    // Cargar notificaciones inmediatamente
    loadNotifications();
    
    // Recargar cada 10 segundos
    setInterval(loadNotifications, 10000);

    async function loadNotifications() {
        try {
            console.log('🔄 Cargando notificaciones...');
            const response = await fetch('api/get_notifications.php');
            
            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }
            
            const notifications = await response.json();
            console.log('📨 Notificaciones recibidas:', notifications);
            
            updateNotificationCounter(notifications);
            renderNotifications(notifications);
            
        } catch (error) {
            console.error('❌ Error cargando notificaciones:', error);
        }
    }

    function updateNotificationCounter(notifications) {
        if (!notificationCount) return;
        
        const unreadCount = notifications.filter(n => n.leida === 'no').length;
        console.log(`🔔 Notificaciones no leídas: ${unreadCount}`);
        
        if (unreadCount > 0) {
            notificationCount.textContent = unreadCount;
            notificationCount.style.display = 'block';
        } else {
            notificationCount.style.display = 'none';
        }
    }

    function renderNotifications(notifications) {
        if (!notificationsList) return;
        
        // Limpiar solo las notificaciones (no los headers)
        const existingNotifications = notificationsList.querySelectorAll('.notification-item, .no-notifications');
        existingNotifications.forEach(item => item.remove());
        
        // Encontrar donde insertar (después del divider)
        const divider = notificationsList.querySelector('.dropdown-divider');
        const insertPoint = divider ? divider.nextSibling : notificationsList.lastChild;
        
        if (!Array.isArray(notifications) || notifications.length === 0) {
            const emptyMsg = document.createElement('li');
            emptyMsg.className = 'dropdown-item text-center text-muted no-notifications';
            emptyMsg.innerHTML = '<small>No hay notificaciones</small>';
            notificationsList.appendChild(emptyMsg);
            return;
        }
        
        notifications.forEach(notification => {
            const li = document.createElement('li');
            const isUnread = notification.leida === 'no';
            
            // Determinar ícono según el tipo de mensaje
            let icon = '📄';
            if (notification.mensaje.includes('agendada') || notification.mensaje.includes('exitosa')) {
                icon = '✅';
            } else if (notification.mensaje.includes('cancelada')) {
                icon = '❌';
            }
            
            li.innerHTML = `
                <a href="index.php?page=historial" class="dropdown-item notification-item ${isUnread ? 'fw-bold' : ''}">
                    <div class="d-flex align-items-start">
                        <span class="me-2">${icon}</span>
                        <div class="flex-grow-1">
                            <div class="small">${notification.mensaje}</div>
                            <small class="text-muted">${notification.fecha_formateada || ''}</small>
                        </div>
                        ${isUnread ? '<span class="badge bg-danger ms-2">Nueva</span>' : ''}
                    </div>
                </a>
            `;
            
            notificationsList.appendChild(li);
        });
    }
    
    // Hacer la función global para que otros scripts puedan usarla
    window.loadNotifications = loadNotifications;
});