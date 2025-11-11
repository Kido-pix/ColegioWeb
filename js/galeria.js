// ============================================
// GALERÍA - FUNCIONALIDAD
// ============================================

function inicializarGaleria() {
    const modal = document.getElementById('imagenModal');
    const imagenModal = document.getElementById('imagenModal-img');
    const cerrarModal = document.querySelector('.cerrar-modal');
    const btnAnterior = document.querySelector('.modal-anterior');
    const btnSiguiente = document.querySelector('.modal-siguiente');
    const fotos = document.querySelectorAll('.galeria-foto');
    const filtros = document.querySelectorAll('.filtro-btn');
    
    let indiceActual = 0;
    let fotosVisibles = Array.from(fotos);

    // ============================================
    // ABRIR MODAL - Click en fotos
    // ============================================
    fotos.forEach((foto) => {
        foto.addEventListener('click', function(e) {
            e.preventDefault();
            if (fotosVisibles.includes(this) && this.style.display !== 'none') {
                indiceActual = fotosVisibles.indexOf(this);
                mostrarImagen(indiceActual);
                modal.classList.add('activo');
            }
        });
    });

    // ============================================
    // CERRAR MODAL
    // ============================================
    if (cerrarModal) {
        cerrarModal.addEventListener('click', function() {
            modal.classList.remove('activo');
        });
    }

    if (modal) {
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.classList.remove('activo');
            }
        });
    }

    // ============================================
    // NAVEGACIÓN EN MODAL
    // ============================================
    if (btnAnterior) {
        btnAnterior.addEventListener('click', function(e) {
            e.stopPropagation();
            if (fotosVisibles.length > 0) {
                indiceActual = (indiceActual - 1 + fotosVisibles.length) % fotosVisibles.length;
                mostrarImagen(indiceActual);
            }
        });
    }

    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', function(e) {
            e.stopPropagation();
            if (fotosVisibles.length > 0) {
                indiceActual = (indiceActual + 1) % fotosVisibles.length;
                mostrarImagen(indiceActual);
            }
        });
    }

    // Navegación con teclas
    document.addEventListener('keydown', function(event) {
        if (modal && modal.classList.contains('activo')) {
            if (event.key === 'ArrowLeft' && btnAnterior) {
                btnAnterior.click();
            } else if (event.key === 'ArrowRight' && btnSiguiente) {
                btnSiguiente.click();
            } else if (event.key === 'Escape' && cerrarModal) {
                cerrarModal.click();
            }
        }
    });

    // ============================================
    // MOSTRAR IMAGEN EN MODAL
    // ============================================
    function mostrarImagen(index) {
        if (!fotosVisibles[index]) return;
        
        const foto = fotosVisibles[index];
        const img = foto.querySelector('img');
        const tituloEl = foto.querySelector('.foto-info h3');
        const categoriaEl = foto.querySelector('.foto-info p');

        if (img && imagenModal) {
            imagenModal.src = img.src;
        }
        
        if (tituloEl) {
            document.getElementById('modalTitulo').textContent = tituloEl.textContent;
        }
        
        if (categoriaEl) {
            document.getElementById('modalCategoria').textContent = categoriaEl.textContent;
        }
    }

    // ============================================
    // FILTROS - EVENTO CLICK
    // ============================================
    filtros.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const filtroActivo = this.getAttribute('data-filtro');

            // Actualizar botones activos
            filtros.forEach(b => b.classList.remove('activo'));
            this.classList.add('activo');

            // Filtrar fotos
            fotos.forEach(foto => {
                const categoriaFoto = foto.getAttribute('data-filtro');
                
                if (filtroActivo === 'todos' || categoriaFoto === filtroActivo) {
                    foto.classList.remove('oculto');
                    foto.style.display = 'block';
                } else {
                    foto.classList.add('oculto');
                    foto.style.display = 'none';
                }
            });

            // Actualizar fotos visibles
            fotosVisibles = Array.from(fotos).filter(foto => 
                foto.style.display !== 'none'
            );
            
            indiceActual = 0;
        });
    });
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarGaleria);
} else {
    inicializarGaleria();
}