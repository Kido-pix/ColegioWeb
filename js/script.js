// ====== DATOS DEL CARRUSEL ======
const logrosData = [
    {
        imagen: "img/Noticias.jpg",
        titulo: "Reconocimiento en feria de ciencia 2025",
        descripcion: "Nuestros estudiantes destacaron con proyectos innovadores en la feria regional."
    },
    {
        imagen: "img/Bienvenida.jpg",
        titulo: "Primer lugar en concurso regional de Matemática",
        descripcion: "Estudiantes del nivel secundario representaron al colegio y obtuvieron el primer puesto a nivel regional."
    },
    {
        imagen: "img/primaria.jpg",
        titulo: "Campeones de fútbol interescolar",
        descripcion: "Nuestro equipo demostró talento y trabajo en equipo en el torneo provincial."
    },
    {
        imagen: "img/noticia2.jpg",
        titulo: "Premio al mejor cuento juvenil",
        descripcion: "Estudiante gana concurso de literatura a nivel regional."
    }
];

let currentIndex = 0;
let isTransitioning = false;
let autoPlayInterval;

// ====== CARRUSEL DE LOGROS ======
function inicializarCarruselLogros() {
    const container = document.getElementById('carouselLogros');
    const indicators = document.getElementById('carouselIndicators');
    
    if (!container || !indicators) return;
    
    logrosData.forEach((logro, index) => {
        const item = crearLogroItem(logro, index);
        container.appendChild(item);
        
        const indicator = document.createElement('div');
        indicator.className = `indicator ${index === 0 ? 'active' : ''}`;
        indicator.onclick = () => irALogro(index);
        indicators.appendChild(indicator);
    });
    
    actualizarCarruselLogros();
    agregarEventosClickLogros();
}

function crearLogroItem(logro, index) {
    const item = document.createElement('div');
    item.className = 'logro-item';
    item.dataset.index = index;
    item.innerHTML = `
        <div class="logro-imagen">
            <img src="${logro.imagen}" alt="${logro.titulo}">
        </div>
        <div class="logro-contenido">
            <h3>${logro.titulo}</h3>
            <p class="logro-descripcion">${logro.descripcion}</p>
        </div>
    `;
    return item;
}

function actualizarCarruselLogros() {
    const items = document.querySelectorAll('.logro-item');
    const indicators = document.querySelectorAll('.indicator');
    const total = items.length;
    
    if (total === 0) return;
    
    currentIndex = ((currentIndex % total) + total) % total;
    
    const prevIndex = ((currentIndex - 1) + total) % total;
    const nextIndex = (currentIndex + 1) % total;
    
    items.forEach(item => item.classList.remove('side', 'center', 'prev', 'next'));
    
    items[prevIndex].classList.add('side', 'prev');
    items[currentIndex].classList.add('center');
    items[nextIndex].classList.add('side', 'next');
    
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentIndex);
    });
    
    const container = document.getElementById('carouselLogros');
    container.innerHTML = '';
    container.appendChild(items[prevIndex]);
    container.appendChild(items[currentIndex]);
    container.appendChild(items[nextIndex]);
    
    items.forEach((item, idx) => {
        item.style.display = (idx === prevIndex || idx === currentIndex || idx === nextIndex) ? 'block' : 'none';
    });
}

function agregarEventosClickLogros() {
    const container = document.getElementById('carouselLogros');
    if (!container) return;
    
    container.addEventListener('click', (e) => {
        const logroItem = e.target.closest('.logro-item');
        if (logroItem && logroItem.classList.contains('side') && !isTransitioning) {
            moverCarruselLogros(logroItem.classList.contains('prev') ? -1 : 1);
            detenerAutoPlayLogros();
            setTimeout(iniciarAutoPlayLogros, 5000);
        }
    });
}

function moverCarruselLogros(direction) {
    if (isTransitioning) return;
    isTransitioning = true;
    currentIndex += direction;
    actualizarCarruselLogros();
    setTimeout(() => { isTransitioning = false; }, 500);
}

