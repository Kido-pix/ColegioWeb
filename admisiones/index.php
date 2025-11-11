<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admisiones 2025 | Colegio Trinity School</title>
    
    <!-- CSS de la página principal -->
    <link rel="stylesheet" href="../css/Estilo.css">
    <link rel="stylesheet" href="../css/darkmode.css">
    
    <!-- CSS específico de admisiones -->
    <link rel="stylesheet" href="css/admisiones.css">
    
    <!-- Fuente Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- NAVBAR (copiado de tu página principal) -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="../index.html" class="logo-link">
                    <img src="../img/logo.png" alt="Logo Colegio Trinity School" />
                    <div class="logo-text">
                        <h1>Colegio<br>Trinity School</h1>
                        <p>Chincha Alta</p>
                    </div>
                </a>
            </div>

            <button class="menu-toggle" aria-label="Abrir menú de navegación">☰</button>

            <ul class="nav-links">
                <li><a href="../pages/Nosotros.html">Nosotros</a></li>
                <li>
                    <a href="../pages/Oferta.html" class="has-submenu">Oferta educativa</a>
                    <ul class="submenu">
                        <li><a href="../pages/Inicial.html">Nivel Inicial</a></li>
                        <li><a href="../pages/Primaria.html">Nivel Primaria</a></li>
                        <li><a href="../pages/Secundaria.html">Nivel Secundaria</a></li>
                        <li><a href="../pages/Talleres.html">Talleres</a></li>
                    </ul>
                </li>
                <li><a href="../pages/Noticias.html">Noticias y Eventos</a></li>
                <li><a href="../pages/Galeria.html">Galería</a></li>
                <li><a href="../pages/Contacto.html">Contacto</a></li>
                <li><a href="index.php" class="btn-admisiones">Admisiones</a></li>
            </ul>

            <div class="darkmode-toggle">
                <button id="darkModeBtn" aria-label="Cambiar modo oscuro">🌙</button>
            </div>
        </nav>
    </header>

    <!-- CONTENIDO DE ADMISIONES -->
    <main class="admisiones-main">
        
        <!-- HERO SECTION -->
        <section class="admisiones-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge">📚 PROCESO DE ADMISIÓN 2025</span>
                <h1>¡Únete a la Familia Trinity!</h1>
                <p>Completa tu solicitud de admisión y forma parte de nuestra comunidad educativa</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">20+</span>
                        <span class="stat-label">Años de experiencia</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Estudiantes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Satisfacción</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- INFORMACIÓN DEL PROCESO -->
        <section class="proceso-info">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Proceso Simple</span>
                    <h2>¿Cómo funciona?</h2>
                    <div class="divider-center"></div>
                </div>

                <div class="pasos-grid">
                    <div class="paso-card">
                        <div class="paso-numero">1</div>
                        <div class="paso-icono">📝</div>
                        <h3>Completa el formulario</h3>
                        <p>Llena todos los datos del estudiante y apoderado</p>
                    </div>

                    <div class="paso-card">
                        <div class="paso-numero">2</div>
                        <div class="paso-icono">📎</div>
                        <h3>Adjunta documentos</h3>
                        <p>Sube los documentos requeridos en formato PDF o imagen</p>
                    </div>

                    <div class="paso-card">
                        <div class="paso-numero">3</div>
                        <div class="paso-icono">✉️</div>
                        <h3>Recibe confirmación</h3>
                        <p>Te enviaremos un email con tu código de postulante</p>
                    </div>

                    <div class="paso-card">
                        <div class="paso-numero">4</div>
                        <div class="paso-icono">🎯</div>
                        <h3>Entrevista personal</h3>
                        <p>Coordinaremos una entrevista con el apoderado</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- DOCUMENTOS REQUERIDOS -->
        <section class="documentos-requeridos">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Importante</span>
                    <h2>Documentos Necesarios</h2>
                    <div class="divider-center"></div>
                    <p>Por favor, ten listos los siguientes documentos en formato digital (PDF, JPG o PNG)</p>
                </div>

                <div class="documentos-grid">
                    <div class="documento-item">
                        <span class="doc-icon">📄</span>
                        <h4>Partida de Nacimiento</h4>
                        <span class="doc-badge obligatorio">Obligatorio</span>
                    </div>

                    <div class="documento-item">
                        <span class="doc-icon">🪪</span>
                        <h4>DNI del Estudiante</h4>
                        <span class="doc-badge obligatorio">Obligatorio</span>
                    </div>

                    <div class="documento-item">
                        <span class="doc-icon">🪪</span>
                        <h4>DNI del Apoderado</h4>
                        <span class="doc-badge obligatorio">Obligatorio</span>
                    </div>

                    <div class="documento-item">
                        <span class="doc-icon">📚</span>
                        <h4>Libreta de Notas</h4>
                        <span class="doc-badge obligatorio">Obligatorio</span>
                    </div>

                    <div class="documento-item">
                        <span class="doc-icon">📜</span>
                        <h4>Certificado de Estudios</h4>
                        <span class="doc-badge opcional">Opcional</span>
                    </div>

                    <div class="documento-item">
                        <span class="doc-icon">📸</span>
                        <h4>Foto del Estudiante</h4>
                        <span class="doc-badge obligatorio">Obligatorio</span>
                    </div>
                </div>

                <div class="documentos-nota">
                    <strong>📌 Nota importante:</strong> Los archivos deben ser menores a 5MB. 
                    Formatos aceptados: PDF, JPG, PNG.
                </div>
            </div>
        </section>

        <!-- BOTÓN PRINCIPAL DE INICIO -->
        <section class="inicio-formulario">
            <div class="container">
                <div class="cta-box">
                    <h2>¿Listo para comenzar?</h2>
                    <p>El proceso de registro toma aproximadamente 10-15 minutos</p>
                    <button class="btn-iniciar-registro" id="btnIniciarRegistro">
                        <span>Iniciar Registro</span>
                        <span class="btn-icon">→</span>
                    </button>
                    <p class="cta-nota">Una vez iniciado, podrás guardar y continuar después</p>
                </div>
            </div>
        </section>

        <!-- FORMULARIO (se mostrará cuando se haga clic en "Iniciar Registro") -->
        <section class="formulario-admisiones" id="formularioAdmisiones" style="display: none;">
            <div class="container">
                <div class="formulario-wrapper">
                    
                    <!-- Indicador de progreso -->
                    <div class="progreso-pasos">
                        <div class="paso-progreso activo" data-paso="1">
                            <div class="paso-circulo">1</div>
                            <span class="paso-texto">Datos del Estudiante</span>
                        </div>
                        <div class="paso-progreso" data-paso="2">
                            <div class="paso-circulo">2</div>
                            <span class="paso-texto">Información Académica</span>
                        </div>
                        <div class="paso-progreso" data-paso="3">
                            <div class="paso-circulo">3</div>
                            <span class="paso-texto">Datos Familiares</span>
                        </div>
                        <div class="paso-progreso" data-paso="4">
                            <div class="paso-circulo">4</div>
                            <span class="paso-texto">Documentos</span>
                        </div>
                        <div class="paso-progreso" data-paso="5">
                            <div class="paso-circulo">5</div>
                            <span class="paso-texto">Confirmar</span>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form id="formAdmision" class="formulario-contenido" method="POST" enctype="multipart/form-data">
                        
                        <!-- PASO 1: DATOS DEL ESTUDIANTE -->
                        <div class="paso-contenido activo" data-paso="1">
                            <h3 class="paso-titulo">
                                <span class="paso-icon">👤</span>
                                Datos del Estudiante
                            </h3>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nombres">Nombres completos <span class="required">*</span></label>
                                    <input type="text" id="nombres" name="nombres" required 
                                           placeholder="Ej: Juan Carlos">
                                </div>

                                <div class="form-group">
                                    <label for="apellidos">Apellidos completos <span class="required">*</span></label>
                                    <input type="text" id="apellidos" name="apellidos" required 
                                           placeholder="Ej: García Pérez">
                                </div>

                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de nacimiento <span class="required">*</span></label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                </div>

                                <div class="form-group">
                                    <label for="dni_estudiante">DNI <span class="required">*</span></label>
                                    <input type="text" id="dni_estudiante" name="dni_estudiante" required 
                                           maxlength="8" placeholder="8 dígitos">
                                </div>

                                <div class="form-group">
                                    <label for="sexo">Sexo <span class="required">*</span></label>
                                    <select id="sexo" name="sexo" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="lugar_nacimiento">Lugar de nacimiento</label>
                                    <input type="text" id="lugar_nacimiento" name="lugar_nacimiento" 
                                           placeholder="Ej: Chincha, Ica">
                                </div>

                                <div class="form-group form-group-full">
                                    <label for="direccion">Dirección completa <span class="required">*</span></label>
                                    <input type="text" id="direccion" name="direccion" required 
                                           placeholder="Calle, número, urbanización, etc.">
                                </div>

                                <div class="form-group">
                                    <label for="distrito">Distrito <span class="required">*</span></label>
                                    <input type="text" id="distrito" name="distrito" required 
                                           placeholder="Ej: Chincha Alta">
                                </div>

                                <div class="form-group">
                                    <label for="provincia">Provincia</label>
                                    <input type="text" id="provincia" name="provincia" 
                                           value="Chincha" placeholder="Ej: Chincha">
                                </div>
                            </div>
                        </div>

                        <!-- PASO 2: INFORMACIÓN ACADÉMICA -->
                        <div class="paso-contenido" data-paso="2">
                            <h3 class="paso-titulo">
                                <span class="paso-icon">🎓</span>
                                Información Académica
                            </h3>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nivel_postula">Nivel al que postula <span class="required">*</span></label>
                                    <select id="nivel_postula" name="nivel_postula" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Inicial">Inicial</option>
                                        <option value="Primaria">Primaria</option>
                                        <option value="Secundaria">Secundaria</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="grado_postula">Grado <span class="required">*</span></label>
                                    <select id="grado_postula" name="grado_postula" required disabled>
                                        <option value="">Seleccione primero el nivel</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="colegio_procedencia">Colegio de procedencia</label>
                                    <input type="text" id="colegio_procedencia" name="colegio_procedencia" 
                                           placeholder="Nombre del colegio anterior">
                                </div>

                                <div class="form-group">
                                    <label for="tipo_colegio">Tipo de colegio</label>
                                    <select id="tipo_colegio" name="tipo_colegio">
                                        <option value="">Seleccionar...</option>
                                        <option value="Público">Público</option>
                                        <option value="Privado">Privado</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="promedio_anterior">Promedio del año anterior</label>
                                    <input type="number" id="promedio_anterior" name="promedio_anterior" 
                                           min="0" max="20" step="0.1" placeholder="Ej: 15.5">
                                </div>
                            </div>

                            <div class="info-box">
                                <strong>ℹ️ Información:</strong> Si el estudiante está postulando a Inicial de 3 años 
                                o no tiene experiencia escolar previa, puede dejar los campos opcionales en blanco.
                            </div>
                        </div>

                        <!-- PASO 3: DATOS FAMILIARES -->
                        <div class="paso-contenido" data-paso="3">
                            <h3 class="paso-titulo">
                                <span class="paso-icon">👨‍👩‍👧</span>
                                Datos Familiares
                            </h3>

                            <!-- Datos del Padre -->
                            <div class="seccion-familia">
                                <h4 class="subtitulo-seccion">📋 Datos del Padre</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="nombre_padre">Nombres y apellidos</label>
                                        <input type="text" id="nombre_padre" name="nombre_padre" 
                                               placeholder="Nombres completos del padre">
                                    </div>

                                    <div class="form-group">
                                        <label for="dni_padre">DNI</label>
                                        <input type="text" id="dni_padre" name="dni_padre" 
                                               maxlength="8" placeholder="8 dígitos">
                                    </div>

                                    <div class="form-group">
                                        <label for="celular_padre">Celular</label>
                                        <input type="tel" id="celular_padre" name="celular_padre" 
                                               maxlength="9" placeholder="9 dígitos">
                                    </div>

                                    <div class="form-group">
                                        <label for="email_padre">Email</label>
                                        <input type="email" id="email_padre" name="email_padre" 
                                               placeholder="correo@ejemplo.com">
                                    </div>

                                    <div class="form-group">
                                        <label for="ocupacion_padre">Ocupación</label>
                                        <input type="text" id="ocupacion_padre" name="ocupacion_padre" 
                                               placeholder="Profesión u oficio">
                                    </div>
                                </div>
                            </div>

                            <!-- Datos de la Madre -->
                            <div class="seccion-familia">
                                <h4 class="subtitulo-seccion">📋 Datos de la Madre</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="nombre_madre">Nombres y apellidos</label>
                                        <input type="text" id="nombre_madre" name="nombre_madre" 
                                               placeholder="Nombres completos de la madre">
                                    </div>

                                    <div class="form-group">
                                        <label for="dni_madre">DNI</label>
                                        <input type="text" id="dni_madre" name="dni_madre" 
                                               maxlength="8" placeholder="8 dígitos">
                                    </div>

                                    <div class="form-group">
                                        <label for="celular_madre">Celular</label>
                                        <input type="tel" id="celular_madre" name="celular_madre" 
                                               maxlength="9" placeholder="9 dígitos">
                                    </div>

                                    <div class="form-group">
                                        <label for="email_madre">Email</label>
                                        <input type="email" id="email_madre" name="email_madre" 
                                               placeholder="correo@ejemplo.com">
                                    </div>

                                    <div class="form-group">
                                        <label for="ocupacion_madre">Ocupación</label>
                                        <input type="text" id="ocupacion_madre" name="ocupacion_madre" 
                                               placeholder="Profesión u oficio">
                                    </div>
                                </div>
                            </div>

                            <!-- Apoderado Principal -->
                            <div class="seccion-familia destacada">
                                <h4 class="subtitulo-seccion">⭐ Apoderado Principal</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="apoderado_principal">¿Quién será el apoderado? <span class="required">*</span></label>
                                        <select id="apoderado_principal" name="apoderado_principal" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Padre">Padre</option>
                                            <option value="Madre">Madre</option>
                                            <option value="Otro">Otro familiar o tutor</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Campos adicionales si es "Otro" -->
                                <div id="datosOtroApoderado" style="display: none;">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="nombre_apoderado">Nombre del apoderado <span class="required">*</span></label>
                                            <input type="text" id="nombre_apoderado" name="nombre_apoderado" 
                                                   placeholder="Nombres y apellidos completos">
                                        </div>

                                        <div class="form-group">
                                            <label for="parentesco_apoderado">Parentesco <span class="required">*</span></label>
                                            <input type="text" id="parentesco_apoderado" name="parentesco_apoderado" 
                                                   placeholder="Ej: Tío, Abuelo, Tutor legal">
                                        </div>

                                        <div class="form-group">
                                            <label for="dni_apoderado">DNI del apoderado <span class="required">*</span></label>
                                            <input type="text" id="dni_apoderado" name="dni_apoderado" 
                                                   maxlength="8" placeholder="8 dígitos">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="celular_apoderado">Celular del apoderado <span class="required">*</span></label>
                                        <input type="tel" id="celular_apoderado" name="celular_apoderado" required 
                                               maxlength="9" placeholder="9 dígitos">
                                    </div>

                                    <div class="form-group">
                                        <label for="email_apoderado">Email del apoderado <span class="required">*</span></label>
                                        <input type="email" id="email_apoderado" name="email_apoderado" required 
                                               placeholder="correo@ejemplo.com">
                                    </div>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <div class="seccion-familia">
                                <h4 class="subtitulo-seccion">ℹ️ Información Adicional</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="tiene_hermanos" name="tiene_hermanos" value="1">
                                            ¿Tiene hermanos estudiando en Trinity School?
                                        </label>
                                    </div>

                                    <div class="form-group form-group-full" id="campoHermanos" style="display: none;">
                                        <label for="nombres_hermanos">Nombres de los hermanos</label>
                                        <textarea id="nombres_hermanos" name="nombres_hermanos" rows="2" 
                                                  placeholder="Nombres y grados de los hermanos"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="necesidades_especiales" name="necesidades_especiales" value="1">
                                            ¿El estudiante tiene necesidades especiales?
                                        </label>
                                    </div>

                                    <div class="form-group form-group-full" id="campoNecesidades" style="display: none;">
                                        <label for="descripcion_necesidades">Descripción</label>
                                        <textarea id="descripcion_necesidades" name="descripcion_necesidades" rows="3" 
                                                  placeholder="Describa las necesidades especiales"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 4: DOCUMENTOS -->
                        <div class="paso-contenido" data-paso="4">
                            <h3 class="paso-titulo">
                                <span class="paso-icon">📎</span>
                                Documentos Requeridos
                            </h3>

                            <div class="documentos-upload">
                                <!-- Partida de nacimiento -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>📄 Partida de Nacimiento</h4>
                                        <span class="doc-badge obligatorio">Obligatorio</span>
                                    </div>
                                    <input type="file" id="doc_partida" name="doc_partida" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <label for="doc_partida" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir archivo</span>
                                        <span class="upload-info">PDF, JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_partida"></div>
                                </div>

                                <!-- DNI Estudiante -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>🪪 DNI del Estudiante</h4>
                                        <span class="doc-badge obligatorio">Obligatorio</span>
                                    </div>
                                    <input type="file" id="doc_dni_estudiante" name="doc_dni_estudiante" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <label for="doc_dni_estudiante" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir archivo</span>
                                        <span class="upload-info">PDF, JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_dni_estudiante"></div>
                                </div>

                                <!-- DNI Apoderado -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>🪪 DNI del Apoderado</h4>
                                        <span class="doc-badge obligatorio">Obligatorio</span>
                                    </div>
                                    <input type="file" id="doc_dni_apoderado" name="doc_dni_apoderado" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <label for="doc_dni_apoderado" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir archivo</span>
                                        <span class="upload-info">PDF, JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_dni_apoderado"></div>
                                </div>

                                <!-- Libreta de notas -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>📚 Libreta de Notas</h4>
                                        <span class="doc-badge obligatorio">Obligatorio</span>
                                    </div>
                                    <input type="file" id="doc_libreta" name="doc_libreta" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <label for="doc_libreta" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir archivo</span>
                                        <span class="upload-info">PDF, JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_libreta"></div>
                                </div>

                                <!-- Certificado (opcional) -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>📜 Certificado de Estudios</h4>
                                        <span class="doc-badge opcional">Opcional</span>
                                    </div>
                                    <input type="file" id="doc_certificado" name="doc_certificado" accept=".pdf,.jpg,.jpeg,.png">
                                    <label for="doc_certificado" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir archivo</span>
                                        <span class="upload-info">PDF, JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_certificado"></div>
                                </div>

                                <!-- Foto del estudiante -->
                                <div class="upload-item">
                                    <div class="upload-header">
                                        <h4>📸 Foto del Estudiante</h4>
                                        <span class="doc-badge obligatorio">Obligatorio</span>
                                    </div>
                                    <input type="file" id="doc_foto" name="doc_foto" accept=".jpg,.jpeg,.png" required>
                                    <label for="doc_foto" class="upload-label">
                                        <span class="upload-icon">☁️</span>
                                        <span class="upload-text">Clic para subir foto</span>
                                        <span class="upload-info">JPG o PNG - Máx. 5MB</span>
                                    </label>
                                    <div class="archivo-preview" id="preview_foto"></div>
                                </div>
                            </div>
                        </div>

                        <!-- PASO 5: CONFIRMACIÓN -->
                        <div class="paso-contenido" data-paso="5">
                            <h3 class="paso-titulo">
                                <span class="paso-icon">✅</span>
                                Revisar y Confirmar
                            </h3>

                            <div class="resumen-datos" id="resumenDatos">
                                <!-- Aquí se mostrará el resumen con JavaScript -->
                            </div>

                            <div class="terminos-condiciones">
                                <label class="checkbox-custom">
                                    <input type="checkbox" id="aceptar_terminos" name="aceptar_terminos" required>
                                    <span>Acepto los <a href="#" target="_blank">términos y condiciones</a> y autorizo el uso de mis datos personales conforme a la <a href="#" target="_blank">política de privacidad</a></span>
                                </label>
                            </div>
                        </div>

                        <!-- Botones de navegación -->
                        <div class="form-navegacion">
                            <button type="button" class="btn-anterior" id="btnAnterior" style="display: none;">
                                ← Anterior
                            </button>
                            <button type="button" class="btn-siguiente" id="btnSiguiente">
                                Siguiente →
                            </button>
                            <button type="submit" class="btn-enviar" id="btnEnviar" style="display: none;">
                                <span>Enviar Solicitud</span>
                                <span class="btn-icon">✓</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER (copiado de tu página principal) -->
    <footer>
        <div class="footer-contenido">
            <div class="footer-seccion">
                <div class="footer-logo">
                    <img src="../img/logo.png" alt="Logo Trinity">
                    <p>Colegio Trinity School<br>Chincha Alta</p>
                </div>
            </div>

            <div class="footer-seccion">
                <h4>Enlaces rápidos</h4>
                <ul>
                    <li><a href="../pages/Nosotros.html">Nosotros</a></li>
                    <li><a href="../pages/Oferta.html">Oferta Educativa</a></li>
                    <li><a href="../pages/Noticias.html">Noticias</a></li>
                    <li><a href="../pages/Galeria.html">Galería</a></li>
                </ul>
            </div>

            <div class="footer-seccion">
                <h4>Niveles</h4>
                <ul>
                    <li><a href="../pages/Inicial.html">Inicial</a></li>
                    <li><a href="../pages/Primaria.html">Primaria</a></li>
                    <li><a href="../pages/Secundaria.html">Secundaria</a></li>
                    <li><a href="../pages/Talleres.html">Talleres</a></li>
                </ul>
            </div>

            <div class="footer-seccion">
                <h4>Contacto</h4>
                <div class="footer-info">
                    <p>📍 Jr. Educación 123, Chincha Alta</p>
                    <p>📞 (056) 123456</p>
                    <p>✉️ contacto@trinityschool.edu.pe</p>
                </div>
            </div>
        </div>
        <p class="footer-copy">© 2025 Colegio Trinity School. Todos los derechos reservados.</p>
    </footer>

    <!-- Scripts -->
    <script src="../js/script.js"></script>
    <script src="../js/darkmode.js"></script>
    <script src="js/admisiones.js"></script>
</body>
</html>