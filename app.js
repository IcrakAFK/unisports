//DETECCION PAGINA CORRECTA
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

    // Inicialización de Flatpickr integrada
    if (document.getElementById('calendarioFijo')) {
        flatpickr("#calendarioFijo", {
            inline: true,
            locale: "es",
            minDate: "today",
            dateFormat: "Y-m-d",
            monthSelectorType: "static", 
            yearSelectorType: "static", 
            
            onChange: function(fechasSeleccionadas, fechaTexto, instancia) {
                if (modalDatePicker) {
                    modalDatePicker.setDate(fechaTexto);
                }
                if (typeof actualizarPistasDisponibles === "function") {
                    actualizarPistasDisponibles(fechaTexto);
                }
            }
        });
    }

    if (document.getElementById('inputFecha')) {
        modalDatePicker = flatpickr("#inputFecha", {
            locale: "es",
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function(fechasSeleccionadas, fechaTexto, instancia) {
                if (typeof actualizarResumen === "function") {
                    actualizarResumen();
                }
            }
        });
    }
});

function detectarPagina() {
    const path = window.location.pathname;
    if (path.includes('reservas')) return 'reservas';
    return 'index';
}

//MENSAJE TEMPORAL
function mensajeTemporal(mensaje, tipo = 'success') {
    const contenedor = document.getElementById('mensajeTemporal');
    if (!contenedor) return;
    
    const elementoMensaje = document.createElement('div');
    elementoMensaje.className = `mensaje-temporal ${tipo}`; 
    elementoMensaje.textContent = mensaje;
    
    contenedor.appendChild(elementoMensaje); 
    
    setTimeout(() => {
        elementoMensaje.remove();
    }, 3500);
}

//FECHA
function fechaLegible(fechaTexto) {
    if (!fechaTexto) return '';
    const [anho, mes, dia] = fechaTexto.split('-');
    return `${dia}/${mes}/${anho}`;
}

let cachePistas = [];
let cacheMonitores = [];
let cacheMateriales = [];
let modalDatePicker;

//FECHA ACTIVA CALENDARIO
let fechaSeleccionada = new Date().toISOString().split('T')[0];

//PINCHAR DIA CALENDARIO (B)
function actualizarPistasDisponibles(fechaTexto) {
    fechaSeleccionada = fechaTexto;

    const titulo = document.getElementById('tituloPistas');
    if (titulo) {
        const [anho, mes, dia] = fechaTexto.split('-');
        const fechaHoy = new Date().toISOString().split('T')[0];
        titulo.textContent = fechaTexto === fechaHoy
            ? '🏟️ Pistas Disponibles Hoy'
            : `🏟️ Pistas Disponibles – ${dia}/${mes}/${anho}`;
    }
    cargarPistas();
}

//ICONOS
function iconoDeporte(d) {
    const icono = { tenis:'🎾', fútbol:'⚽', futbol:'⚽', pádel:'🏓', padel:'🏓', baloncesto:'🏀', voleibol:'🏐' };
    return icono[(d || '').toLowerCase()] || '🏅';
}

