// public/js/agendar-especializacion.js
document.addEventListener('DOMContentLoaded', () => {
  console.log('agendar-especializacion.js cargado');

  // ----- referencias DOM -----
  const calendarGrid = document.getElementById('calendarGrid');
  const currentMonthLabel = document.getElementById('currentMonth');
  const prevMonthBtn = document.getElementById('prevMonth');
  const nextMonthBtn = document.getElementById('nextMonth');

  const specializationSelect = document.getElementById('specializationSelect');
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
  // selectedDoctorId: undefined = usuario no eligió nada (aún no buscó o no eligió);
  // null = "Sin preferencia"; number = id del doctor seleccionado
  let selectedDate = null;      
  let selectedDoctorId = undefined;  
  let selectedSlot = null;      
  let selectedSpecialization = ''; // valor del select (ej. 'neurologia')

  // ----- datos de ejemplo (médicos) -----
  const doctors = [
    { id: 1, name: 'Dra. Laura Méndez', speciality: 'neurologia' },
    { id: 2, name: 'Dr. Juan Gomez', speciality: 'neurologia' },
    { id: 2, name: 'Dr. Carlos Ruiz', speciality: 'pediatria' },
    { id: 3, name: 'Dr. Enrique Chocue', speciality: 'pediatria' },
    { id: 3, name: 'Dra. Natalia Gómez', speciality: 'odontologia' },
    { id: 4, name: 'Dra. Sofía Prado', speciality: 'odontologia' },
    { id: 5, name: 'Dr. Andrés Pérez', speciality: 'dermatologia' },
    { id: 6, name: 'Dr. Melisa Muñoz', speciality: 'dermatologia' },
    { id: 7, name: 'Dr. Dana Estrada', speciality: 'cardiologia' },
    { id: 8, name: 'Dr. Dayna Restrepo', speciality: 'cardiologia' }
  ];

  // ==== utilidades ====
  function pad2(n){ return String(n).padStart(2,'0'); }
  function formatDateISO(d){
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  }

  // Determinista: marca algunos slots como ocupados según fecha+doctor+slot
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

      // deshabilitar fechas anteriores
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

  // ==== gestión de habilitación de pestaña y botón Confirmar ====
  function updateStep2State(){
    const ready = (selectedDate !== null) && (selectedSlot !== null) && (selectedDoctorId !== undefined);
    // habilita/deshabilita pestaña step2
    if (step2TabTrigger) {
      step2TabTrigger.disabled = !ready;
      if (!ready) step2TabTrigger.classList.add('disabled','text-muted');
      else step2TabTrigger.classList.remove('disabled','text-muted');
    }
    // botón confirmar
    if (confirmBtn) confirmBtn.disabled = !ready;
    // botón continuar habilitado si hay fecha y slot (doctor puede venir después como "Sin preferencia")
    if (continueBtn) continueBtn.disabled = !((selectedDate !== null) && (selectedSlot !== null));
  }

  // prevenir navegación manual a step2 cuando no está listo
  if (step2TabTrigger) {
    step2TabTrigger.addEventListener('click', (e) => {
      if (step2TabTrigger.disabled) e.preventDefault();
    });
    document.getElementById('appointmentTabs')?.addEventListener('shown.bs.tab', (ev) => {
      const targetId = ev.target?.id;
      if (targetId === 'step2-tab' && step2TabTrigger && step2TabTrigger.disabled) {
        const step1 = document.getElementById('step1-tab');
        if (step1) new bootstrap.Tab(step1).show();
      }
    });
  }

  // ==== doctores / búsqueda ====
  function getFilteredDoctors(filter = '') {
    const q = (filter||'').trim().toLowerCase();
    // si hay especialidad seleccionada, filtrar por ella
    const bySpec = selectedSpecialization ? doctors.filter(d => d.speciality === selectedSpecialization) : doctors.slice();
    return (q === '') ? bySpec : bySpec.filter(d => d.name.toLowerCase().includes(q) || d.speciality.toLowerCase().includes(q));
  }

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

  // renderDoctors(showResults=false) -> si false muestra mensaje "Presione 'Buscar'..."
  function renderDoctors(filter = '', showResults = false){
    doctorListEl.innerHTML = '';

    if (!showResults) {
      const hint = document.createElement('div');
      hint.className = 'text-muted small';
      hint.textContent = selectedSpecialization ? `Presione 'Buscar' para ver profesionales (filtrando por ${specializationSelect.options[specializationSelect.selectedIndex].text}).` 
                                                : "Presione 'Buscar' para ver profesionales.";
      doctorListEl.appendChild(hint);
      return;
    }

    const filtered = getFilteredDoctors(filter);

    // Opción "Sin preferencia"
    const anyItem = document.createElement('button');
    anyItem.type = 'button';
    anyItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
    anyItem.dataset.doctorId = 'any';
    anyItem.innerHTML = `<div><strong>Sin preferencia</strong><div class="small text-muted">Asignar cualquier profesional disponible${selectedSpecialization ? ' de la especialidad seleccionada' : ''}</div></div><div class="small text-muted">ID any</div>`;
    anyItem.addEventListener('click', () => {
      selectedDoctorId = null; // "Sin preferencia"
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

    highlightSelectedDoctor();
  }

  // eventos de búsqueda
  btnDoctorSearch?.addEventListener('click', () => {
    const val = (doctorSearchInput.value || '').trim();
    renderDoctors(val, true);
  });
  doctorSearchInput?.addEventListener('keyup', (e) => { if (e.key === 'Enter') {
    const val = (doctorSearchInput.value || '').trim();
    renderDoctors(val, true);
  }});

  // Omitir -> seleccionar "Sin preferencia" y mostrar la lista (para que se vea la opción)
  omitDoctorBtn?.addEventListener('click', () => {
    selectedDoctorId = null;
    selectedSlot = null;
    renderDoctors(doctorSearchInput.value || '', true);
    highlightSelectedDoctor();
    renderSchedule();
    updateStep2State();
  });

  // cuando cambie la especialidad, actualizamos variable y limpiamos selección de doctor
  specializationSelect?.addEventListener('change', () => {
    selectedSpecialization = specializationSelect.value || '';
    // Limpiar selección previa de doctor (el usuario deberá volver a buscar/seleccionar)
    selectedDoctorId = undefined;
    selectedSlot = null;
    renderDoctors(doctorSearchInput.value || '', false); // no mostrar resultados automáticamente
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

    // si no hay selección de doctor (undefined), pedir que busque o elija "Sin preferencia"
    if (selectedDoctorId === undefined){
      scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un médico (presione Buscar) o elija "Sin preferencia".</div>';
      return;
    }

    const dateISO = formatDateISO(selectedDate);
    const start = 8*60;
    const end = 17*60;
    const fragment = document.createDocumentFragment();

    // función para obtener lista de doctores a considerar según la especialidad
    const candidateDoctors = selectedSpecialization ? doctors.filter(d => d.speciality === selectedSpecialization) : doctors.slice();

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
        // Sin preferencia: disponible si al menos un profesional en la especialidad está libre
        const anyAvailable = candidateDoctors.some(d => !isOccupiedDeterministic(dateISO, d.id, t));
        if (!anyAvailable) {
          btn.classList.add('btn-danger');
          btn.disabled = true;
        } else {
          btn.classList.add('btn-outline-success');
          btn.addEventListener('click', () => {
            // asignar automaticamente el primer doctor libre dentro de la especialidad (o en general si no hay especialidad)
            const assigned = candidateDoctors.find(d => !isOccupiedDeterministic(dateISO, d.id, t));
            if (assigned) {
              selectedDoctorId = assigned.id;
              highlightSelectedDoctor();
            }
            // marcar seleccionado
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
      } else {
        // doctor específico
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

    // restaurar slot seleccionado si sigue existiendo
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

  // ==== confirmación y navegación ====
  continueBtn?.addEventListener('click', () => {
    if (!selectedDate) return alert('Seleccione una fecha.');
    const selStart = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate(), 0,0,0,0);
    if (selStart.getTime() < todayStart.getTime()) return alert('No puede agendar una fecha anterior a hoy.');
    if (!selectedSlot) return alert('Seleccione un horario.');
    if (selectedDoctorId === undefined) return alert('Seleccione un médico o presione "Sin preferencia".');

    // si sin preferencia, intentamos asignar ahora (si no se asignó en el click)
    if (selectedDoctorId === null) {
      const dateISO = formatDateISO(selectedDate);
      const mins = parseInt(selectedSlot.split(':')[0],10)*60 + parseInt(selectedSlot.split(':')[1],10);
      // candidatos según especialidad
      const candidates = selectedSpecialization ? doctors.filter(d => d.speciality === selectedSpecialization) : doctors;
      const assigned = candidates.find(d => !isOccupiedDeterministic(dateISO, d.id, mins));
      if (assigned) selectedDoctorId = assigned.id;
      else return alert('No hay profesionales disponibles en el horario seleccionado.');
    }

    confirmDateInput.value = formatDateISO(selectedDate);
    confirmDoctorIdInput.value = selectedDoctorId;
    confirmDoctorEl.textContent = doctors.find(d=>d.id===selectedDoctorId)?.name ?? 'Sin preferencia';
    confirmTypeEl.textContent = specializationSelect?.options[specializationSelect.selectedIndex]?.text ?? 'Especialización';
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
  // NO mostramos lista de doctores hasta que el usuario pulse Buscar
  renderDoctors('', false);
  renderSchedule();
  updateStep2State();
});
