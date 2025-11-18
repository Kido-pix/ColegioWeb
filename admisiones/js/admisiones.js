// ============================================
// FORMULARIO DE ADMISIONES - TRINITY SCHOOL
// JavaScript completo y funcional
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Variables globales
    let pasoActual = 1;
    const totalPasos = 5;
    
    // Elementos del DOM
    const btnIniciarRegistro = document.getElementById('btnIniciarRegistro');
    const formularioAdmisiones = document.getElementById('formularioAdmisiones');
    const form = document.getElementById('formAdmision');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnEnviar = document.getElementById('btnEnviar');
    
    // ============================================
    // INICIAR FORMULARIO
    // ============================================
    if (btnIniciarRegistro) {
        btnIniciarRegistro.addEventListener('click', function() {
            formularioAdmisiones.style.display = 'block';
            formularioAdmisiones.scrollIntoView({ behavior: 'smooth' });
        });
    }
    
    // ============================================
    // NAVEGACIÓN ENTRE PASOS
    // ============================================
    function mostrarPaso(numeroPaso) {
        // Ocultar todos los pasos
        document.querySelectorAll('.paso-contenido').forEach(paso => {
            paso.classList.remove('activo');
        });
        
        // Mostrar paso actual
        const pasoMostrar = document.querySelector(`.paso-contenido[data-paso="${numeroPaso}"]`);
        if (pasoMostrar) {
            pasoMostrar.classList.add('activo');
        }
        
        // Actualizar indicadores de progreso
        document.querySelectorAll('.paso-progreso').forEach((indicador, index) => {
            indicador.classList.remove('activo', 'completado');
            if (index + 1 === numeroPaso) {
                indicador.classList.add('activo');
            } else if (index + 1 < numeroPaso) {
                indicador.classList.add('completado');
            }
        });
        
        // Mostrar/ocultar botones
        btnAnterior.style.display = numeroPaso === 1 ? 'none' : 'inline-block';
        btnSiguiente.style.display = numeroPaso === totalPasos ? 'none' : 'inline-block';
        btnEnviar.style.display = numeroPaso === totalPasos ? 'inline-block' : 'none';
        
        // Scroll suave al inicio
        formularioAdmisiones.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Botón Anterior
    if (btnAnterior) {
        btnAnterior.addEventListener('click', function() {
            if (pasoActual > 1) {
                pasoActual--;
                mostrarPaso(pasoActual);
            }
        });
    }
    
    // Botón Siguiente
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', function() {
            if (validarPasoActual()) {
                if (pasoActual < totalPasos) {
                    pasoActual++;
                    mostrarPaso(pasoActual);
                }
            }
        });
    }
    
    // ============================================
    // VALIDACIÓN POR PASO
    // ============================================
    function validarPasoActual() {
        const pasoElement = document.querySelector(`.paso-contenido[data-paso="${pasoActual}"]`);
        const camposRequeridos = pasoElement.querySelectorAll('[required]');
        let valido = true;
        
        camposRequeridos.forEach(campo => {
            // Limpiar errores previos
            campo.classList.remove('error');
            const errorPrevio = campo.parentElement.querySelector('.error-message');
            if (errorPrevio) errorPrevio.remove();
            
            // Validar según tipo de campo
            if (campo.type === 'file') {
                if (campo.files.length === 0) {
                    mostrarError(campo, 'Debe seleccionar un archivo');
                    valido = false;
                }
            } else if (campo.value.trim() === '') {
                mostrarError(campo, 'Este campo es obligatorio');
                valido = false;
            } else {
                // Validaciones específicas
                if (campo.type === 'email' && !validarEmail(campo.value)) {
                    mostrarError(campo, 'Email inválido');
                    valido = false;
                }
                if (campo.name === 'dni_estudiante' && !validarDNI(campo.value)) {
                    mostrarError(campo, 'DNI debe tener 8 dígitos');
                    valido = false;
                }
                if ((campo.name.includes('celular')) && !validarCelular(campo.value)) {
                    mostrarError(campo, 'Celular debe tener 9 dígitos');
                    valido = false;
                }
            }
        });
        
        if (!valido) {
            mostrarAlerta('Por favor complete todos los campos obligatorios correctamente', 'error');
        }
        
        return valido;
    }
    
    function mostrarError(campo, mensaje) {
        campo.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = mensaje;
        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.85rem; margin-top: 5px;';
        campo.parentElement.appendChild(errorDiv);
    }
    
    function validarEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function validarDNI(dni) {
        return /^\d{8}$/.test(dni);
    }
    
    function validarCelular(celular) {
        return /^\d{9}$/.test(celular);
    }
    
    // ============================================
    // GRADOS SEGÚN NIVEL
    // ============================================
    const nivelPostula = document.getElementById('nivel_postula');
    const gradoPostula = document.getElementById('grado_postula');
    
    const gradosPorNivel = {
        'Inicial': ['3 años', '4 años', '5 años'],
        'Primaria': ['1° Grado', '2° Grado', '3° Grado', '4° Grado', '5° Grado', '6° Grado'],
        'Secundaria': ['1° Año', '2° Año', '3° Año', '4° Año', '5° Año']
    };
    
    if (nivelPostula && gradoPostula) {
        nivelPostula.addEventListener('change', function() {
            const nivel = this.value;
            gradoPostula.innerHTML = '<option value="">Seleccionar...</option>';
            gradoPostula.disabled = false;
            
            if (nivel && gradosPorNivel[nivel]) {
                gradosPorNivel[nivel].forEach(grado => {
                    const option = document.createElement('option');
                    option.value = grado;
                    option.textContent = grado;
                    gradoPostula.appendChild(option);
                });
            }
        });
    }
    
    // ============================================
    // APODERADO PRINCIPAL - MOSTRAR CAMPOS
    // ============================================
    const apoderadoPrincipal = document.getElementById('apoderado_principal');
    const datosOtroApoderado = document.getElementById('datosOtroApoderado');
    
    if (apoderadoPrincipal && datosOtroApoderado) {
        apoderadoPrincipal.addEventListener('change', function() {
            if (this.value === 'Otro') {
                datosOtroApoderado.style.display = 'block';
                // Hacer campos requeridos
                datosOtroApoderado.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                datosOtroApoderado.style.display = 'none';
                // Quitar requerido
                datosOtroApoderado.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
        });
    }
    
    // ============================================
    // CHECKBOXES CONDICIONALES
    // ============================================
    const tieneHermanos = document.getElementById('tiene_hermanos');
    const campoHermanos = document.getElementById('campoHermanos');
    
    if (tieneHermanos && campoHermanos) {
        tieneHermanos.addEventListener('change', function() {
            campoHermanos.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    const necesidadesEspeciales = document.getElementById('necesidades_especiales');
    const campoNecesidades = document.getElementById('campoNecesidades');
    
    if (necesidadesEspeciales && campoNecesidades) {
        necesidadesEspeciales.addEventListener('change', function() {
            campoNecesidades.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // ============================================
    // PREVISUALIZACIÓN DE ARCHIVOS
    // ============================================
    const inputsArchivo = document.querySelectorAll('input[type="file"]');
    
    inputsArchivo.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = 'preview_' + this.id.replace('doc_', '');
            const preview = document.getElementById(previewId);
            
            if (this.files.length > 0) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                
                // Validar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    mostrarAlerta('El archivo no debe superar los 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Validar extensión
                const extension = file.name.split('.').pop().toLowerCase();
                const extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
                
                if (!extensionesPermitidas.includes(extension)) {
                    mostrarAlerta('Solo se permiten archivos PDF, JPG, JPEG y PNG', 'error');
                    this.value = '';
                    return;
                }
                
                // Mostrar preview
                if (preview) {
                    preview.innerHTML = `
                        <div style="padding: 10px; background: #d4edda; border-radius: 5px; color: #155724; margin-top: 10px;">
                            ✓ ${file.name} (${fileSize} MB)
                        </div>
                    `;
                }
                
                // Cambiar estilo del label
                const label = this.parentElement.querySelector('.upload-label');
                if (label) {
                    label.style.borderColor = '#28a745';
                    label.style.background = 'rgba(40, 167, 69, 0.05)';
                }
            }
        });
    });
    
    // ============================================
    // ENVÍO DEL FORMULARIO
    // ============================================
    if (btnEnviar) {
        btnEnviar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Validar último paso
            if (!validarPasoActual()) {
                return;
            }
            
            // Verificar checkbox de términos
            const aceptaTerminos = document.getElementById('aceptar_terminos');
            if (!aceptaTerminos || !aceptaTerminos.checked) {
                mostrarAlerta('Debe aceptar los términos y condiciones', 'error');
                return;
            }
            
            // Confirmar envío
            if (!confirm('¿Está seguro de enviar la solicitud? Verifique que todos los datos sean correctos.')) {
                return;
            }
            
            // Deshabilitar botón
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            
            // Crear FormData
            const formData = new FormData(form);
            
            // Log para debug (puedes quitarlo después)
            console.log('Datos a enviar:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key + ': ' + value.name + ' (' + value.size + ' bytes)');
                } else {
                    console.log(key + ': ' + value);
                }
            }
            
            // Enviar por AJAX
            fetch('procesar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                
                if (data.exito) {
                    // Redirigir a página de confirmación
                    mostrarAlerta('¡Solicitud enviada exitosamente!', 'success');
                    setTimeout(() => {
                        window.location.href = `gracias.php?codigo=${data.codigo}`;
                    }, 1500);
                } else {
                    mostrarAlerta(data.mensaje || 'Error al procesar la solicitud', 'error');
                    btnEnviar.disabled = false;
                    btnEnviar.innerHTML = '<span>Enviar Solicitud</span><span class="btn-icon">✓</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error de conexión. Por favor intente nuevamente.', 'error');
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<span>Enviar Solicitud</span><span class="btn-icon">✓</span>';
            });
        });
    }
    
    // ============================================
    // FUNCIÓN PARA MOSTRAR ALERTAS
    // ============================================
    function mostrarAlerta(mensaje, tipo = 'info') {
        const colores = {
            'error': '#dc3545',
            'success': '#28a745',
            'info': '#17a2b8',
            'warning': '#ffc107'
        };
        
        const iconos = {
            'error': 'exclamation-circle',
            'success': 'check-circle',
            'info': 'info-circle',
            'warning': 'exclamation-triangle'
        };
        
        const alerta = document.createElement('div');
        alerta.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${colores[tipo] || colores.info};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            animation: slideIn 0.3s ease;
        `;
        
        alerta.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-${iconos[tipo] || iconos.info}" style="font-size: 1.2rem;"></i>
                <span>${mensaje}</span>
            </div>
        `;
        
        document.body.appendChild(alerta);
        
        setTimeout(() => {
            alerta.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    }
    
    // ============================================
    // ESTILOS CSS DINÁMICOS
    // ============================================
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .error {
            border-color: #dc3545 !important;
            background: rgba(220, 53, 69, 0.05) !important;
        }
        
        .paso-progreso.completado .paso-circulo {
            background: #28a745 !important;
        }
        
        .paso-progreso.activo .paso-circulo {
            background: #8B1538 !important;
            transform: scale(1.1);
        }
    `;
    document.head.appendChild(style);
    
    console.log('✓ Sistema de admisiones iniciado correctamente');
});