//PISTAS
async function cargarPistas() {
    const contenedor = document.getElementById('listaPistas');
    try {
        const respuesta = await fetch('api.php?action=pistas');
        const datos = await respuesta.json();
        if (!datos.ok) throw new Error(datos.mensaje);
        cachePistas = datos.pistas;

        if (!datos.pistas.length) {
            contenedor.innerHTML = '<p class="muted">No hay pistas disponibles.</p>';
            return;
        }

        contenedor.innerHTML = datos.pistas.map(pista => `
            <div class="pista-caja">
                <div class="caja-header">${iconoDeporte(pista.deporte)} ${pista.nombre}</div>
                <div class="caja-body">
                    <p class="desc">${pista.deporte}</p>
                    <p class="precio">${parseFloat(pista.precio).toFixed(2)} €/h</p>
                    <button class="btn-reservar" onclick="abrirModalConPista(${pista.id})">Reservar</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        contenedor.innerHTML = `<p class="muted">Error al cargar pistas: ${error.message}</p>`;
    }
}

//PROXIMAS RESERVAS
async function cargarReservasProximas() {
    const contenedor = document.getElementById('listaReservas');
    try {
        const respuesta = await fetch('api.php?action=reservas');
        const datos = await respuesta.json();
        if (!datos.ok) throw new Error(datos.mensaje);

        if (!datos.reservas.length) {
            contenedor.innerHTML = '<p class="muted">No tienes reservas próximas.</p>';
            return;
        }

        contenedor.innerHTML = datos.reservas.map(reserva => `
            <div class="reserva-caja">
                <span class="sport-icon">${iconoDeporte(reserva.deporte)}</span>
                <strong>${reserva.pista}</strong>
                <span class="fecha">${fechaLegible(reserva.fecha)} · ${reserva.hora_inicio.slice(0,5)}–${reserva.hora_fin.slice(0,5)}</span>
                ${reserva.monitor_nombre ? `<span class="muted">Monitor: ${reserva.monitor_nombre}</span>` : ''}
                <span class="muted precio-resaltado">${parseFloat(reserva.precio_final).toFixed(2)} €</span>
                <button class="btn-cancelar" onclick="cancelarReserva(${reserva.id})">Cancelar</button>
            </div>
        `).join('');
    } catch (error) {
        contenedor.innerHTML = `<p class="muted">Error: ${error.message}</p>`;
    }
}

//HISTORIAL
async function cargarHistorial() {
    const cuerpoTabla = document.getElementById('cuerpoTablaReservas');
    if (!cuerpoTabla) return;

    try {
        const respuesta = await fetch('api.php?action=historial');
        const datos = await respuesta.json();
        if (!datos.ok) throw new Error(datos.mensaje);

        if (!datos.reservas.length) {
            cuerpoTabla.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#6c757d;">No tienes reservas registradas.</td></tr>';
            return;
        }

        cuerpoTabla.innerHTML = datos.reservas.map(reserva => {
            const claseEstado = reserva.estado_pago === 'pagado' ? 'etiqueta-estado-ok'
                              : reserva.estado_pago === 'cancelada' ? 'etiqueta-estado-mal'
                              : 'etiqueta-estado-pend';

            const botonCancelar = reserva.estado_pago !== 'cancelada'
                ? `<button class="btn-cancelar" onclick="cancelarReservaHistorial(${reserva.id})">Cancelar</button>`
                : '—';

            return `
                <tr>
                    <td>${reserva.id}</td>
                    <td>${reserva.pista}<span class="etiqueta-info">${reserva.deporte}</span></td>
                    <td>${fechaLegible(reserva.fecha)}</td>
                    <td>${reserva.hora_inicio.slice(0,5)} – ${reserva.hora_fin.slice(0,5)}</td>
                    <td>${reserva.monitor_nombre || '—'}</td>
                    <td>${reserva.materiales || '—'}</td>
                    <td class="precio-resaltado">${parseFloat(reserva.precio_final).toFixed(2)} €</td>
                    <td><span class="${claseEstado}">${reserva.estado_pago}</span></td>
                    <td>${botonCancelar}</td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        cuerpoTabla.innerHTML = `<tr><td colspan="9" style="color:red;">Error: ${error.message}</td></tr>`;
    }
}

//CANCELAR MODAL
function abrirModalCancelar(id, callback) {
    const overlay = document.getElementById('modalCancelar');
    const boton     = document.getElementById('btnConfirmarCancelar');
    if (!overlay || !boton) { callback(id); return; }
    overlay.classList.add('activo');
    //CLONAR BOTON PARA ELIMINAR LISTENERS ANTERIORES
    const btnNuevo = boton.cloneNode(true);
    boton.parentNode.replaceChild(btnNuevo, boton);
    btnNuevo.addEventListener('click', () => {
        cerrarModalCancelar();
        callback(id);
    });
}

function cerrarModalCancelar() {
    const overlay = document.getElementById('modalCancelar');
    if (overlay) overlay.classList.remove('activo');
}

//CANCELAR RESERVA
function cancelarReserva(id) {
    abrirModalCancelar(id, async (idReserva) => {
        const formularioDatos = new FormData();
        formularioDatos.append('action', 'cancelar');
        formularioDatos.append('reserva_id', idReserva);
        
        const respuesta = await fetch('api.php', { method: 'POST', body: formularioDatos });
        const datos = await respuesta.json();
        
        mensajeTemporal(datos.mensaje, datos.ok ? 'success' : 'error');
        
        if (datos.ok) {
            actualizarSaldoUI(datos.saldo);
            cargarReservasProximas();
        }
    });
}

//CANCELAR HISTORIAL
function cancelarReservaHistorial(id) {
    abrirModalCancelar(id, async (idReserva) => {
        const formularioDatos = new FormData();
        formularioDatos.append('action', 'cancelar');
        formularioDatos.append('reserva_id', idReserva);
        
        const respuesta = await fetch('api.php', { method: 'POST', body: formularioDatos });
        const datos = await respuesta.json();
        
        mensajeTemporal(datos.mensaje, datos.ok ? 'success' : 'error');
        
        if (datos.ok) {
            cargarHistorial();
        }
    });
}

//MODAL
async function initModal() {
    const [respPistas, respMonitores, respMateriales] = await Promise.all([
        fetch('api.php?action=pistas').then(respuesta => respuesta.json()),
        fetch('api.php?action=monitores').then(respuesta => respuesta.json()),
        fetch('api.php?action=materiales').then(respuesta => respuesta.json()),
    ]);

    cachePistas     = respPistas.pistas || [];
    cacheMonitores  = respMonitores.monitores || [];
    cacheMateriales = respMateriales.materiales || [];

    const selectPista = document.getElementById('selectPista');
    selectPista.innerHTML = cachePistas.map(pista =>
        `<option value="${pista.id}" data-precio="${pista.precio}">${pista.nombre} (${pista.deporte}) – ${parseFloat(pista.precio).toFixed(2)} €/h</option>`
    ).join('');

    //DESAHBILITAR MONITORES NO DISPONIBLES
    const selectMonitor = document.getElementById('selectMonitor');
    selectMonitor.innerHTML = '<option value="0" data-precio="0">Sin monitor</option>' +
        cacheMonitores.map(monitor => {
            if (parseInt(monitor.disponibilidad) === 0) {
                return `<option value="${monitor.id}" disabled style="color:#bbb;">${monitor.nombre} (${monitor.especialidad}) – No disponible</option>`;
            }
            return `<option value="${monitor.id}" data-precio="${monitor.precio}">${monitor.nombre} (${monitor.especialidad}) – ${parseFloat(monitor.precio).toFixed(2)} €/sesión</option>`;
        }).join('');

    const selectMaterial = document.getElementById('selectMaterial');
    selectMaterial.innerHTML = '<option value="0" data-precio="0">Sin material</option>' +
        cacheMateriales.map(material =>
            `<option value="${material.id}" data-precio="${material.precio}">${material.nombre} – ${parseFloat(material.precio).toFixed(2)} €/ud</option>`
        ).join('');

    const fechaHoy = new Date().toISOString().split('T')[0];
    document.getElementById('inputFecha').min = fechaHoy;
    document.getElementById('inputFecha').value = fechaHoy;

    actualizarResumen();
}

//ABRIR MODAL
function abrirModal() {
    document.getElementById('modalReserva').classList.add('activo');
    document.getElementById('modalMensaje').classList.remove('visible', 'error', 'success');
    const inputFecha = document.getElementById('inputFecha');
    if (inputFecha && fechaSeleccionada) inputFecha.value = fechaSeleccionada;
}

//ABRIR MODAL PISTA
function abrirModalConPista(idPista) {
    abrirModal();
    const selectPista = document.getElementById('selectPista');
    if (selectPista) selectPista.value = idPista;
    actualizarResumen();
}

//CERRAR MODAL
function cerrarModal() {
    document.getElementById('modalReserva').classList.remove('activo');
}

//ACTUALIZAR RESUMEN FINAL
function actualizarResumen() {
    const selectPista  = document.getElementById('selectPista');
    const inputInicio = document.getElementById('inputHoraInicio');
    const inputFin  = document.getElementById('inputHoraFin');
    const selectMonitor = document.getElementById('selectMonitor');
    const selectMaterial = document.getElementById('selectMaterial');
    const cantidad = parseInt(document.getElementById('cantidadMaterial')?.value || '1', 10);

    if (!selectPista || !inputInicio || !inputFin) return;

    const precioPista = parseFloat(selectPista.selectedOptions[0]?.dataset.precio   || 0);
    const precioMonitor = parseFloat(selectMonitor?.selectedOptions[0]?.dataset.precio || 0);
    const precioMaterial = parseFloat(selectMaterial?.selectedOptions[0]?.dataset.precio|| 0);

    const horaInicio = inputInicio.value;
    const horaFin = inputFin.value;
    let horasTotales = 0;
    
    if (horaInicio && horaFin && horaFin > horaInicio) {
        horasTotales = (new Date('1970-01-01T' + horaFin) - new Date('1970-01-01T' + horaInicio)) / 3600000;
    }

    const costePista = precioPista * horasTotales;
    const costeMonitor = precioMonitor;
    const costeMaterial = precioMaterial * cantidad;
    const total = costePista + costeMonitor + costeMaterial;

    document.getElementById('resumenPista').textContent = costePista.toFixed(2) + ' €';
    document.getElementById('resumenMonitor').textContent = costeMonitor.toFixed(2) + ' €';
    document.getElementById('resumenMaterial').textContent = costeMaterial.toFixed(2) + ' €';
    document.getElementById('resumenTotal').textContent = total.toFixed(2) + ' €';
}

//CONFIRMACION RESERVA
async function confirmarReserva() {
    const elementoMensaje = document.getElementById('modalMensaje');
    const idPista= document.getElementById('selectPista').value;
    const fecha = document.getElementById('inputFecha').value;
    const horaInicio = document.getElementById('inputHoraInicio').value;
    const horaFin = document.getElementById('inputHoraFin').value;
    const idMonitor = document.getElementById('selectMonitor').value;
    const idMaterial = document.getElementById('selectMaterial').value;
    const cantidad = document.getElementById('cantidadMaterial').value;

    if (!fecha || !horaInicio || !horaFin) {
        elementoMensaje.className = 'alerta error visible';
        elementoMensaje.textContent = 'Rellena fecha y horario.';
        return;
    }
    if (horaFin <= horaInicio) {
        elementoMensaje.className = 'alerta error visible';
        elementoMensaje.textContent = 'La hora de fin debe ser posterior a la de inicio.';
        return;
    }

    const formularioDatos = new FormData();
    formularioDatos.append('action', 'reservar');
    formularioDatos.append('pista_id', idPista);
    formularioDatos.append('fecha', fecha);
    formularioDatos.append('hora_inicio', horaInicio);
    formularioDatos.append('hora_fin', horaFin);
    formularioDatos.append('monitor_id', idMonitor);
    formularioDatos.append('material_id', idMaterial);
    formularioDatos.append('cantidad', cantidad);

    const respuesta = await fetch('api.php', { method: 'POST', body: formularioDatos });
    const datos = await respuesta.json();

    if (datos.ok) {
        cerrarModal();
        const t = datos.ticket;
        const params = new URLSearchParams({
            ticket: t.id,
            pista: t.pista,
            deporte: t.deporte,
            fecha: t.fecha,
            hora_inicio: t.hora_inicio,
            hora_fin: t.hora_fin,
            total: t.total
        });
        window.location.href = 'perfil.php?' + params.toString();
    } else {
        elementoMensaje.className = 'alerta error visible';
        elementoMensaje.textContent = datos.mensaje;
    }
}

//ACTUALIZAR SALDO
function actualizarSaldoUI(saldo) {
    const elementoSaldo = document.getElementById('saldoDisplay');
    if (elementoSaldo) elementoSaldo.textContent = saldo;
}