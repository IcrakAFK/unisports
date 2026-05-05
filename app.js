// =============================================
//  UNISPORT BOOKING - app.js
// =============================================

const API = 'api.php';

// Iconos por deporte
const iconos = {
  tenis:      '🎾',
  fútbol:     '⚽',
  futbol:     '⚽',
  pádel:      '🏓',
  padel:      '🏓',
  baloncesto: '🏀',
  voleibol:   '🏐',
};

function iconoDeporte(deporte) {
  return iconos[(deporte || '').toLowerCase()] || '🏅';
}

// ─── SALDO ────────────────────────────────────────────────────
function actualizarSaldo() {
  fetch(`${API}?action=saldo`)
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        document.getElementById('saldoDisplay').textContent = data.saldo;
      }
    });
}

// Actualizar saldo cada 30 segundos
setInterval(actualizarSaldo, 30000);

// ─── PRÓXIMAS RESERVAS ────────────────────────────────────────
function cargarReservas() {
  const contenedor = document.getElementById('listaReservas');
  fetch(`${API}?action=reservas`)
    .then(r => r.json())
    .then(data => {
      if (!data.ok || data.reservas.length === 0) {
        contenedor.innerHTML = '<div class="col-12"><p class="text-muted">No tienes próximas reservas.</p></div>';
        return;
      }
      contenedor.innerHTML = data.reservas.map(r => `
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card reserva-card shadow-sm p-3">
            <span class="sport-icon">${iconoDeporte(r.deporte)}</span>
            <div class="fw-bold mt-1">${r.pista}</div>
            <div class="text-muted small">
              ${formatearFecha(r.fecha)} &nbsp;|&nbsp; ${r.hora_inicio.slice(0,5)} – ${r.hora_fin.slice(0,5)} h
            </div>
            <button class="btn btn-sm mt-2 btn-cancelar"
              style="background:#dc3545;color:#fff;border-radius:0;font-size:.8rem"
              data-id="${r.id}">
              Cancelar
            </button>
          </div>
        </div>
      `).join('');

      // Eventos cancelar
      contenedor.querySelectorAll('.btn-cancelar').forEach(btn => {
        btn.addEventListener('click', () => cancelarReserva(btn.dataset.id));
      });
    });
}

function formatearFecha(fechaStr) {
  const [y, m, d] = fechaStr.split('-');
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return `${parseInt(d)} ${meses[parseInt(m)-1]}`;
}

// ─── CANCELAR RESERVA ────────────────────────────────────────
function cancelarReserva(reservaId) {
  if (!confirm('¿Seguro que quieres cancelar esta reserva? Se te devolverá el importe.')) return;

  const body = new FormData();
  body.append('action', 'cancelar');
  body.append('reserva_id', reservaId);

  fetch(API, { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      mostrarToast(data.msg, data.ok ? 'success' : 'danger');
      if (data.ok) {
        document.getElementById('saldoDisplay').textContent = data.saldo;
        cargarReservas();
      }
    });
}

// ─── PISTAS DISPONIBLES ───────────────────────────────────────
function cargarPistas() {
  const contenedor = document.getElementById('listaPistas');
  fetch(`${API}?action=pistas`)
    .then(r => r.json())
    .then(data => {
      if (!data.ok || data.pistas.length === 0) {
        contenedor.innerHTML = '<div class="col-12"><p class="text-muted">No hay pistas disponibles.</p></div>';
        return;
      }
      contenedor.innerHTML = data.pistas.map(p => `
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="card pista-card shadow-sm">
            <div class="card-header">${iconoDeporte(p.deporte)} ${p.nombre}</div>
            <div class="card-body">
              <p class="mb-1 text-muted small">${p.descripcion || ''}</p>
              <p class="fw-bold mb-3">${parseFloat(p.precio).toFixed(2)} € / hora</p>
              <button class="btn btn-reservar w-100 btn-abrir-modal"
                data-id="${p.id}" data-nombre="${p.nombre}">
                Reservar
              </button>
            </div>
          </div>
        </div>
      `).join('');

      // Click en "Reservar" → abrir modal con pista preseleccionada
      contenedor.querySelectorAll('.btn-abrir-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          preseleccionarPista(btn.dataset.id, btn.dataset.nombre);
          new bootstrap.Modal(document.getElementById('modalReserva')).show();
        });
      });
    });
}

// ─── MODAL: NUEVA RESERVA ────────────────────────────────────
function cargarSelectPistas() {
  fetch(`${API}?action=pistas`)
    .then(r => r.json())
    .then(data => {
      const sel = document.getElementById('selectPista');
      sel.innerHTML = (data.pistas || []).map(p =>
        `<option value="${p.id}">${p.nombre} – ${parseFloat(p.precio).toFixed(2)} €/h</option>`
      ).join('');
    });
}

function preseleccionarPista(id, nombre) {
  const sel = document.getElementById('selectPista');
  for (const opt of sel.options) {
    if (opt.value == id) { opt.selected = true; break; }
  }
}

// Fecha mínima = hoy
document.addEventListener('DOMContentLoaded', () => {
  const inputFecha = document.getElementById('inputFecha');
  if (inputFecha) {
    inputFecha.min = new Date().toISOString().split('T')[0];
    inputFecha.value = new Date().toISOString().split('T')[0];
  }

  cargarReservas();
  cargarPistas();
  cargarSelectPistas();
  actualizarSaldo();
});

// Confirmar reserva desde el modal
document.getElementById('btnConfirmarReserva')?.addEventListener('click', () => {
  const pista_id   = document.getElementById('selectPista').value;
  const fecha      = document.getElementById('inputFecha').value;
  const hora_inicio = document.getElementById('inputHoraIni').value;
  const hora_fin   = document.getElementById('inputHoraFin').value;
  const msgBox     = document.getElementById('modalMsg');

  if (!pista_id || !fecha || !hora_inicio || !hora_fin) {
    mostrarModalMsg('Completa todos los campos.', 'danger');
    return;
  }
  if (hora_fin <= hora_inicio) {
    mostrarModalMsg('La hora de fin debe ser posterior a la de inicio.', 'danger');
    return;
  }

  const body = new FormData();
  body.append('action',      'reservar');
  body.append('pista_id',    pista_id);
  body.append('fecha',       fecha);
  body.append('hora_inicio', hora_inicio);
  body.append('hora_fin',    hora_fin);

  fetch(API, { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalReserva')).hide();
        document.getElementById('saldoDisplay').textContent = data.saldo;
        cargarReservas();
        cargarPistas();
        mostrarToast(data.msg, 'success');
      } else {
        mostrarModalMsg(data.msg, 'danger');
      }
    });
});

// ─── HELPERS UI ──────────────────────────────────────────────
function mostrarModalMsg(texto, tipo) {
  const box = document.getElementById('modalMsg');
  box.className = `alert alert-${tipo}`;
  box.textContent = texto;
}

function mostrarToast(texto, tipo = 'success') {
  // Toast Bootstrap dinámico
  const id = 'toast_' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center text-bg-${tipo} border-0 show"
      role="alert" style="border-radius:0;min-width:260px">
      <div class="d-flex">
        <div class="toast-body fw-bold">${texto}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;

  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:70px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px';
    document.body.appendChild(container);
  }
  container.insertAdjacentHTML('beforeend', html);
  setTimeout(() => document.getElementById(id)?.remove(), 4000);
}
