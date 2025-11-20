  // public/js/agendar-general.js - CONEXIÓN BD
  document.addEventListener('DOMContentLoaded', () => {
    console.log('agendar-general.js cargado - CON BD');

    // Carga datos de reprogramación
    let citaReprogramarId = null;
    const datosReprogramar = sessionStorage.getItem('citaReprogramar');
    if (datosReprogramar) {
      const citaData = JSON.parse(datosReprogramar);
      citaReprogramarId = citaData.id;
      console.log('Modo reprogramación activado para cita:', citaReprogramarId);
    }
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
    let selectedDate = null;
    let selectedDoctorId = undefined;
    let selectedSlot = null; // {id: number, hora: "HH:MM:SS"}
    
    // ----- datos desde BD -----
    let doctors = [];
    let availableSlots = [];

    // ==== utilidades ====
    function pad2(n){ return String(n).padStart(2,'0'); }
    function formatDateISO(d){
      return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
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

        if (datosReprogramar && !selectedDate) {
      const citaData = JSON.parse(datosReprogramar);
      if (citaData.fechaOriginal) {
        const fechaOriginal = new Date(citaData.fechaOriginal);
        if (fechaOriginal.getFullYear() === year && fechaOriginal.getMonth() === month) {
          selectedDate = fechaOriginal;
            }
          }
        }

        if (cellDate.getTime() < todayStart.getTime()) {
          btn.disabled = true;
          btn.classList.add('text-muted');
        } else {
          btn.addEventListener('click', async () => {
            selectedDate = cellDate;
            // visual calendario
            grid.querySelectorAll('button').forEach(b => b.classList.remove('btn-primary', 'text-white'));
            btn.classList.add('btn-primary', 'text-white');
            selectedSlot = null;

            // Cargar horarios disponibles desde BD
            await loadAvailableSlots();
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
      // botón continuar habilitado si hay fecha y slot
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

    // ==== doctores / búsqueda (CONEXIÓN BD) ====
    async function loadDoctors(filter = '') {
      try {
        const url = filter ? 
          `api/get_medicos.php?tipo=general&filter=${encodeURIComponent(filter)}` : 
          'api/get_medicos.php?tipo=general';
        
        const response = await fetch(url);
        if (!response.ok) throw new Error('Error al cargar médicos');
        const data = await response.json();

        // Normalizar datos
        doctors = (Array.isArray(data) ? data : []).map(item => {
          const id = item.id_medico ?? item.id ?? item.id_doctor ?? item.idMedico;
          const nombre_full = item.nombre_medico ?? ((item.nombre || '') + (item.apellido ? ' ' + item.apellido : '')).trim();
          const [nombre, ...rest] = (nombre_full || '').split(' ');
          const apellido = rest.join(' ');
          return {
            id: id,
            id_medico: id,
            name: nombre_full || item.nombre || '',
            nombre: nombre_full || item.nombre || '',
            nombre_separado: nombre || '',
            apellido: item.apellido ?? apellido ?? '',
            speciality: item.nombre_especialidad || 'Medicina General',
            descripcion_especialidad: item.descripcion_especialidad ?? item.nombre_especialidad ?? 'Medicina General',
            id_especialidad: item.id_especialidad ?? item.idEspecialidad ?? item.especialidad ?? ''
          };
        });

        if (datosReprogramar && !filter) {
        const citaData = JSON.parse(datosReprogramar);
        if (citaData.medicoId) {
          selectedDoctorId = citaData.medicoId;
          console.log('Médico preseleccionado para reprogramación:', selectedDoctorId);
        }
      }

        // mostrar resultados inmediatamente
        renderDoctors(filter, true);
      } catch (error) {
        console.error('Error cargando médicos:', error);
        doctorListEl.innerHTML = '<div class="text-danger">Error cargando médicos</div>';
        doctors = [];
      }
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
      const filtered = (q === '') ? doctors.slice() : doctors.filter(d =>
        (d.name || '').toLowerCase().includes(q) ||
        (d.nombre || '').toLowerCase().includes(q) ||
        (d.apellido || '').toLowerCase().includes(q) ||
        (d.speciality || '').toLowerCase().includes(q)
      );

      // Opción "Sin preferencia"
      const anyItem = document.createElement('button');
      anyItem.type = 'button';
      anyItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
      anyItem.dataset.doctorId = 'any';
      anyItem.innerHTML = `<div><strong>Sin preferencia</strong><div class="small text-muted">Asignar cualquier profesional disponible</div></div>`;
      anyItem.addEventListener('click', () => {
        selectedDoctorId = null;
        selectedSlot = null;
        highlightSelectedDoctor();
        loadAvailableSlots();
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
        btn.innerHTML = `<div><strong>${d.name}</strong><div class="small text-muted">${d.speciality || 'Medicina General'}</div></div>`;

        btn.addEventListener('click', () => {
          selectedDoctorId = d.id;
          highlightSelectedDoctor();
          selectedSlot = null;
          loadAvailableSlots();
          updateStep2State();
        });

        doctorListEl.appendChild(btn);
      });

      highlightSelectedDoctor();
    }

    // eventos de búsqueda
    btnDoctorSearch?.addEventListener('click', () => {
      const val = (doctorSearchInput.value || '').trim();
      loadDoctors(val);
    });

    doctorSearchInput?.addEventListener('keyup', (e) => {
      if (e.key === 'Enter') {
        const val = (doctorSearchInput.value || '').trim();
        loadDoctors(val);
      }
    });

    // Omitir -> seleccionar "Sin preferencia"
    omitDoctorBtn?.addEventListener('click', () => {
      selectedDoctorId = null;
      selectedSlot = null;
      loadDoctors(doctorSearchInput.value || '');
      highlightSelectedDoctor();
      loadAvailableSlots();
      updateStep2State();
    });

    // ==== horarios (CONEXIÓN BD) ====
    async function loadAvailableSlots() {
      if (!selectedDate) {
        scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un día.</div>';
        availableSlots = [];
        return;
      }

      const dateISO = formatDateISO(selectedDate);

      try {
        let url = `api/get_horarios.php?fecha=${dateISO}&tipo=general`;
        // enviamos id_medico si hay uno específico
        if (selectedDoctorId && selectedDoctorId !== null) {
          url += `&id_medico=${encodeURIComponent(selectedDoctorId)}`;
        }

        const response = await fetch(url);
        if (!response.ok) throw new Error('Error al cargar horarios');

        availableSlots = await response.json();
        renderSchedule();
      } catch (error) {
        console.error('Error cargando horarios:', error);
        scheduleGrid.innerHTML = '<div class="text-danger">Error cargando horarios</div>';
        availableSlots = [];
      }
    }

    function renderSchedule(){
      scheduleGrid.innerHTML = '';

      if (!selectedDate){
        scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un día.</div>';
        return;
      }

      if (selectedDoctorId === undefined){
        scheduleGrid.innerHTML = '<div class="text-muted small">Seleccione primero un médico (presione Buscar) o elija "Sin preferencia".</div>';
        return;
      }

      if (!Array.isArray(availableSlots) || availableSlots.length === 0) {
        scheduleGrid.innerHTML = '<div class="text-muted small">No hay horarios disponibles para esta fecha.</div>';
        return;
      }

      const fragment = document.createDocumentFragment();

      // Filtrar horarios únicos por hora_inicio
      const horariosUnicos = [];
      const horasVistas = new Set();
      
      availableSlots.forEach(slot => {
        const hora = slot.hora || slot.hora_inicio;
        const horaKey = hora.substring(0, 5); // "HH:MM"
        
        if (!horasVistas.has(horaKey)) {
          horasVistas.add(horaKey);
          horariosUnicos.push(slot);
        }
      });

      horariosUnicos.forEach(slot => {
        const timeLabel = (slot.hora || slot.hora_inicio || '').substring(0, 5); // HH:MM
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = timeLabel;
        btn.style.minWidth = '90px';
        btn.className = 'btn btn-sm m-1 btn-outline-success';
        btn.dataset.slotId = slot.id;
        btn.dataset.medicoId = slot.id_medico;

        btn.addEventListener('click', () => {
          scheduleGrid.querySelectorAll('button').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-success');
          });
          btn.classList.remove('btn-outline-success');
          btn.classList.add('btn-primary');
          selectedSlot = { 
            id: slot.id, 
            hora: slot.hora || slot.hora_inicio,
            medico_id: slot.id_medico || slot.medico_id
          };
          
          // Si es "Sin preferencia", asignar el médico automáticamente
          if (selectedDoctorId === null && slot.id_medico) {
            selectedDoctorId = slot.id_medico;
            highlightSelectedDoctor();
          }
          
          updateStep2State();
        });

        fragment.appendChild(btn);
      });

      scheduleGrid.appendChild(fragment);

      // Restaurar selección si existe
      if (selectedSlot) {
        const exists = Array.from(scheduleGrid.querySelectorAll('button'))
          .find(b => parseInt(b.dataset.slotId) === Number(selectedSlot.id));
        if (exists) {
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

      // Para "Sin preferencia", usar el médico del slot seleccionado
      if (selectedDoctorId === null) {
        if (selectedSlot.medico_id) {
          selectedDoctorId = selectedSlot.medico_id;
        } else {
          return alert('No hay profesionales disponibles en el horario seleccionado.');
        }
      }

      confirmDateInput.value = formatDateISO(selectedDate);
      confirmDoctorIdInput.value = selectedDoctorId;

      const doctor = doctors.find(d => Number(d.id) === Number(selectedDoctorId));
      confirmDoctorEl.textContent = doctor ? doctor.name : 'Sin preferencia';
      confirmTypeEl.textContent = 'General';
      confirmScheduleEl.textContent = `${selectedDate.toLocaleDateString()} · ${(selectedSlot.hora||'').substring(0,5)}`;

      if (step2TabTrigger && !step2TabTrigger.disabled) new bootstrap.Tab(step2TabTrigger).show();
    });

    backBtn?.addEventListener('click', () => {
      const step1 = document.getElementById('step1-tab');
      if (step1) new bootstrap.Tab(step1).show();
    });

    confirmBtn?.addEventListener('click', async () => {
    // 1. Validaciones Básicas
    if (!selectedDate || !selectedSlot || !selectedDoctorId) {
      alert('Faltan datos para confirmar la cita.');
      return;
    }

    try {
      // === AQUÍ ESTÁ LA LÓGICA CENTRAL ===
      
      // Opción A: ES UNA REPROGRAMACIÓN
      if (citaReprogramarId) {
        console.log("Procesando Reprogramación...");
        
        const reprogramData = {
            cita_id: citaReprogramarId,         // La cita vieja
            nuevo_horario_id: selectedSlot.id   // El nuevo horario elegido
        };

        // Llamamos a la API que hace TODO el trabajo sucio (cancelar vieja + crear nueva)
        const response = await fetch('api/reprogramar_cita.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reprogramData)
        });

        const result = await response.json();

        if (result.success) {
            // Limpiamos la sesión para que la próxima vez sea una cita normal
            sessionStorage.removeItem('citaReprogramar');
            
            // Notificaciones visuales
            if (window.notificationManager) window.notificationManager.showToast('¡Cita reprogramada exitosamente!', 'success');
            if (window.notifyNewAppointment) window.notifyNewAppointment();

            const modalEl = document.getElementById('confirmModal');
            if (modalEl) new bootstrap.Modal(modalEl).show();
        } else {
            alert('Error al reprogramar: ' + (result.message || result.error));
        }

      } 
      // Opción B: ES UNA CITA NUEVA NORMAL
      else {
        console.log("Procesando Cita Nueva...");
        
        const appointmentData = {
          paciente_id: await getPacienteId(),
          medico_id: selectedDoctorId,
          horario_id: selectedSlot.id,
          tipo_cita: 'general'
        };
  
        const response = await fetch('api/create_appointment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(appointmentData)
        });
  
        const result = await response.json();
  
        if (result.success) {
          if (window.notificationManager) window.notificationManager.showToast('¡Cita general agendada exitosamente!', 'success');
          if (window.notifyNewAppointment) window.notifyNewAppointment();
          
          const modalEl = document.getElementById('confirmModal');
          if (modalEl) new bootstrap.Modal(modalEl).show();
        } else {
          alert('Error al agendar la cita: ' + (result.message || JSON.stringify(result)));
        }
      }

    } catch (error) {
      console.error('Error:', error);
      alert('Error al conectar con el servidor');
    }
  });

    // ==== función auxiliar para obtener ID del paciente ====
    async function getPacienteId() {
      if (typeof currentPacienteId !== 'undefined') {
        return currentPacienteId;
      }
      try {
        const response = await fetch('api/get_current_patient.php');
        const data = await response.json();
        return data.paciente_id;
      } catch (error) {
        console.error('Error obteniendo ID del paciente:', error);
        return 1; // Fallback para testing
      }
    }

    // ==== inicialización ====
  renderCalendar(currentMonth);
  // Si es reprogramación, cargar doctores automáticamente
  if (datosReprogramar) {
    loadDoctors('');
  } else {
    renderDoctors('', false); // NO mostrar lista hasta que usuario presione "Buscar"
  }
  updateStep2State();
  });