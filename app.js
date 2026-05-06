// =============================================
//  UNISPORT BOOKING - app.js  (SIN BOOTSTRAP)
// =============================================

var API = 'api.php';

var iconos = {
  tenis:      '🎾',
  fútbol:     '⚽',
  futbol:     '⚽',
  pádel:      '🏓',
  padel:      '🏓',
  baloncesto: '🏀',
  voleibol:   '🏐'
};

function icono(deporte) {
  return iconos[(deporte || '').toLowerCase()] || '🏅';
}

// ---- MODAL ----
function abrirModal() {
  document.getElementById('modalReserva').classList.add('activo');
  document.getElementById('modalMsg').className = 'alerta';
  document.getElementById('modalMsg').textContent = '';
}

function cerrarModal() {
  document.getElementById('modalReserva').classList.remove('activo');
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
  if (e.target.id === 'modalReserva') cerrarModal();
});

// ---- SALDO ----
function actualizarSaldo() {
  fetch(API + '?action=saldo')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        document.getElementById('saldoDisplay').textContent = data.saldo;
      }
    });
}

setInterval(actualizarSaldo, 30000);

// ---- RESERVAS ----
function cargarReservas() {
  var contenedor = document.getElementById('listaReservas');
  fetch(API + '?action=reservas')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.ok || data.reservas.length === 0) {
        contenedor.innerHTML = '<p class="muted">No tienes próximas reservas.</p>';
        return;
      }
      var html = '';
      for (var i = 0; i < data.reservas.length; i++) {
        var r = data.reservas[i];
        html += '<div class="reserva-card">' +
          '<span class="sport-icon">' + icono(r.deporte) + '</span>' +
          '<strong>' + r.pista + '</strong>' +
          '<span class="fecha">' + formatarFecha(r.fecha) + ' | ' + r.hora_inicio.slice(0,5) + ' – ' + r.hora_fin.slice(0,5) + ' h</span>' +
          '<br><button class="btn-cancelar" onclick="cancelarReserva(' + r.id + ')">Cancelar</button>' +
          '</div>';
      }
      contenedor.innerHTML = html;
    });
}

function formatarFecha(f) {
  var partes = f.split('-');
  var meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return parseInt(partes[2]) + ' ' + meses[parseInt(partes[1]) - 1];
}

// ---- CANCELAR ----
function cancelarReserva(id) {
  if (!confirm('¿Cancelar esta reserva? Se te devolverá el importe.')) return;

  var datos = new FormData();
  datos.append('action', 'cancelar');
  datos.append('reserva_id', id);

  fetch(API, { method: 'POST', body: datos })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      mostrarToast(data.msg, data.ok ? 'success' : 'error');
      if (data.ok) {
        document.getElementById('saldoDisplay').textContent = data.saldo;
        cargarReservas();
      }
    });
}

// ---- PISTAS ----
function cargarPistas() {
  var contenedor = document.getElementById('listaPistas');
  fetch(API + '?action=pistas')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.ok || data.pistas.length === 0) {
        contenedor.innerHTML = '<p class="muted">No hay pistas disponibles.</p>';
        return;
      }
      var html = '';
      for (var i = 0; i < data.pistas.length; i++) {
        var p = data.pistas[i];
        html += '<div class="pista-card">' +
          '<div class="card-header">' + icono(p.deporte) + ' ' + p.nombre + '</div>' +
          '<div class="card-body">' +
          '<p class="desc">' + (p.descripcion || '') + '</p>' +
          '<p class="precio">' + parseFloat(p.precio).toFixed(2) + ' € / hora</p>' +
          '<button class="btn-reservar" onclick="abrirModalConPista(' + p.id + ', \'' + p.nombre.replace(/'/g,"\\'") + '\')">Reservar</button>' +
          '</div></div>';
      }
      contenedor.innerHTML = html;
    });
}

function cargarSelectPistas() {
  fetch(API + '?action=pistas')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var sel = document.getElementById('selectPista');
      sel.innerHTML = '';
      for (var i = 0; i < (data.pistas || []).length; i++) {
        var p = data.pistas[i];
        var opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.nombre + ' – ' + parseFloat(p.precio).toFixed(2) + ' €/h';
        sel.appendChild(opt);
      }
    });
}

function abrirModalConPista(id, nombre) {
  abrirModal();
  var sel = document.getElementById('selectPista');
  for (var i = 0; i < sel.options.length; i++) {
    if (sel.options[i].value == id) {
      sel.selectedIndex = i;
      break;
    }
  }
}

// ---- CONFIRMAR RESERVA ----
function confirmarReserva() {
  var pista_id    = document.getElementById('selectPista').value;
  var fecha       = document.getElementById('inputFecha').value;
  var hora_inicio = document.getElementById('inputHoraIni').value;
  var hora_fin    = document.getElementById('inputHoraFin').value;

  if (!pista_id || !fecha || !hora_inicio || !hora_fin) {
    mostrarMsgModal('Completa todos los campos.', 'error');
    return;
  }
  if (hora_fin <= hora_inicio) {
    mostrarMsgModal('La hora de fin debe ser posterior a la de inicio.', 'error');
    return;
  }

  var datos = new FormData();
  datos.append('action',      'reservar');
  datos.append('pista_id',    pista_id);
  datos.append('fecha',       fecha);
  datos.append('hora_inicio', hora_inicio);
  datos.append('hora_fin',    hora_fin);

  fetch(API, { method: 'POST', body: datos })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        cerrarModal();
        document.getElementById('saldoDisplay').textContent = data.saldo;
        cargarReservas();
        cargarPistas();
        mostrarToast(data.msg, 'success');
      } else {
        mostrarMsgModal(data.msg, 'error');
      }
    });
}

// ---- HELPERS ----
function mostrarMsgModal(texto, tipo) {
  var box = document.getElementById('modalMsg');
  box.textContent = texto;
  box.className = 'alerta ' + tipo + ' visible';
}

function mostrarToast(texto, tipo) {
  var cont = document.getElementById('toastContainer');
  var div = document.createElement('div');
  div.className = 'toast ' + tipo;
  div.textContent = texto;
  cont.appendChild(div);
  setTimeout(function() { div.remove(); }, 4000);
}

// ---- INICIO ----
document.addEventListener('DOMContentLoaded', function() {
  var hoy = new Date().toISOString().split('T')[0];
  document.getElementById('inputFecha').min   = hoy;
  document.getElementById('inputFecha').value = hoy;

  cargarReservas();
  cargarPistas();
  cargarSelectPistas();
  actualizarSaldo();
});