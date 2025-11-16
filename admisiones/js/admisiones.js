/**
 * =====================================================
 * SISTEMA DE ADMISIONES - TRINITY SCHOOL
 * JavaScript para funcionalidad del formulario
 * =====================================================
 */

// ============================================
// VARIABLES GLOBALES
// ============================================
let pasoActual = 1;
const totalPasos = 5;

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    inicializarFormulario();
    configurarEventos();
    configurarValidaciones();
});

/**
 * Inicializar el formulario y sus componentes
 */
function inicializarFormulario() {
    console.log('✅ Sistema de admisiones iniciado');
    
    // Ocultar formulario inicialmente
    document.getElementById('formularioAdmisiones').style.display = 'none';
    
    // Configurar fecha máxima para fecha de nacimiento (3 años atrás)
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    if (fechaNacimiento) {
        const hoy = new Date();
        const hace3Anios = new Date(hoy.getFullYear() - 3, hoy.getMonth(), hoy.getDate());
        fechaNacimiento.max = hace3Anios.toISOString().split('T')[0];
    }
}

/**
 * Configurar todos los eventos del formulario
 */
function configurarEventos() {
    // Botón iniciar registro
    const btnIniciar = document.getElementById('btnIniciarRegistro');
    if (btnIniciar) {
        btnIniciar.addEventListener('click', mostrarFormulario);
    }
    
    // Navegación entre pasos
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnAnterior = document.getElementById('btnAnterior');
    
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', siguientePaso);
    }
    
    if (btnAnterior) {
        btnAnterior.addEventListener('click', anteriorPaso);
    }
    
    // Envío del formulario
    const formAdmision = document.getElementById('formAdmision');
    if (formAdmision) {
        formAdmision.addEventListener('submit', enviarFormulario);
    }
    
    // Nivel educativo - cambiar grados dinámicamente
    const nivelPostula = document.getElementById('nivel_postula');
    if (nivelPostula) {
        nivelPostula.addEventListener('change', actualizarGrados);
    }
    
    // Apoderado principal - mostrar/ocultar campos
    const apoderadoPrincipal = document.getElementById('apoderado_principal');
    if (apoderadoPrincipal) {
        apoderadoPrincipal.addEventListener('change', toggleApoderado);
    }
    
    // Hermanos en el colegio
    const tieneHermanos = document.getElementById('tiene_hermanos');
    if (tieneHermanos) {
        tieneHermanos.addEventListener('change', function() {
            const campo = document.getElementById('campoHermanos');
            campo.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Necesidades especiales
    const necesidadesEspeciales = document.getElementById('necesidades_especiales');
    if (necesidadesEspeciales) {
        necesidadesEspeciales.addEventListener('change', function() {
            const campo = document.getElementById('campoNecesidades');
            campo.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Preview de archivos subidos
    configurarPreviewArchivos();
    
    // Validación de DNI (solo números)
    validarSoloNumeros();
}

/**
 * Mostrar el formulario con animación
 */
function mostrarFormulario() {
    const formulario = document.getElementById('formularioAdmisiones');
    formulario.style.display = 'block';
    
    // Scroll suave hacia el formulario
    formulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Animación de entrada
    setTimeout(() => {
        formulario.style.opacity = '0';
        formulario.style.transform = 'translateY(20px)';
        formulario.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            formulario.style.opacity = '1';
            formulario.style.transform = 'translateY(0)';
        }, 50);
    }, 100);
}

/**
 * Ir al siguiente paso
 */
function siguientePaso() {
    // Validar paso actual antes de avanzar
    if (!validarPasoActual()) {
        mostrarAlerta('Por favor, completa todos los campos obligatorios', 'error');
        return;
    }
    
    if (pasoActual < totalPasos) {
        pasoActual++;
        actualizarPaso();
    }
}

/**
 * Volver al paso anterior
 */
function anteriorPaso() {
    if (pasoActual > 1) {
        pasoActual--;
        actualizarPaso();
    }
}

/**
 * Actualizar la visualización del paso actual
 */
function actualizarPaso() {
    // Ocultar todos los pasos
    const pasos = document.querySelectorAll('.paso-contenido');
    pasos.forEach(paso => {
        paso.classList.remove('activo');
    });
    
    // Mostrar paso actual
// Mostrar paso actual
const pasoActivo = document.querySelector(`.paso-contenido[data-paso="${pasoActual}"]`);
if (pasoActivo) {
    pasoActivo.classList.add('activo');
}
    // Actualizar indicador de progreso
    actualizarProgreso();
    
    // Actualizar botones
    actualizarBotones();
    
    // Scroll al inicio del formulario
    document.querySelector('.formulario-wrapper').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
    
    // Si estamos en el último paso, generar resumen
    if (pasoActual === totalPasos) {
        generarResumen();
    }
}

/**
 * Actualizar el indicador de progreso
 */
function actualizarProgreso() {
    const indicadores = document.querySelectorAll('.paso-progreso');
    
    indicadores.forEach((indicador, index) => {
        const numeroPaso = index + 1;
        
        // Remover clases
        indicador.classList.remove('activo', 'completado');
        
        // Agregar clase según estado
        if (numeroPaso === pasoActual) {
            indicador.classList.add('activo');
        } else if (numeroPaso < pasoActual) {
            indicador.classList.add('completado');
        }
    });
}

/**
 * Actualizar visibilidad de botones
 */
function actualizarBotones() {
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnEnviar = document.getElementById('btnEnviar');
    
    // Botón anterior
    btnAnterior.style.display = pasoActual > 1 ? 'block' : 'none';
    
    // Botón siguiente / enviar
    if (pasoActual === totalPasos) {
        btnSiguiente.style.display = 'none';
        btnEnviar.style.display = 'flex';
    } else {
        btnSiguiente.style.display = 'block';
        btnEnviar.style.display = 'none';
    }
}

/**
 * Validar el paso actual
 */
function validarPasoActual() {
    const pasoActivo = document.querySelector(`.paso-contenido[data-paso="${pasoActual}"]`);
    if (!pasoActivo) return false;
    
    const camposRequeridos = pasoActivo.querySelectorAll('[required]');
    let valido = true;
    
    camposRequeridos.forEach(campo => {
        // Limpiar estilos previos
        campo.style.borderColor = '';
        
        if (!campo.value.trim()) {
            campo.style.borderColor = '#dc3545';
            valido = false;
            
            // Focus en el primer campo inválido
            if (valido === false) {
                campo.focus();
            }
        }
    });
    
    return valido;
}

/**
 * Actualizar grados según el nivel educativo
 */
function actualizarGrados() {
    const nivel = document.getElementById('nivel_postula').value;
    const gradoSelect = document.getElementById('grado_postula');
    
    // Limpiar opciones
    gradoSelect.innerHTML = '<option value="">Seleccionar...</option>';
    
    let grados = [];
    
    switch(nivel) {
        case 'Inicial':
            grados = ['3 años', '4 años', '5 años'];
            break;
        case 'Primaria':
            grados = ['1° Grado', '2° Grado', '3° Grado', '4° Grado', '5° Grado', '6° Grado'];
            break;
        case 'Secundaria':
            grados = ['1° Año', '2° Año', '3° Año', '4° Año', '5° Año'];
            break;
    }
    
    // Agregar opciones
    grados.forEach(grado => {
        const option = document.createElement('option');
        option.value = grado;
        option.textContent = grado;
        gradoSelect.appendChild(option);
    });
    
    // Habilitar select
    gradoSelect.disabled = false;
}

/**
 * Mostrar/ocultar campos según apoderado
 */
function toggleApoderado() {
    const apoderado = document.getElementById('apoderado_principal').value;
    const datosOtro = document.getElementById('datosOtroApoderado');
    
    if (apoderado === 'Otro') {
        datosOtro.style.display = 'block';
        // Hacer campos requeridos
        document.getElementById('nombre_apoderado').required = true;
        document.getElementById('parentesco_apoderado').required = true;
        document.getElementById('dni_apoderado').required = true;
    } else {
        datosOtro.style.display = 'none';
        // Quitar requeridos
        document.getElementById('nombre_apoderado').required = false;
        document.getElementById('parentesco_apoderado').required = false;
        document.getElementById('dni_apoderado').required = false;
    }
}

/**
 * Configurar preview de archivos subidos
 */
function configurarPreviewArchivos() {
    const inputsArchivo = document.querySelectorAll('input[type="file"]');
    
    inputsArchivo.forEach(input => {
        input.addEventListener('change', function() {
            const archivo = this.files[0];
            const previewId = 'preview_' + this.id.replace('doc_', '');
            const preview = document.getElementById(previewId);
            
            if (archivo) {
                // Validar tamaño (5MB)
                if (archivo.size > 5 * 1024 * 1024) {
                    mostrarAlerta('El archivo no debe superar 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Validar extensión
                const extension = archivo.name.split('.').pop().toLowerCase();
                const extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
                
                if (!extensionesPermitidas.includes(extension)) {
                    mostrarAlerta('Formato no permitido. Use: PDF, JPG o PNG', 'error');
                    this.value = '';
                    return;
                }
                
                // Mostrar preview
                if (preview) {
                    preview.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: #e8f5e9; border-radius: 6px;">
                            <span style="color: #28a745; font-size: 1.5rem;">✓</span>
                            <div style="flex: 1;">
                                <strong style="display: block; color: #333;">${archivo.name}</strong>
                                <small style="color: #666;">${(archivo.size / 1024).toFixed(2)} KB</small>
                            </div>
                            <button type="button" onclick="eliminarArchivo('${this.id}')" 
                                    style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                ✕
                            </button>
                        </div>
                    `;
                    preview.classList.add('mostrar');
                }
            }
        });
    });
}

/**
 * Eliminar archivo seleccionado
 */
function eliminarArchivo(inputId) {
    const input = document.getElementById(inputId);
    const previewId = 'preview_' + inputId.replace('doc_', '');
    const preview = document.getElementById(previewId);
    
    if (input) {
        input.value = '';
    }
    
    if (preview) {
        preview.innerHTML = '';
        preview.classList.remove('mostrar');
    }
}

/**
 * Validar solo números en campos DNI y celular
 */
function validarSoloNumeros() {
    const camposNumericos = document.querySelectorAll(
        '#dni_estudiante, #dni_padre, #dni_madre, #dni_apoderado, ' +
        '#celular_padre, #celular_madre, #celular_apoderado'
    );
    
    camposNumericos.forEach(campo => {
        campo.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
}

/**
 * Generar resumen de datos para confirmación
 */
function generarResumen() {
    const resumenDiv = document.getElementById('resumenDatos');
    if (!resumenDiv) return;
    
    const formData = new FormData(document.getElementById('formAdmision'));
    
    let html = '';
    
    // Datos del Estudiante
    html += `
        <div class="resumen-seccion">
            <h4>👤 Datos del Estudiante</h4>
            <div class="resumen-item">
                <span class="resumen-label">Nombres:</span>
                <span class="resumen-valor">${formData.get('nombres') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Apellidos:</span>
                <span class="resumen-valor">${formData.get('apellidos') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Fecha de Nacimiento:</span>
                <span class="resumen-valor">${formData.get('fecha_nacimiento') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">DNI:</span>
                <span class="resumen-valor">${formData.get('dni_estudiante') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Sexo:</span>
                <span class="resumen-valor">${formData.get('sexo') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Dirección:</span>
                <span class="resumen-valor">${formData.get('direccion') || '-'}</span>
            </div>
        </div>
    `;
    
    // Información Académica
    html += `
        <div class="resumen-seccion">
            <h4>🎓 Información Académica</h4>
            <div class="resumen-item">
                <span class="resumen-label">Nivel:</span>
                <span class="resumen-valor">${formData.get('nivel_postula') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Grado:</span>
                <span class="resumen-valor">${formData.get('grado_postula') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Colegio de Procedencia:</span>
                <span class="resumen-valor">${formData.get('colegio_procedencia') || 'No especificado'}</span>
            </div>
        </div>
    `;
    
    // Apoderado Principal
    html += `
        <div class="resumen-seccion">
            <h4>⭐ Apoderado Principal</h4>
            <div class="resumen-item">
                <span class="resumen-label">Apoderado:</span>
                <span class="resumen-valor">${formData.get('apoderado_principal') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Celular:</span>
                <span class="resumen-valor">${formData.get('celular_apoderado') || '-'}</span>
            </div>
            <div class="resumen-item">
                <span class="resumen-label">Email:</span>
                <span class="resumen-valor">${formData.get('email_apoderado') || '-'}</span>
            </div>
        </div>
    `;
    
    // Documentos
    const archivos = [
        { id: 'doc_partida', nombre: 'Partida de Nacimiento' },
        { id: 'doc_dni_estudiante', nombre: 'DNI Estudiante' },
        { id: 'doc_dni_apoderado', nombre: 'DNI Apoderado' },
        { id: 'doc_libreta', nombre: 'Libreta de Notas' },
        { id: 'doc_certificado', nombre: 'Certificado de Estudios' },
        { id: 'doc_foto', nombre: 'Foto del Estudiante' }
    ];
    
    html += `<div class="resumen-seccion"><h4>📎 Documentos Adjuntos</h4>`;
    
    archivos.forEach(archivo => {
        const input = document.getElementById(archivo.id);
        const tieneArchivo = input && input.files.length > 0;
        const icono = tieneArchivo ? '✅' : '❌';
        const estado = tieneArchivo ? input.files[0].name : 'No adjuntado';
        
        html += `
            <div class="resumen-item">
                <span class="resumen-label">${icono} ${archivo.nombre}:</span>
                <span class="resumen-valor">${estado}</span>
            </div>
        `;
    });
    
    html += `</div>`;
    
    resumenDiv.innerHTML = html;
}

/**
 * Enviar formulario
 */
function enviarFormulario(e) {
    e.preventDefault();
    
    // Validar términos y condiciones
    const aceptaTerminos = document.getElementById('aceptar_terminos');
    if (!aceptaTerminos.checked) {
        mostrarAlerta('Debes aceptar los términos y condiciones', 'error');
        return;
    }
    
    // Mostrar loader
    const btnEnviar = document.getElementById('btnEnviar');
    const textoOriginal = btnEnviar.innerHTML;
    btnEnviar.disabled = true;
    btnEnviar.innerHTML = '<span>Enviando...</span> <span class="btn-icon">⌛</span>';
    
    // Crear FormData
    const formData = new FormData(document.getElementById('formAdmision'));
    
    // Enviar con AJAX
    fetch('procesar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.exito) {
            // Redirigir a página de éxito
            window.location.href = 'gracias.php?codigo=' + data.codigo;
        } else {
            mostrarAlerta(data.mensaje || 'Error al procesar la solicitud', 'error');
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = textoOriginal;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión. Por favor, intenta nuevamente.', 'error');
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = textoOriginal;
    });
}

/**
 * Mostrar alertas
 */
function mostrarAlerta(mensaje, tipo = 'info') {
    // Crear elemento de alerta
    const alerta = document.createElement('div');
    alerta.className = 'alerta-flotante alerta-' + tipo;
    alerta.innerHTML = `
        <span class="alerta-icono">${tipo === 'error' ? '⚠️' : tipo === 'exito' ? '✅' : 'ℹ️'}</span>
        <span class="alerta-mensaje">${mensaje}</span>
        <button class="alerta-cerrar" onclick="this.parentElement.remove()">✕</button>
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('estilosAlerta')) {
        const style = document.createElement('style');
        style.id = 'estilosAlerta';
        style.textContent = `
            .alerta-flotante {
                position: fixed;
                top: 100px;
                right: 20px;
                max-width: 400px;
                padding: 15px 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            }
            
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            .alerta-error { border-left: 4px solid #dc3545; }
            .alerta-exito { border-left: 4px solid #28a745; }
            .alerta-info { border-left: 4px solid #17a2b8; }
            
            .alerta-icono { font-size: 1.5rem; }
            .alerta-mensaje { flex: 1; color: #333; }
            .alerta-cerrar {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 24px;
                height: 24px;
            }
            .alerta-cerrar:hover { color: #333; }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(alerta);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        alerta.remove();
    }, 5000);
}

/**
 * Configurar validaciones adicionales
 */
function configurarValidaciones() {
    // Validar email
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validarEmail(this.value)) {
                this.style.borderColor = '#dc3545';
                mostrarAlerta('Email no válido', 'error');
            } else {
                this.style.borderColor = '';
            }
        });
    });
}

/**
 * Validar formato de email
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    if (!fecha) return '-';
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

/**
 * Calcular edad
 */
function calcularEdad(fechaNacimiento) {
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    return edad;
}

console.log('✅ Sistema de admisiones cargado correctamente');