function irALogro(index) {
    if (isTransitioning || index === currentIndex) return;
    isTransitioning = true;
    currentIndex = index;
    actualizarCarruselLogros();
    setTimeout(() => { isTransitioning = false; }, 500);
}

function iniciarAutoPlayLogros() {
    clearInterval(autoPlayInterval);
    autoPlayInterval = setInterval(() => moverCarruselLogros(1), 5000);
}

function detenerAutoPlayLogros() {
    clearInterval(autoPlayInterval);
}

// ====== CONTROL DE TECLADO Y TOUCH ======
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        moverCarruselLogros(-1);
        detenerAutoPlayLogros();
        setTimeout(iniciarAutoPlayLogros, 5000);
    } else if (e.key === 'ArrowRight') {
        moverCarruselLogros(1);
        detenerAutoPlayLogros();
        setTimeout(iniciarAutoPlayLogros, 5000);
    }
});

let touchStartX = 0;
let touchEndX = 0;

function handleSwipe() {
    if (touchEndX < touchStartX - 50) {
        moverCarruselLogros(1);
    } else if (touchEndX > touchStartX + 50) {
        moverCarruselLogros(-1);
    }
}

// ====== MENÚ DESPLEGABLE MEJORADO ======
function implementarMenuDesplegable() {
    const menuItems = document.querySelectorAll('.nav-links > li');
    
    menuItems.forEach(item => {
        const submenu = item.querySelector('.submenu');
        
        if (!submenu) return;
        
        let hideTimeout;
        
        // Mostrar menú al entrar
        item.addEventListener('mouseenter', function() {
            clearTimeout(hideTimeout);
            submenu.style.display = 'block';
            submenu.style.opacity = '1';
            submenu.style.visibility = 'visible';
            submenu.style.transform = 'translateY(0)';
            submenu.style.pointerEvents = 'auto';
        });
        
        // Ocultar menú al salir (con delay)
        item.addEventListener('mouseleave', function() {
            hideTimeout = setTimeout(function() {
                submenu.style.opacity = '0';
                submenu.style.visibility = 'hidden';
                submenu.style.transform = 'translateY(-10px)';
                submenu.style.pointerEvents = 'none';
                submenu.style.display = 'none';
            }, 100);
        });
    });
}


// ====== MENÚ HAMBURGUESA ======
function implementarMenuHamburguesa() {
    const toggleBtn = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (!toggleBtn || !navLinks) return;
    
    toggleBtn.addEventListener('click', () => {
        navLinks.classList.toggle('activo');
    });
    
    navLinks.querySelectorAll('a:not(.has-submenu)').forEach(link => {
        link.addEventListener('click', () => navLinks.classList.remove('activo'));
    });
    
    navLinks.querySelectorAll('.has-submenu').forEach(parent => {
        parent.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const submenu = parent.nextElementSibling;
                if (submenu?.classList.contains('submenu')) {
                    submenu.classList.toggle('activo');
                }
            }
        });
    });
}

// ====== SMOOTH SCROLL ======
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.length > 1) {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const navbar = document.querySelector('.navbar');
                const offset = navbar ? navbar.offsetHeight : 0;
                const targetPosition = target.offsetTop - offset - 20;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        }
    });
});

// ====== ANIMACIÓN AL HACER SCROLL ======
function animarElementosAlScroll() {
    const observerOptions = {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            } else if (entry.boundingClientRect.top > 0) {
                entry.target.classList.remove('visible');
            }
        });
    }, observerOptions);
    
    const elementosAnimar = document.querySelectorAll(`
        .bienvenida .texto,
        .bienvenida .imagen-container,
        .valor-card,
        .nivel,
        .noticia-card,
        .section-header,
        .section-label,
        .carousel,
        .carousel-indicators,
        .logro-item
    `);
    
    elementosAnimar.forEach(elemento => {
        if (!elemento.classList.contains('animate-on-scroll') && 
            !elemento.classList.contains('animate-from-left') && 
            !elemento.classList.contains('animate-from-right')) {
            elemento.classList.add('animate-on-scroll');
        }
        observer.observe(elemento);
    });
}

