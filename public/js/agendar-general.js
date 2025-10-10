// public/js/agendar-general.js
document.addEventListener('DOMContentLoaded', () => {
  console.log('agendar-general.js cargado');

  // ----- referencias DOM -----
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

  const step2TabTrigger = document.getElementById('step2-tab');

  const confirmDoctorEl = document.getElementById('confirmDoctor');
  const confirmTypeEl = document.getElementById('confirmType');
  const confirmScheduleEl = document.getElementById('confirmSchedule');
  const confirmDateInput = document.getElementById('confirmDate');
  const confirmDoctorIdInput = document.getElementById('confirmDoctorId');

  // ----- estado -----
  let selectedDate = null;      // Date object
  let selectedDoctorId = undefined;  // undefined = no elección; null = "Sin preferencia"; number = id
  let selectedSlot = null;      // "HH:MM"

  // ----- datos de ejemplo (médicos) -----
  const doctors = [
    { id: 1, name: 'Dra. Laura Méndez', speciality: 'Medicina general' },
    { id: 2, name: 'Dr. Carlos Ruiz', speciality: 'Medicina general' },
    { id: 3, name: 'Dra. Natalia Gómez', speciality: 'Medicina general' },
    { id: 4, name: 'Dr. Andrés Pérez', speciality: 'Medicina general' }
  ];

  // ==== utilidades ====
  function pad2(n){ return String(n).padStart(2,'0'); }
  function formatDateISO(d){
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  }

  function isOccupiedDeterministic(dateISO, doctorId, totalMins){
    const key = `${dateISO}|${doctorId ?? 'any'}|${totalMins}`;
    let sum = 0;
    for (let i=0;i<key.length;i++) sum = (sum + key.charCodeAt(i) * (i+1)) % 97;
    return (sum % 4) === 0;
  }

  // ==== calendario ====
  const now = new Date();
  const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0,0,0,0);
  let currentMonth = new Date(todayStart.getFullYear(), todayStart.getMonth(), 1);

  function renderCalendar(date) {
    calendarGrid.innerHTML = '';
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDate = new Date(year, month + 1, 0).getDate();

    currentMonthLabel.textContent = date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

    const startOffset = (firstDay.getDay() + 6) % 7; // lunes = 0
    const grid = document.createElement('div');
    grid.className = 'calendar-grid d-grid gap-2';
    grid.style.gridTemplateColumns = 'repeat(7, 1fr)';

    for (let i = 0; i < startOffset; i++) grid.appendChild(document.createElement('div'));

    for (let d = 1; d <= lastDate; d++) {
      const cellDate = new Date(year, month, d);
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-outline-secondary w-100 py-2';
      btn.textContent = d;
      btn.dataset.date = formatDateISO(cellDate);

      if (cellDate.toDateString() === todayStart.toDateString()) {
        btn.classList.add('fw-bold', 'border-primary');
      }

      if (cellDate.getTime() < todayStart.getTime()) {
        btn.disabled = true;
        btn.classList.add('text-muted');
      } else {
        btn.addEventListener('click', () => {
          selectedDate = cellDate;
          // visual calendario
          grid.querySelectorAll('button').forEach(b => b.classList.remove('btn-primary', 'text-white'));
          btn.classList.add('btn-primary', 'text-white');
          selectedSlot = null;
          renderSchedule();
          updateStep2State();
        });
      }

      grid.appendChild(btn);
    }

    while (grid.children.length % 7 !== 0) grid.appendChild(document.createElement('div'));
    calendarGrid.appendChild(grid);

    if (selectedDate && selectedDate.getFullYear() === year && selectedDate.getMonth() === month) {
      const selBtn = calendarGrid.querySelector(`button[data-date="${formatDateISO(selectedDate)}"]`);
      if (selBtn && !selBtn.disabled) selBtn.classList.add('btn-primary', 'text-white');
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

  // ==== administracion de habilitación de la pestaña y boton Confirmar ====
  // Habilitar step2-tab y confirmBtn sólo cuando: hay fecha, hay slot y el usuario ya eligió doctor (o eligió explicitamente "Sin preferencia" => selectedDoctorId === null).
  function updateStep2State(){
    const ready = (selectedDate !== null) && (selectedSlot !== null) && (selectedDoctorId !== undefined);
    // Pestaña step2
    if (step2TabTrigger) {
      step2TabTrigger.disabled = !ready;
      if (!ready) {
        step2TabTrigger.classList.add('disabled','text-muted');
      } else {
        step2TabTrigger.classList.remove('disabled','text-muted');
      }
    }
    // Boton confirmar en el paso 2
    if (confirmBtn) confirmBtn.disabled = !ready;
    // También deshabilitar el botón continuar si no está listo (para forzar validaciones)
    if (continueBtn) continueBtn.disabled = !((selectedDate !== null) && (selectedSlot !== null));
  }

  // Evitar que el usuario navegue manualmente a step2 si está deshabilitado (por seguridad extra)
  if (step2TabTrigger) {
    step2TabTrigger.addEventListener('click', (e) => {
      if (step2TabTrigger.disabled) e.preventDefault();
    });
    // Si algún script intenta mostrar la pestaña cuando no está listo, cancelar
    document.getElementById('appointmentTabs')?.addEventListener('shown.bs.tab', (ev) => {
      const targetId = ev.target?.id;
      if (targetId === 'step2-tab' && step2TabTrigger && step2TabTrigger.disabled) {
        // forzar volver a la primera pestaña
        const step1 = document.getElementById('step1-tab');
        if (step1) new bootstrap.Tab(step1).show();
      }
    });
  }

  // ==== doctores ====
  function highlightSelectedDoctor() {
    doctorListEl.querySelectorAll('.list-group-item').forEach(x => {
      x.classList.remove('active','bg-primary','text-white');
    });
    if (selectedDoctorId === null) {
      const anyEl = doctorListEl.querySelector('[data-doctor-id="any"]');
      if (anyEl) anyEl.classList.add('active','bg-primary','text-white');
    } else if (selectedDoctorId !== undefined) {
      const btn = doctorListEl.querySelector(`[data-doctor-id="${selectedDoctorId}"]`);
      if (btn) btn.classList.add('active','bg-primary','text-white');
    }
  }

  // renderDoctors ahora tiene un parámetro showResults: si false -> muestra mensaje "Presione Buscar..."
  function renderDoctors(filter = '', showResults = false){
    doctorListEl.innerHTML = '';

    if (!showResults) {
      const hint = document.createElement('div');
      hint.className = 'text-muted small';
      hint.textContent = "Presione 'Buscar' para ver profesionales.";
      doctorListEl.appendChild(hint);
      return;
    }

    const q = (filter||'').trim().toLowerCase();
    const filtered = (q === '') ? doctors.slice() : doctors.filter(d => d.name.toLowerCase().includes(q) || d.speciality.toLowerCase().includes(q));

    // Opción "Sin preferencia" (antes "Cualquiera")
    const anyItem = document.createElement('button');
    anyItem.type = 'button';
    anyItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
    anyItem.dataset.doctorId = 'any';
    anyItem.innerHTML = `<div><strong>Sin preferencia</strong><div class="small text-muted">Asignar cualquier profesional disponible</div></div><div class="small text-muted">ID any</div>`;
    anyItem.addEventListener('click', () => {
      selectedDoctorId = null; // representa "Sin preferencia"
      selectedSlot = null;
      highlightSelectedDoctor();
      renderSchedule();
      updateStep2State();
    });
    doctorListEl.appendChild(anyItem);

    if (filtered.length === 0){
      const no = document.createElement('div');
      no.className = 'text-muted small';
      no.textContent = 'No se encontraron médicos.';
      doctorListEl.appendChild(no);
      return;
    }

    filtered.forEach(d => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
      btn.dataset.doctorId = d.id;
      btn.innerHTML = `<div><strong>${d.name}</strong><div class="small text-muted">${d.speciality}</div></div><div class="small text-muted">ID ${d.id}</div>`;

      btn.addEventListener('click', () => {
        selectedDoctorId = d.id;
        highlightSelectedDoctor();
        selectedSlot = null;
        renderSchedule();
        updateStep2State();
      });

      doctorListEl.appendChild(btn);
    });

    // aplicar resaltado si hay seleccionado
    highlightSelectedDoctor();
  }

  // Al hacer click en buscar mostramos resultados (incluso si el campo está vacío)
  btnDoctorSearch?.addEventListener('click', () => {
    const val = (doctorSearchInput.value || '').trim();
    renderDoctors(val, true);
  });
  doctorSearchInput?.addEventListener('keyup', (e) => { if (e.key === 'Enter') {
    const val = (doctorSearchInput.value || '').trim();
    renderDoctors(val, true);
  }});

  // Omitir -> seleccionar "Sin preferencia" y mostrar lista (por si quieren ver la opción)
  omitDoctorBtn?.addEventListener('click', () => {
    selectedDoctorId = null;
    selectedSlot = null;
    renderDoctors(doctorSearchInput.value || '', true);
    highlightSelectedDoctor();
    renderSchedule();
    updateStep2State();
  });

  // ==== horarios (cada 20 minutos) ====
  function renderSchedule(){
    scheduleGrid.innerHTML = '';

    if (!selectedDate){
      scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un día.</div>';
      return;
    }

    const dateISO = formatDateISO(selectedDate);
    const start = 8*60;
    const end = 17*60;
    const fragment = document.createDocumentFragment();

    for (let t = start; t <= end; t += 20){
      const h = Math.floor(t/60);
      const m = t%60;
      const timeLabel = `${pad2(h)}:${pad2(m)}`;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = timeLabel;
      btn.style.minWidth = '90px';
      btn.className = 'btn btn-sm m-1';

      if (selectedDoctorId === null) {
        const anyAvailable = doctors.some(d => !isOccupiedDeterministic(dateISO, d.id, t));
        if (!anyAvailable) {
          btn.classList.add('btn-danger');
          btn.disabled = true;
        } else {
          btn.classList.add('btn-outline-success');
          btn.addEventListener('click', () => {
            const assigned = doctors.find(d => !isOccupiedDeterministic(dateISO, d.id, t));
            if (assigned) {
              selectedDoctorId = assigned.id;
              highlightSelectedDoctor();
            }
            scheduleGrid.querySelectorAll('button').forEach(b => {
              if (!b.disabled) {
                b.classList.remove('btn-primary');
                b.classList.remove('btn-success');
                if (!b.classList.contains('btn-outline-success')) b.classList.add('btn-outline-success');
              }
            });
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-primary');
            selectedSlot = timeLabel;
            updateStep2State();
          });
        }
      } else if (selectedDoctorId === undefined) {
        // No hay doctor seleccionado todavía -> pedir que escoja uno (no permitir seleccionar horario)
        btn.classList.add('btn-outline-secondary');
        btn.disabled = true;
      } else {
        const occupied = isOccupiedDeterministic(dateISO, selectedDoctorId, t);
        if (occupied){
          btn.classList.add('btn-danger');
          btn.disabled = true;
        } else {
          btn.classList.add('btn-outline-success');
          btn.addEventListener('click', () => {
            scheduleGrid.querySelectorAll('button').forEach(b => {
              if (!b.disabled) {
                b.classList.remove('btn-primary');
                b.classList.remove('btn-success');
                if (!b.classList.contains('btn-outline-success')) b.classList.add('btn-outline-success');
              }
            });
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-primary');
            selectedSlot = timeLabel;
            updateStep2State();
          });
        }
      }

      fragment.appendChild(btn);
    }

    scheduleGrid.appendChild(fragment);

    if (selectedSlot){
      const exists = Array.from(scheduleGrid.querySelectorAll('button')).find(b => !b.disabled && b.textContent === selectedSlot);
      if (exists){
        exists.classList.remove('btn-outline-success');
        exists.classList.add('btn-primary');
      } else {
        selectedSlot = null;
        updateStep2State();
      }
    }
  }

  // ==== confirmación y navegación de tabs ====
  continueBtn?.addEventListener('click', () => {
    if (!selectedDate) return alert('Seleccione una fecha.');
    const selStart = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 0,0,0,0);
    if (selStart.getTime() < todayStart.getTime()) return alert('No puede agendar una fecha anterior a hoy.');
    if (!selectedSlot) return alert('Seleccione un horario.');
    if (selectedDoctorId === undefined) return alert('Seleccione un médico o presione "Sin preferencia".');

    // Si selectedDoctorId === null => "Sin preferencia": ya se asignó cuando eligió el horario (lógica anterior) o lo intentamos ahora
    if (selectedDoctorId === null) {
      const dateISO = formatDateISO(selectedDate);
      const mins = parseInt(selectedSlot.split(':')[0],10)*60 + parseInt(selectedSlot.split(':')[1],10);
      const assigned = doctors.find(d => !isOccupiedDeterministic(dateISO, d.id, mins));
      if (assigned) selectedDoctorId = assigned.id;
      else return alert('No hay profesionales disponibles en el horario seleccionado.');
    }

    confirmDateInput.value = formatDateISO(selectedDate);
    confirmDoctorIdInput.value = selectedDoctorId;
    confirmDoctorEl.textContent = doctors.find(d=>d.id===selectedDoctorId)?.name ?? 'Sin preferencia';
    confirmTypeEl.textContent = 'General';
    confirmScheduleEl.textContent = `${selectedDate.toLocaleDateString()} · ${selectedSlot}`;

    if (step2TabTrigger && !step2TabTrigger.disabled) new bootstrap.Tab(step2TabTrigger).show();
  });

  backBtn?.addEventListener('click', () => {
    const step1 = document.getElementById('step1-tab');
    if (step1) new bootstrap.Tab(step1).show();
  });

  confirmBtn?.addEventListener('click', () => {
    const modalEl = document.getElementById('confirmModal');
    if (modalEl) new bootstrap.Modal(modalEl).show();
  });

  // ==== inicialización ====
  renderCalendar(currentMonth);
  renderDoctors('', false); // NO mostrar lista hasta que usuario presione "Buscar"
  renderSchedule();
  updateStep2State();
});
