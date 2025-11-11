// ====== GESTOR DE MODO OSCURO ======
class DarkModeManager {
    constructor() {
        this.darkModeBtn = document.getElementById('darkModeBtn');
        this.storageKey = 'trinity-dark-mode';
        this.init();
    }
    
    init() {
        if (!this.darkModeBtn) return;
        
        // Cargar preferencia guardada
        this.loadSavedPreference();
        
        // Event listener del botón
        this.darkModeBtn.addEventListener('click', () => this.toggleDarkMode());
        
        // Detectar cambio en preferencias del sistema
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addListener(() => {
                this.applySystemPreference();
            });
        }
    }
    
    loadSavedPreference() {
        const savedPreference = localStorage.getItem(this.storageKey);
        
        if (savedPreference) {
            // Usar preferencia guardada
            if (savedPreference === 'dark') {
                this.enableDarkMode();
            } else {
                this.disableDarkMode();
            }
        } else {
            // Usar preferencia del sistema
            this.applySystemPreference();
        }
    }
    
    applySystemPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            this.enableDarkMode();
        } else {
            this.disableDarkMode();
        }
    }
    
    toggleDarkMode() {
        if (document.body.classList.contains('dark-mode')) {
            this.disableDarkMode();
        } else {
            this.enableDarkMode();
        }
    }
    
    enableDarkMode() {
        document.body.classList.add('dark-mode');
        if (this.darkModeBtn) {
            this.darkModeBtn.textContent = '☀️';
            this.darkModeBtn.setAttribute('title', 'Cambiar a modo claro');
        }
        localStorage.setItem(this.storageKey, 'dark');
        this.updateMetaTheme('#0f0f10');
    }
    
    disableDarkMode() {
        document.body.classList.remove('dark-mode');
        if (this.darkModeBtn) {
            this.darkModeBtn.textContent = '🌙';
            this.darkModeBtn.setAttribute('title', 'Cambiar a modo oscuro');
        }
        localStorage.setItem(this.storageKey, 'light');
        this.updateMetaTheme('#ffffff');
    }
    
    updateMetaTheme(color) {
        let metaTheme = document.querySelector('meta[name="theme-color"]');
        if (!metaTheme) {
            metaTheme = document.createElement('meta');
            metaTheme.name = 'theme-color';
            document.head.appendChild(metaTheme);
        }
        metaTheme.content = color;
    }
}

// ====== INICIALIZAR CUANDO CARGA LA PÁGINA ======
document.addEventListener('DOMContentLoaded', () => {
    new DarkModeManager();
    console.log('✓ Sistema de modo oscuro inicializado');
});