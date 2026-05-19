// =============================================
//  UNISPORT BOOKING - app.js
// =============================================

function toast(msg, tipo = 'success') {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const t = document.createElement('div');
    t.className = `toast ${tipo}`;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function iconoDeporte(d) {
    const m = { tenis:'🎾', fútbol:'⚽', futbol:'⚽', pádel:'🏓', padel:'🏓', baloncesto:'🏀', voleibol:'🏐' };
    return m[(d || '').toLowerCase()] || '🏅';
}

function fechaLegible(f) {
    if (!f) return '';
    const [y, m, d] = f.split('-');
    return `${d}/${m}/${y}`;
}

let cachePistas     = [];
let cacheMonitores  = [];
let cacheMateriales = [];

document.addEventListener('DOMContentLoaded', () => {
    const pagina = detectarPagina();
    if (pagina === 'index') {
        cargarPistas();
        cargarReservasProximas();
        initModal();
    }
    if (pagina === 'reservas') {
        cargarHistorial();
    }
});

function detectarPagina() {
    const path = window.location.pathname;
    if (path.includes('reservas')) return 'reservas';
    return 'index';
}

// ─── PISTAS ───────────────────────────────────────────────────
async function cargarPistas() {
    const contenedor = document.getElementById('listaPistas');
    try {
        const r = await fetch('api.php?action=pistas');
        const d = await r.json();
        if (!d.ok) throw new Error(d.msg);
        cachePistas = d.pistas;

        if (!d.pistas.length) {
            contenedor.innerHTML = '<p class="muted">No hay pistas disponibles.</p>';
            return;
        }

        contenedor.innerHTML = d.pistas.map(p => `
            <div class="pista-caja">
                <div class="caja-header">${iconoDeporte(p.deporte)} ${p.nombre}</div>
                <div class="caja-body">
                    <p class="desc">${p.deporte}</p>
                    <p class="precio">${parseFloat(p.precio).toFixed(2)} €/h</p>
                    <button class="btn-reservar" onclick="abrirModalConPista(${p.id})">Reservar</button>
                </div>
            </div>
        `).join('');
    } catch (e) {
        contenedor.innerHTML = `<p class="muted">Error al cargar pistas: ${e.message}</p>`;
    }
}

// ─── PRÓXIMAS RESERVAS ────────────────────────────────────────
async function cargarReservasProximas() {
    const contenedor = document.getElementById('listaReservas');
    try {
        const r = await fetch('api.php?action=reservas');
        const d = await r.json();
        if (!d.ok) throw new Error(d.msg);

        if (!d.reservas.length) {
            contenedor.innerHTML = '<p class="muted">No tienes reservas próximas.</p>';
            return;
        }

        contenedor.innerHTML = d.reservas.map(rv => `
            <div class="reserva-caja">
                <span class="sport-icon">${iconoDeporte(rv.deporte)}</span>
                <strong>${rv.pista}</strong>
                <span class="fecha">${fechaLegible(rv.fecha)} · ${rv.hora_inicio.slice(0,5)}–${rv.hora_fin.slice(0,5)}</span>
                ${rv.monitor_nombre ? `<span class="muted">Monitor: ${rv.monitor_nombre}</span>` : ''}
                <span class="muted precio-resaltado">${parseFloat(rv.precio_final).toFixed(2)} €</span>
                <button class="btn-cancelar" onclick="cancelarReserva(${rv.id})">Cancelar</button>
            </div>
        `).join('');
    } catch (e) {
        contenedor.innerHTML = `<p class="muted">Error: ${e.message}</p>`;
    }
}

// ─── HISTORIAL ────────────────────────────────────────────────
async function cargarHistorial() {
    const tbody = document.getElementById('cuerpoTablaReservas');
    if (!tbody) return;

    try {
        const r = await fetch('api.php?action=historial');
        const d = await r.json();
        if (!d.ok) throw new Error(d.msg);

        if (!d.reservas.length) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#6c757d;">No tienes reservas registradas.</td></tr>';
            return;
        }

        tbody.innerHTML = d.reservas.map(rv => {
            const estadoClass = rv.estado_pago === 'pagado'    ? 'etiqueta-estado-ok'
                              : rv.estado_pago === 'cancelada' ? 'etiqueta-estado-mal'
                              : 'etiqueta-estado-pend';

            const cancelBtn = rv.estado_pago !== 'cancelada'
                ? `<button class="btn-cancelar" onclick="cancelarReservaHistorial(${rv.id})">Cancelar</button>`
                : '—';

            return `
                <tr>
                    <td>${rv.id}</td>
                    <td>${rv.pista}<span class="etiqueta-info">${rv.deporte}</span></td>
                    <td>${fechaLegible(rv.fecha)}</td>
                    <td>${rv.hora_inicio.slice(0,5)} – ${rv.hora_fin.slice(0,5)}</td>
                    <td>${rv.monitor_nombre || '—'}</td>
                    <td>${rv.materiales || '—'}</td>
                    <td class="precio-resaltado">${parseFloat(rv.precio_final).toFixed(2)} €</td>
                    <td><span class="${estadoClass}">${rv.estado_pago}</span></td>
                    <td>${cancelBtn}</td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="9" style="color:red;">Error: ${e.message}</td></tr>`;
    }
}

// ─── CANCELAR (index) ─────────────────────────────────────────
async function cancelarReserva(id) {
    if (!confirm('¿Seguro que quieres cancelar esta reserva? Se te devolverá el importe completo.')) return;
    const fd = new FormData();
    fd.append('action', 'cancelar');
    fd.append('reserva_id', id);
    const r = await fetch('api.php', { method: 'POST', body: fd });
    const d = await r.json();
    toast(d.msg, d.ok ? 'success' : 'error');
    if (d.ok) {
        actualizarSaldoUI(d.saldo);
        cargarReservasProximas();
    }
}

// ─── CANCELAR (historial) ─────────────────────────────────────
async function cancelarReservaHistorial(id) {
    if (!confirm('¿Cancelar esta reserva? Se te devolverá el importe completo.')) return;
    const fd = new FormData();
    fd.append('action', 'cancelar');
    fd.append('reserva_id', id);
    const r = await fetch('api.php', { method: 'POST', body: fd });
    const d = await r.json();
    toast(d.msg, d.ok ? 'success' : 'error');
    if (d.ok) cargarHistorial();
}

// ─── MODAL ────────────────────────────────────────────────────
async function initModal() {
    const [rPistas, rMonitores, rMateriales] = await Promise.all([
        fetch('api.php?action=pistas').then(r => r.json()),
        fetch('api.php?action=monitores').then(r => r.json()),
        fetch('api.php?action=materiales').then(r => r.json()),
    ]);

    cachePistas     = rPistas.pistas       || [];
    cacheMonitores  = rMonitores.monitores  || [];
    cacheMateriales = rMateriales.materiales || [];

    const selPista = document.getElementById('selectPista');
    selPista.innerHTML = cachePistas.map(p =>
        `<option value="${p.id}" data-precio="${p.precio}">${p.nombre} (${p.deporte}) – ${parseFloat(p.precio).toFixed(2)} €/h</option>`
    ).join('');

    // Monitores: deshabilitar los no disponibles
    const selMonitor = document.getElementById('selectMonitor');
    selMonitor.innerHTML = '<option value="0" data-precio="0">Sin monitor</option>' +
        cacheMonitores.map(m => {
            if (parseInt(m.disponibilidad) === 0) {
                return `<option value="${m.id}" disabled style="color:#bbb;">${m.nombre} (${m.especialidad}) – No disponible</option>`;
            }
            return `<option value="${m.id}" data-precio="${m.precio}">${m.nombre} (${m.especialidad}) – ${parseFloat(m.precio).toFixed(2)} €/sesión</option>`;
        }).join('');

    const selMaterial = document.getElementById('selectMaterial');
    selMaterial.innerHTML = '<option value="0" data-precio="0">Sin material</option>' +
        cacheMateriales.map(m =>
            `<option value="${m.id}" data-precio="${m.precio}">${m.nombre} – ${parseFloat(m.precio).toFixed(2)} €/ud</option>`
        ).join('');

    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('inputFecha').min   = hoy;
    document.getElementById('inputFecha').value = hoy;

    actualizarResumen();
}

function abrirModal() {
    document.getElementById('modalReserva').classList.add('activo');
    document.getElementById('modalMsg').classList.remove('visible', 'error', 'success');
}

function abrirModalConPista(idPista) {
    abrirModal();
    const sel = document.getElementById('selectPista');
    if (sel) sel.value = idPista;
    actualizarResumen();
}

function cerrarModal() {
    document.getElementById('modalReserva').classList.remove('activo');
}

function actualizarResumen() {
    const selPista    = document.getElementById('selectPista');
    const inputIni    = document.getElementById('inputHoraInicio');
    const inputFin    = document.getElementById('inputHoraFin');
    const selMonitor  = document.getElementById('selectMonitor');
    const selMaterial = document.getElementById('selectMaterial');
    const cantidad    = parseInt(document.getElementById('cantidadMaterial')?.value || '1', 10);

    if (!selPista || !inputIni || !inputFin) return;

    const precioPista   = parseFloat(selPista.selectedOptions[0]?.dataset.precio    || 0);
    const precioMonitor = parseFloat(selMonitor?.selectedOptions[0]?.dataset.precio || 0);
    const precioMat     = parseFloat(selMaterial?.selectedOptions[0]?.dataset.precio|| 0);

    const ini = inputIni.value;
    const fin = inputFin.value;
    let horas = 0;
    if (ini && fin && fin > ini) {
        horas = (new Date('1970-01-01T' + fin) - new Date('1970-01-01T' + ini)) / 3600000;
    }

    const costePista    = precioPista * horas;
    const costeMonitor  = precioMonitor;
    const costeMaterial = precioMat * cantidad;
    const total         = costePista + costeMonitor + costeMaterial;

    document.getElementById('resumenPista').textContent    = costePista.toFixed(2)    + ' €';
    document.getElementById('resumenMonitor').textContent  = costeMonitor.toFixed(2)  + ' €';
    document.getElementById('resumenMaterial').textContent = costeMaterial.toFixed(2) + ' €';
    document.getElementById('resumenTotal').textContent    = total.toFixed(2)         + ' €';
}

async function confirmarReserva() {
    const msgEl   = document.getElementById('modalMsg');
    const pistaId = document.getElementById('selectPista').value;
    const fecha   = document.getElementById('inputFecha').value;
    const horaIni = document.getElementById('inputHoraInicio').value;
    const horaFin = document.getElementById('inputHoraFin').value;
    const monId   = document.getElementById('selectMonitor').value;
    const matId   = document.getElementById('selectMaterial').value;
    const cantidad= document.getElementById('cantidadMaterial').value;

    if (!fecha || !horaIni || !horaFin) {
        msgEl.className = 'alerta error visible';
        msgEl.textContent = 'Rellena fecha y horario.';
        return;
    }
    if (horaFin <= horaIni) {
        msgEl.className = 'alerta error visible';
        msgEl.textContent = 'La hora de fin debe ser posterior a la de inicio.';
        return;
    }

    const fd = new FormData();
    fd.append('action',      'reservar');
    fd.append('pista_id',    pistaId);
    fd.append('fecha',       fecha);
    fd.append('hora_inicio', horaIni);
    fd.append('hora_fin',    horaFin);
    fd.append('monitor_id',  monId);
    fd.append('material_id', matId);
    fd.append('cantidad',    cantidad);

    const r = await fetch('api.php', { method: 'POST', body: fd });
    const d = await r.json();

    if (d.ok) {
        cerrarModal();
        toast(d.msg, 'success');
        actualizarSaldoUI(d.saldo);
        cargarReservasProximas();
    } else {
        msgEl.className = 'alerta error visible';
        msgEl.textContent = d.msg;
    }
}

function actualizarSaldoUI(saldo) {
    const el = document.getElementById('saldoDisplay');
    if (el) el.textContent = saldo;
}
