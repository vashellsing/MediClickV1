// public/js/navbar.js

document.addEventListener('DOMContentLoaded', () => {
  const notifications = [
    { text: "Cita agendada", time: "09:00", type: "agendada" },
    { text: "Cita reprogramada", time: "10:30", type: "reprogramada" },
    { text: "Cita cancelada", time: "11:45", type: "cancelada" }
  ];

  const list = document.getElementById('notificationsList');
  const count = document.getElementById('notificationCount');

  // Limpiamos
  list.querySelectorAll('.notification-item').forEach(el => el.remove());

  notifications.forEach(note => {
    const li = document.createElement('li');
    li.innerHTML = `
      <a href="index.php?page=historial" class="dropdown-item notification-item notification-${note.type}">
        <span>${note.text}</span>
        <small>${note.time}</small>
      </a>
    `;
    list.appendChild(li);
  });

  count.textContent = notifications.length;
});