// ====== CONFIGURAR ANIMACIONES POR SECCIÓN ======
function configurarAnimacionesPorSeccion() {
    const bienvenidaTexto = document.querySelector('.bienvenida .texto');
    if (bienvenidaTexto) bienvenidaTexto.classList.add('animate-from-left');
    
    const bienvenidaImg = document.querySelector('.bienvenida .imagen-container');
    if (bienvenidaImg) bienvenidaImg.classList.add('animate-from-right');
    
    document.querySelectorAll('.section-header').forEach(header => {
        header.classList.add('animate-title');
    });
    
    document.querySelectorAll('.valor-card').forEach((card, index) => {
        card.classList.add('animate-bounce', `delay-${Math.min((index % 3 + 1) * 100, 600)}`);
    });
    
    document.querySelectorAll('.nivel').forEach((nivel, index) => {
        nivel.classList.add('animate-zoom', `delay-${Math.min((index % 4 + 1) * 100, 600)}`);
    });
    
    document.querySelectorAll('.noticia-card').forEach((noticia, index) => {
        noticia.classList.add(index % 2 === 0 ? 'animate-from-left' : 'animate-from-right', 
                             `delay-${Math.min((index % 3) * 100, 400)}`);
    });
    
    const carousel = document.querySelector('.carousel');
    if (carousel) carousel.classList.add('animate-zoom');
    
    const indicators = document.querySelector('.carousel-indicators');
    if (indicators) indicators.classList.add('animate-fade', 'delay-200');
}

// ====== EFECTO NAVBAR AL SCROLL ======
function efectoNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        if (currentScroll > 100) {
            navbar.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            navbar.style.padding = '12px 50px';
        } else {
            navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
            navbar.style.padding = '15px 50px';
        }
    });
}

// ====== VALIDAR FORMULARIO ======
function validarFormulario(formulario) {
    const campos = formulario.querySelectorAll('input[required], textarea[required]');
    let valido = true;
    
    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#B93737';
            valido = false;
        } else {
            campo.style.borderColor = '#ddd';
        }
    });
    
    return valido;
}

// ====== LAZY LOADING ======
function implementarLazyLoading() {
    const imagenes = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    imagenes.forEach(img => imageObserver.observe(img));
}

// ====== INICIALIZAR CUANDO CARGA LA PÁGINA ======
window.addEventListener('DOMContentLoaded', () => {
    // Resetear scroll
    window.scrollTo(0, 0);
    
    // Inicializar funciones
    configurarAnimacionesPorSeccion();
    animarElementosAlScroll();
    implementarMenuDesplegable();
    implementarMenuHamburguesa();
    efectoNavbarScroll();
    inicializarCarruselLogros();
    iniciarAutoPlayLogros();
    
    // Control del carrusel con mouse
    const carousel = document.querySelector('.carousel');
    if (carousel) {
        carousel.addEventListener('mouseenter', detenerAutoPlayLogros);
        carousel.addEventListener('mouseleave', iniciarAutoPlayLogros);
    }
    
    // Control del carrusel con touch
    const carouselTrack = document.querySelector('.carousel-track');
    if (carouselTrack) {
        carouselTrack.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        carouselTrack.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }
    
    console.log('%c🎓 Trinity School Website', 'color: #8B1538; font-size: 20px; font-weight: bold;');
    console.log('%cDesarrollado con ❤️ para la mejor educación', 'color: #D4AF37; font-size: 12px;');
});

// ====== MANEJADOR DE ERRORES ======
window.addEventListener('error', (e) => {
    if (e.message.includes('Failed to fetch')) {
        console.warn('⚠️ No se pudo cargar un recurso externo');
        e.preventDefault();
    }
});