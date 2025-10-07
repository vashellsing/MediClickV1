// public/js/agendar-general.js
document.addEventListener('DOMContentLoaded', () => {
  console.log('agendar-general.js cargado');

  // ----- referencias DOM (deben coincidir con tu HTML) -----
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

  // ...existing code...
const specializationSelect = document.getElementById('specializationSelect');

specializationSelect?.addEventListener('change', () => {
  const selectedSpecialization = specializationSelect.options[specializationSelect.selectedIndex].text.trim().toLowerCase();
  renderDoctors(selectedSpecialization);
});
// ...existing code...

  // ----- estado -----
  let selectedDate = null;      // Date object
  let selectedDoctorId = null;  // number
  let selectedSlot = null;      // "HH:MM"

  // ----- datos de ejemplo (médicos) -----
  const doctors = [
    { id: 1, name: 'Dra. Laura Méndez', speciality: 'Neurología' },
    { id: 2, name: 'Dr. Carlos Ruiz', speciality: 'Pediatría' },
    { id: 3, name: 'Dra. Natalia Gómez', speciality: 'Odontología' },
    { id: 4, name: 'Dr. Andrés Pérez', speciality: 'Dermatología' },
    { id: 5, name: 'Dr. Dana Estrada', speciality: 'Cardiología' }
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
    // Aprox ~1/4 ocupados
    return (sum % 4) === 0;
  }

  // ==== calendario ====
  let today = new Date();
  let currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);



function renderCalendar(date) {
  calendarGrid.innerHTML = '';
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDate = new Date(year, month + 1, 0).getDate();

  currentMonthLabel.textContent = date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

  const startOffset = (firstDay.getDay() + 6) % 7; // lunes = 0
  const totalCells = startOffset + lastDate;
  const rows = Math.ceil(totalCells / 7);

  const grid = document.createElement('div');
  grid.className = 'calendar-grid d-grid gap-2';
  grid.style.gridTemplateColumns = 'repeat(7, 1fr)';

  // celdas vacías antes del primer día
  for (let i = 0; i < startOffset; i++) {
    const empty = document.createElement('div');
    grid.appendChild(empty);
  }

  // días del mes
  for (let d = 1; d <= lastDate; d++) {
    const cellDate = new Date(year, month, d);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-secondary w-100 py-2';
    btn.textContent = d;
    btn.dataset.date = formatDateISO(cellDate);

    // marcar hoy
    if (cellDate.toDateString() === today.toDateString()) {
      btn.classList.add('fw-bold', 'border-primary');
    }

    // al hacer clic
    btn.addEventListener('click', () => {
      selectedDate = cellDate;
      grid.querySelectorAll('button').forEach(b => b.classList.remove('btn-primary', 'text-white'));
      btn.classList.add('btn-primary', 'text-white');
      selectedSlot = null;
      renderSchedule();
    });

    grid.appendChild(btn);
  }

  // completar hasta múltiplo de 7
  while (grid.children.length % 7 !== 0) {
    const filler = document.createElement('div');
    grid.appendChild(filler);
  }

  calendarGrid.appendChild(grid);

  // mantener resaltado si ya hay día seleccionado
  if (selectedDate && selectedDate.getFullYear() === year && selectedDate.getMonth() === month) {
    const selBtn = calendarGrid.querySelector(`button[data-date="${formatDateISO(selectedDate)}"]`);
    if (selBtn) selBtn.classList.add('btn-primary', 'text-white');
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

  // ==== doctores ====
  function renderDoctors(filter = ''){
    doctorListEl.innerHTML = '';
    const q = (filter||'').trim().toLowerCase();
    const filtered = doctors.filter(d => d.name.toLowerCase().includes(q) || d.speciality.toLowerCase().includes(q));

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
        // visual
        doctorListEl.querySelectorAll('.list-group-item').forEach(x => {
          x.classList.remove('active','bg-primary','text-white');
        });
        btn.classList.add('active','bg-primary','text-white');
        selectedSlot = null;
        renderSchedule();
      });

      doctorListEl.appendChild(btn);
    });
  }

  btnDoctorSearch?.addEventListener('click', () => renderDoctors(doctorSearchInput.value));
  doctorSearchInput?.addEventListener('keyup', (e) => { if (e.key === 'Enter') renderDoctors(doctorSearchInput.value); });

  // Omitir -> seleccionar médico aleatorio
  omitDoctorBtn?.addEventListener('click', () => {
    if (doctors.length === 0) return;
    const rand = doctors[Math.floor(Math.random()*doctors.length)];
    selectedDoctorId = rand.id;
    doctorListEl.querySelectorAll('.list-group-item').forEach(x => x.classList.remove('active','bg-primary','text-white'));
    const btn = Array.from(doctorListEl.children).find(c => c.dataset && Number(c.dataset.doctorId) === rand.id);
    if (btn) {
      btn.classList.add('active','bg-primary','text-white');
      btn.scrollIntoView({behavior:'smooth',block:'center'});
    }
    selectedSlot = null;
    renderSchedule();
  });

  // ==== horarios (cada 20 minutos) ====
  function renderSchedule(){
    scheduleGrid.innerHTML = '';

    if (!selectedDate || !selectedDoctorId){
      scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un día y un médico.</div>';
      return;
    }

    const dateISO = formatDateISO(selectedDate);
    // intervalos 20 minutos desde 08:00 hasta 17:00 (incl 17:00)
    const start = 8*60; // minutos
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

      const occupied = isOccupiedDeterministic(dateISO, selectedDoctorId, t);
      if (occupied){
        btn.classList.add('btn-danger');
        btn.disabled = true;
      } else {
        btn.classList.add('btn-outline-success');
        btn.addEventListener('click', () => {
          // desmarcar otros
          scheduleGrid.querySelectorAll('button').forEach(b => {
            if (!b.disabled) {
              b.classList.remove('btn-primary');
              // dejar outline para disponibles
              b.classList.remove('btn-success');
              if (!b.classList.contains('btn-outline-success')) {
                b.classList.add('btn-outline-success');
              }
            }
          });
          // marcar seleccionado
          btn.classList.remove('btn-outline-success');
          btn.classList.add('btn-primary');
          selectedSlot = timeLabel;
        });
      }

      fragment.appendChild(btn);
    }

    scheduleGrid.appendChild(fragment);

    // si había slot seleccionado y todavía existe, volver a seleccionarlo
    if (selectedSlot){
      const exists = Array.from(scheduleGrid.querySelectorAll('button')).find(b => !b.disabled && b.textContent === selectedSlot);
      if (exists){
        exists.classList.remove('btn-outline-success');
        exists.classList.add('btn-primary');
      } else {
        selectedSlot = null;
      }
    }
  }

  // ==== confirmación y navegación de tabs ====
  continueBtn?.addEventListener('click', () => {
    if (!selectedDate) return alert('Seleccione una fecha.');
    if (!selectedDoctorId) return alert('Seleccione un médico (o presione Omitir).');
    if (!selectedSlot) return alert('Seleccione un horario.');

    confirmDateInput.value = formatDateISO(selectedDate);
    confirmDoctorIdInput.value = selectedDoctorId;
    confirmDoctorEl.textContent = doctors.find(d=>d.id===selectedDoctorId)?.name ?? 'Cualquiera';
    confirmTypeEl.textContent = 'Especialización';
    confirmScheduleEl.textContent = `${selectedDate.toLocaleDateString()} · ${selectedSlot}`;

    const step2TabTrigger = document.getElementById('step2-tab');
    if (step2TabTrigger) new bootstrap.Tab(step2TabTrigger).show();
  });

  backBtn?.addEventListener('click', () => {
    const step1 = document.getElementById('step1-tab');
    if (step1) new bootstrap.Tab(step1).show();
  });

  confirmBtn?.addEventListener('click', () => {
    const modalEl = document.getElementById('confirmModal');
    if (modalEl) new bootstrap.Modal(modalEl).show();
    // opcional: resetear selección
    // selectedDate = selectedDoctorId = selectedSlot = null;
    // renderCalendar(currentMonth); renderDoctors(); renderSchedule();
  });

  // ==== inicialización ====
  renderCalendar(currentMonth);
  renderDoctors();
  renderSchedule();
});
