/* public/js/agendar-general.js
   Lógica del calendario, médicos y horarios (front-end only, simulación).
*/

(function () {
  // ---------- Datos de ejemplo ----------
  const doctors = [
    { id: 1, name: 'Dra. María López', speciality: 'Medicina General', avatar: '' },
    { id: 2, name: 'Dr. Carlos Ramírez', speciality: 'Pediatría', avatar: '' },
    { id: 3, name: 'Dra. Ana Torres', speciality: 'Ginecología', avatar: '' },
    { id: 4, name: 'Dr. Felipe Gómez', speciality: 'Ortopedia', avatar: '' },
  ];

  // estado
  let currentMonth = new Date(); // fecha utilizada para el calendario
  let selectedDate = null;       // Date object
  let selectedDoctorId = null;   // number | null
  let selectedSlot = null;       // string 'HH:MM'

  // referencias al DOM
  const calendarGrid = document.getElementById('calendarGrid');
  const currentMonthLabel = document.getElementById('currentMonth');
  const prevMonthBtn = document.getElementById('prevMonth');
  const nextMonthBtn = document.getElementById('nextMonth');

  const doctorListEl = document.getElementById('doctorList');
  const doctorSearchInput = document.getElementById('doctorSearch');
  const btnDoctorSearch = document.getElementById('btnDoctorSearch');
  const omitDoctorBtn = document.getElementById('omitDoctor');

  const scheduleGrid = document.getElementById('scheduleGrid');

  const continueBtn = document.getElementById('continueBtn');
  const backBtn = document.getElementById('backBtn');
  const confirmBtn = document.getElementById('confirmBtn');

  const confirmDoctorEl = document.getElementById('confirmDoctor');
  const confirmTypeEl = document.getElementById('confirmType');
  const confirmScheduleEl = document.getElementById('confirmSchedule');
  const confirmDateInput = document.getElementById('confirmDate');
  const confirmDoctorIdInput = document.getElementById('confirmDoctorId');

  // util: formatea fecha
  function formatDate(date) {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${yyyy}-${mm}-${dd}`;
  }

  function formatDisplayDate(date) {
    return date.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
  }

  // ---------- Calendario ----------
  function renderCalendar(date) {
    // fecha al primer día del mes
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    // weekday Monday as first column: JS getDay() returns 0=Sun..6=Sat
    // convert so that Monday=0..Sunday=6
    const offset = (firstDay.getDay() + 6) % 7;

    currentMonthLabel.textContent = date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });

    calendarGrid.innerHTML = '';

    // fill blanks
    for (let i = 0; i < offset; i++) {
      const empty = document.createElement('div');
      empty.className = 'col border rounded bg-light text-center py-3';
      empty.innerHTML = '&nbsp;';
      calendarGrid.appendChild(empty);
    }

    for (let d = 1; d <= lastDay.getDate(); d++) {
      const cellDate = new Date(year, month, d);
      const cell = document.createElement('div');
      cell.className = 'col border rounded text-center py-2 day-cell';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm btn-outline-secondary w-100';
      btn.textContent = d;
      btn.dataset.date = formatDate(cellDate);

      // marca el día hoy
      const today = new Date();
      if (cellDate.toDateString() === today.toDateString()) {
        btn.classList.add('fw-bold');
      }

      btn.addEventListener('click', () => {
        // selecciona día
        selectedDate = cellDate;
        // marca visual
        document.querySelectorAll('.day-cell button').forEach(b => b.classList.remove('btn-primary'));
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
        // renderiza horarios para ese día
        renderSchedule();
      });

      cell.appendChild(btn);
      calendarGrid.appendChild(cell);
    }

    // si ya había seleccionado una fecha y está en el mes actual, re-marcarla
    if (selectedDate && selectedDate.getMonth() === month && selectedDate.getFullYear() === year) {
      const sel = calendarGrid.querySelector(`button[data-date="${formatDate(selectedDate)}"]`);
      if (sel) {
        sel.classList.remove('btn-outline-secondary');
        sel.classList.add('btn-primary');
      }
    }
  }

  prevMonthBtn?.addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
    renderCalendar(currentMonth);
  });

  nextMonthBtn?.addEventListener('click', () => {
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
    renderCalendar(currentMonth);
  });

  // ---------- Doctores ----------
  function renderDoctors(filter = '') {
    doctorListEl.innerHTML = '';
    const normalizedFilter = filter.trim().toLowerCase();
    const filtered = doctors.filter(d => d.name.toLowerCase().includes(normalizedFilter) || d.speciality.toLowerCase().includes(normalizedFilter));

    filtered.forEach(d => {
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
      item.dataset.doctorId = d.id;
      item.innerHTML = `<div>
                          <strong>${d.name}</strong><div class="small text-muted">${d.speciality}</div>
                        </div>
                        <div class="small text-muted">ID ${d.id}</div>`;

      item.addEventListener('click', () => {
        selectedDoctorId = d.id;
        // visual
        doctorListEl.querySelectorAll('.list-group-item').forEach(n => n.classList.remove('active'));
        item.classList.add('active');
        // render horarios según doctor
        renderSchedule();
      });

      doctorListEl.appendChild(item);
    });

    if (filtered.length === 0) {
      const li = document.createElement('div');
      li.className = 'text-muted small';
      li.textContent = 'No se encontraron médicos.';
      doctorListEl.appendChild(li);
    }
  }

  btnDoctorSearch?.addEventListener('click', () => {
    renderDoctors(doctorSearchInput.value);
  });

  doctorSearchInput?.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') renderDoctors(doctorSearchInput.value);
  });

  omitDoctorBtn?.addEventListener('click', () => {
    selectedDoctorId = null;
    // remover selección visual
    doctorListEl.querySelectorAll('.list-group-item').forEach(n => n.classList.remove('active'));
    renderSchedule();
  });

  // ---------- Horarios ----------
  // Genera horarios: start 09:00 end 17:00 cada 30 minutos
  function generateSlots() {
    const slots = [];
    for (let h = 9; h < 17; h++) {
      slots.push(`${String(h).padStart(2, '0')}:00`);
      slots.push(`${String(h).padStart(2, '0')}:30`);
    }
    // incluir 17:00 como última hora
    slots.push('17:00');
    return slots;
  }

  // pequeña función determinista para marcar algunos horarios como ocupados
  function isOccupied(dateStr, doctorId, slot) {
    // usa suma de códigos para pseudo-aleatoriedad determinista
    const key = `${dateStr}|${doctorId ?? 'any'}|${slot}`;
    let sum = 0;
    for (let i = 0; i < key.length; i++) sum = (sum + key.charCodeAt(i)) % 97;
    // marca ocupado si sum % 5 == 0 (aprox 1/5 de slots ocupados)
    return (sum % 5) === 0;
  }

  function renderSchedule() {
    scheduleGrid.innerHTML = '';

    if (!selectedDate) {
      scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero una fecha en el calendario.</div>';
      return;
    }

    const dateStr = formatDate(selectedDate);
    const slots = generateSlots();

    slots.forEach(slot => {
      const occupied = isOccupied(dateStr, selectedDoctorId, slot);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-sm';
      btn.style.minWidth = '90px';
      btn.textContent = slot;

      if (occupied) {
        btn.classList.add('btn-danger');
        btn.disabled = true;
      } else {
        btn.classList.add('btn-outline-success');
        btn.addEventListener('click', () => {
          // seleccionar
          selectedSlot = slot;
          // actualizar clases visuales
          scheduleGrid.querySelectorAll('button').forEach(b => {
            b.classList.remove('btn-primary');
            // solo botones disponibles conservan el outline
            if (!b.disabled && !b.classList.contains('btn-danger')) {
              b.classList.remove('btn-success');
              b.classList.add('btn-outline-success');
            }
          });
          btn.classList.remove('btn-outline-success');
          btn.classList.add('btn-primary');
        });
      }

      scheduleGrid.appendChild(btn);
    });

    // si ya hay slot seleccionado y aún disponible, re-seleccionarlo visualmente
    if (selectedSlot) {
      const existing = Array.from(scheduleGrid.querySelectorAll('button')).find(b => b.textContent === selectedSlot && !b.disabled);
      if (existing) {
        existing.classList.remove('btn-outline-success');
        existing.classList.add('btn-primary');
      } else {
        selectedSlot = null; // slot no disponible en la nueva vista
      }
    }
  }

  // ---------- Navegación entre tabs (Bootstrap Tab API) ----------
  function showTabById(tabId) {
    const tabTriggerEl = document.querySelector(`#${tabId}`);
    if (!tabTriggerEl) return;
    const tab = new bootstrap.Tab(tabTriggerEl);
    tab.show();
  }

  continueBtn?.addEventListener('click', () => {
    if (!selectedDate) {
      alert('Seleccione una fecha primero.');
      return;
    }
    if (!selectedSlot) {
      alert('Seleccione un horario disponible.');
      return;
    }

    // llenar confirmación
    confirmDateInput.value = formatDate(selectedDate);
    confirmDoctorIdInput.value = selectedDoctorId ?? '';
    confirmDoctorEl.textContent = selectedDoctorId ? (doctors.find(d => d.id === selectedDoctorId)?.name ?? '---') : 'Cualquiera';
    confirmTypeEl.textContent = 'General';
    confirmScheduleEl.textContent = `${formatDisplayDate(selectedDate)} · ${selectedSlot}`;

    // cambiar a pestaña 2
    showTabById('step2-tab');
  });

  backBtn?.addEventListener('click', () => {
    showTabById('step1-tab');
  });

  confirmBtn?.addEventListener('click', () => {
    // Simula guardar: muestra modal bootstrap
    const modalEl = document.getElementById('confirmModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    // opcional: reset selección (comentar si no quieres resetear)
    selectedDate = null;
    selectedSlot = null;
    selectedDoctorId = null;
    // re-render para limpiar UI
    renderCalendar(currentMonth);
    renderDoctors();
    renderSchedule();
    confirmDoctorEl.textContent = '__________';
    confirmScheduleEl.textContent = '__________';
  });

  // ---------- inicialización ----------
  function init() {
    // marcar currentMonth al primer día del mes
    currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    renderCalendar(currentMonth);
    renderDoctors();
    renderSchedule();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
