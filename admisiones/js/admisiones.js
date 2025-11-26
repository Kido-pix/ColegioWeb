// ============================================
// FORMULARIO DE ADMISIONES - TRINITY SCHOOL
// JavaScript completo y funcional
// Versión 3.0 - Final con validación mejorada
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
        
        // Generar resumen si estamos en el paso final
        if (numeroPaso === totalPasos) {
            generarResumen();
        }
        
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
                if (campo.name === 'dni_apoderado_otro' && campo.value && !validarDNI(campo.value)) {
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
                const nombreApoderado = document.getElementById('nombre_apoderado');
                const parentescoApoderado = document.getElementById('parentesco_apoderado');
                const dniApoderadoOtro = document.getElementById('dni_apoderado_otro');
                
                if (nombreApoderado) nombreApoderado.required = true;
                if (parentescoApoderado) parentescoApoderado.required = true;
                if (dniApoderadoOtro) dniApoderadoOtro.required = true;
            } else {
                datosOtroApoderado.style.display = 'none';
                // Quitar requerido
                const nombreApoderado = document.getElementById('nombre_apoderado');
                const parentescoApoderado = document.getElementById('parentesco_apoderado');
                const dniApoderadoOtro = document.getElementById('dni_apoderado_otro');
                
                if (nombreApoderado) nombreApoderado.required = false;
                if (parentescoApoderado) parentescoApoderado.required = false;
                if (dniApoderadoOtro) dniApoderadoOtro.required = false;
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
    // LIMPIAR ERRORES AL ESCRIBIR EN CAMPOS OPCIONALES
    // ============================================
    const camposOpcionales = [
        'nombre_padre', 'celular_padre', 'email_padre', 'ocupacion_padre',
        'nombre_madre', 'celular_madre', 'email_madre', 'ocupacion_madre'
    ];
    
    camposOpcionales.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.addEventListener('input', function() {
                this.classList.remove('error');
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            });
        }
    });
    
    // ============================================
    // PREVISUALIZACIÓN DE ARCHIVOS (VALIDACIÓN MEJORADA)
    // ============================================
    const inputsArchivo = document.querySelectorAll('input[type="file"]');
    
    inputsArchivo.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file) return;
            
            // ===== VALIDACIÓN 1: TAMAÑO MÁXIMO (5MB) =====
            const MAX_SIZE = 5 * 1024 * 1024;
            if (file.size > MAX_SIZE) {
                mostrarAlerta('⚠️ El archivo es muy grande. Tamaño máximo: 5MB', 'error');
                this.value = '';
                return;
            }
            
            // ===== VALIDACIÓN 2: TAMAÑO MÍNIMO (1KB) =====
            const MIN_SIZE = 1024;
            if (file.size < MIN_SIZE) {
                mostrarAlerta('⚠️ El archivo es muy pequeño. Tamaño mínimo: 1KB', 'error');
                this.value = '';
                return;
            }
            
            // ===== VALIDACIÓN 3: EXTENSIÓN DEL ARCHIVO =====
            const extension = file.name.split('.').pop().toLowerCase();
            const extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!extensionesPermitidas.includes(extension)) {
                mostrarAlerta('⚠️ Tipo de archivo no permitido. Solo: PDF, JPG, JPEG, PNG', 'error');
                this.value = '';
                return;
            }
            
            // ===== VALIDACIÓN 4: TIPO MIME =====
            const tiposMimePermitidos = [
                'application/pdf',
                'image/jpeg',
                'image/jpg', 
                'image/png'
            ];
            
            if (!tiposMimePermitidos.includes(file.type)) {
                mostrarAlerta('⚠️ Tipo de archivo inválido detectado. Archivo rechazado.', 'error');
                this.value = '';
                return;
            }
            
            // ===== VALIDACIÓN 5: NOMBRE DE ARCHIVO =====
            const nombreArchivo = file.name;
            const caracteresInvalidos = /[<>:"\/\\|?*\x00-\x1F]/g;
            
            if (caracteresInvalidos.test(nombreArchivo)) {
                mostrarAlerta('⚠️ El nombre del archivo contiene caracteres no permitidos', 'error');
                this.value = '';
                return;
            }
            
            // ===== VALIDACIÓN 6: VERIFICACIÓN ESPECÍFICA POR TIPO =====
            if (file.type === 'application/pdf') {
                validarPDF(file, this);
            } else if (file.type.startsWith('image/')) {
                validarImagen(file, this);
            }
            
            // Buscar el preview correcto
            let previewContainer = this.parentElement.querySelector('.archivo-preview');
            if (!previewContainer) {
                const previewId = 'preview_' + this.id;
                previewContainer = document.getElementById(previewId);
            }
            
            if (previewContainer) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const tipoIcono = file.type === 'application/pdf' ? '📄' : '🖼️';
                
                previewContainer.innerHTML = `
                    <div class="archivo-info" style="background: #e8f5e9; padding: 10px; border-radius: 8px; margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <span class="archivo-icono" style="font-size: 1.5rem; margin-right: 10px;">${tipoIcono}</span>
                            <div>
                                <span class="archivo-nombre" style="font-weight: 600; color: #2e7d32; display: block;">✅ ${file.name}</span>
                                <span class="archivo-tamano" style="font-size: 0.85rem; color: #666;">${fileSize} MB</span>
                            </div>
                        </div>
                        <button type="button" class="btn-eliminar-archivo" onclick="eliminarArchivo('${this.id}')" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; margin-left: 10px;">✕</button>
                    </div>
                `;
                previewContainer.classList.add('mostrar');
            }
            
            // Limpiar errores
            this.classList.remove('error');
            const errorMsg = this.parentElement.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
        });
        
        // Prevenir drag & drop
        input.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        
        input.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.files = files;
                this.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // ===== FUNCIÓN: VALIDAR PDF =====
    function validarPDF(file, inputElement) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const arr = new Uint8Array(e.target.result).subarray(0, 5);
            let header = "";
            for (let i = 0; i < arr.length; i++) {
                header += String.fromCharCode(arr[i]);
            }
            
            if (!header.startsWith('%PDF-')) {
                mostrarAlerta('⚠️ El archivo no es un PDF válido', 'error');
                inputElement.value = '';
                const preview = inputElement.parentElement.querySelector('.archivo-preview');
                if (preview) preview.innerHTML = '';
            }
        };
        
        reader.onerror = function() {
            mostrarAlerta('⚠️ Error al leer el archivo PDF', 'error');
            inputElement.value = '';
        };
        
        reader.readAsArrayBuffer(file.slice(0, 5));
    }
    
    // ===== FUNCIÓN: VALIDAR IMAGEN =====
    function validarImagen(file, inputElement) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = new Image();
            
            img.onload = function() {
                if (this.width < 100 || this.height < 100) {
                    mostrarAlerta('⚠️ La imagen es muy pequeña. Dimensiones mínimas recomendadas: 100x100px', 'warning');
                }
                
                if (this.width > 5000 || this.height > 5000) {
                    mostrarAlerta('⚠️ La imagen es muy grande. Dimensiones máximas recomendadas: 5000x5000px', 'warning');
                }
            };
            
            img.onerror = function() {
                mostrarAlerta('⚠️ El archivo no es una imagen válida', 'error');
                inputElement.value = '';
                const preview = inputElement.parentElement.querySelector('.archivo-preview');
                if (preview) preview.innerHTML = '';
            };
            
            img.src = e.target.result;
        };
        
        reader.onerror = function() {
            mostrarAlerta('⚠️ Error al leer la imagen', 'error');
            inputElement.value = '';
        };
        
        reader.readAsDataURL(file);
    }
    
    // ============================================
    // GENERAR RESUMEN (PASO 5)
    // ============================================
    function generarResumen() {
        const resumenContainer = document.getElementById('resumenDatos');
        if (!resumenContainer) return;
        
        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">';
        
        // Datos del Estudiante
        html += '<div style="background: #f5f5f5; padding: 20px; border-radius: 10px;">';
        html += '<h4 style="color: #8B1538; margin-bottom: 15px; font-size: 1.2rem;">👤 Datos del Estudiante</h4>';
        html += '<ul style="list-style: none; padding: 0;">';
        html += `<li style="margin-bottom: 8px;"><strong>Nombres:</strong> ${obtenerValor('nombres')} ${obtenerValor('apellido_paterno')} ${obtenerValor('apellido_materno')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>DNI:</strong> ${obtenerValor('dni_estudiante')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>Fecha de nacimiento:</strong> ${obtenerValor('fecha_nacimiento')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>Sexo:</strong> ${obtenerValor('sexo')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>Dirección:</strong> ${obtenerValor('direccion')}, ${obtenerValor('distrito')}</li>`;
        html += '</ul></div>';
        
        // Información Académica
        html += '<div style="background: #f5f5f5; padding: 20px; border-radius: 10px;">';
        html += '<h4 style="color: #8B1538; margin-bottom: 15px; font-size: 1.2rem;">🎓 Información Académica</h4>';
        html += '<ul style="list-style: none; padding: 0;">';
        html += `<li style="margin-bottom: 8px;"><strong>Nivel:</strong> ${obtenerValor('nivel_postula')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>Grado:</strong> ${obtenerValor('grado_postula')}</li>`;
        if (obtenerValor('colegio_procedencia')) {
            html += `<li style="margin-bottom: 8px;"><strong>Colegio anterior:</strong> ${obtenerValor('colegio_procedencia')}</li>`;
        }
        html += '</ul></div>';
        
        // Apoderado Principal
        html += '<div style="background: #f5f5f5; padding: 20px; border-radius: 10px;">';
        html += '<h4 style="color: #8B1538; margin-bottom: 15px; font-size: 1.2rem;">👨‍👩‍👧 Apoderado Principal</h4>';
        html += '<ul style="list-style: none; padding: 0;">';
        html += `<li style="margin-bottom: 8px;"><strong>Apoderado:</strong> ${obtenerValor('apoderado_principal')}</li>`;
        if (obtenerValor('apoderado_principal') === 'Otro') {
            html += `<li style="margin-bottom: 8px;"><strong>Nombre:</strong> ${obtenerValor('nombre_apoderado')}</li>`;
            html += `<li style="margin-bottom: 8px;"><strong>Parentesco:</strong> ${obtenerValor('parentesco_apoderado')}</li>`;
            html += `<li style="margin-bottom: 8px;"><strong>DNI:</strong> ${obtenerValor('dni_apoderado_otro')}</li>`;
        }
        html += `<li style="margin-bottom: 8px;"><strong>Celular:</strong> ${obtenerValor('celular_apoderado')}</li>`;
        html += `<li style="margin-bottom: 8px;"><strong>Email:</strong> ${obtenerValor('email_apoderado')}</li>`;
        html += '</ul></div>';
        
        // Documentos Adjuntos
        html += '<div style="background: #f5f5f5; padding: 20px; border-radius: 10px;">';
        html += '<h4 style="color: #8B1538; margin-bottom: 15px; font-size: 1.2rem;">📎 Documentos Adjuntos</h4>';
        html += '<ul style="list-style: none; padding: 0;">';
        
        const documentosMap = {
            'partida_nacimiento': 'Partida de Nacimiento',
            'dni_estudiante_doc': 'DNI del Estudiante',
            'dni_apoderado': 'DNI del Apoderado',
            'libreta_notas': 'Libreta de Notas',
            'certificado_estudios': 'Certificado de Estudios',
            'foto_estudiante': 'Foto del Estudiante',
            'comprobante_pago': 'Comprobante de Pago'
        };
        
        Object.keys(documentosMap).forEach(docId => {
            const input = document.getElementById(docId) || document.querySelector(`input[name="${docId}"]`);
            if (input && input.files && input.files[0]) {
                const fileSize = (input.files[0].size / 1024).toFixed(2);
                html += `<li style="margin-bottom: 8px;">✅ ${documentosMap[docId]}: ${input.files[0].name} (${fileSize} KB)</li>`;
            }
        });
        
        html += '</ul></div>';
        html += '</div>';
        
        resumenContainer.innerHTML = html;
    }
    
    function obtenerValor(id) {
        const elemento = document.getElementById(id);
        return elemento ? (elemento.value || '') : '';
    }
    
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
            
            // Log para debug
            console.log('📤 Datos a enviar:');
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
                console.log('📥 Respuesta del servidor:', data);
                
                if (data.exito) {
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
                console.error('❌ Error:', error);
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
        
        input[type="file"] {
            cursor: pointer;
        }
        
        input[type="file"]:hover {
            border-color: #8B1538;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✅ Sistema de admisiones iniciado correctamente - Versión 3.0 Final');
});

// ============================================
// FUNCIÓN GLOBAL PARA ELIMINAR ARCHIVO
// ============================================
function eliminarArchivo(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.value = '';
        
        let previewContainer = input.parentElement.querySelector('.archivo-preview');
        if (!previewContainer) {
            const previewId = 'preview_' + inputId;
            previewContainer = document.getElementById(previewId);
        }
        
        if (previewContainer) {
            previewContainer.innerHTML = '';
            previewContainer.classList.remove('mostrar');
        }
    }
}