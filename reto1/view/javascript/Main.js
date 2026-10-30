const appCssBaseUrl = new URL('../css/', document.currentScript.src);

document.addEventListener("DOMContentLoaded", async () => {
    // index.html?page=equipamientos)
    const urlParams = new URLSearchParams(window.location.search);
    let page = urlParams.get('page') || 'login';

    try {
        const res = await fetch('api/Login.php', { method: 'GET' });
        const sessionData = await res.json();

        if (sessionData.autenticado === false) {
            // NOT LOGGED IN
            if (page !== 'login') {
                window.location.href = 'index.html?page=login';
                return;
            }
            loadView('login');
            
        } else {
            // LOGGED IN
            const user = sessionData.usuario;
            window.ROL = user.rol; 

            if (page === 'login') {
                window.location.href = 'index.html?page=equipamientos';
                return;
            }

            // Security Check (Block USER from admin pages)
            if ((page === 'usuarios' || page === 'ubicaciones') && user.rol !== 'ADMIN') {
                window.location.href = 'index.html?page=equipamientos';
                return;
            }

            // Setup Navbar
            document.getElementById('main-navbar').style.display = 'flex';
            document.getElementById('main-footer').style.display = 'block';
            document.getElementById('nav-username').textContent = user.nombre;
            document.getElementById('nav-avatar').textContent = user.nombre.substring(0, 2).toUpperCase();

            if (user.rol === 'ADMIN') {
                document.getElementById('nav-ubicaciones').style.display = 'block';
                document.getElementById('nav-usuarios').style.display = 'block';
            }

            // Highlight the active tab
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.dataset.page === page) link.classList.add('active');
                else link.classList.remove('active');
            });

            // Load the specific view
            loadView(page, user.rol);
        }
    } catch (error) {
        console.error("Error checking session:", error);
        document.getElementById('app-content').innerHTML = '<p style="text-align:center; color:red;">Error de conexión. Revisa la consola.</p>';
    }
});

// =====================================================================
// AQUI ESTA LOS METODOS PARA CARGAR VISTAS DINAMICAMENTE
// =====================================================================
async function loadView(pageName, role) {
    const mainContent = document.getElementById('app-content');
    mainContent.innerHTML = '<p style="text-align:center; margin-top:50px;">Cargando vista...</p>';

    let htmlFile = '';
    let cssFile = '';
    let jsFile = '';

    // --- MAPA DE LOS ARCHIVOS ---
    if (pageName === 'login') { 
        htmlFile = 'Login'; // HTML -MIZARNE
        jsFile = 'Login';   // JS -MIZARNE
    } 
    else if (pageName === 'equipamientos') {
        htmlFile = 'Equipamientos'; // HTML -MIZARNE
        jsFile = 'Equipamiento';    // JS -MIZARNE
    } 
    else if (pageName === 'usuarios') {
        htmlFile = 'Usuarios'; // HTML - ADRIAN
        jsFile = 'Usuarios'; // JS - ADRIAN
    } 
    else if (pageName === 'ubicaciones') {
        htmlFile = 'Ubicaciones'; // HTML - ADRIAN
        cssFile = 'Ubicaciones';
        jsFile = 'Ubicaciones'; // JS - ADRIAN
    } 
    else if (pageName === 'notificaciones') {
        htmlFile = (role === 'ADMIN') ? 'NotificacionesAdmin' : 'NotificacionesLista'; // HTML - VICTOR
        jsFile = 'Notificaciones'; // JS - VICTOR
    } 
    else if (pageName === 'historico') {
        htmlFile = 'Historico'; // HTML -MIZARNE
        jsFile = 'Historico'; // JS -MIZARNE
    }

    // --- CARGAR HTML ---
    try {
        const res = await fetch(`view/html/${htmlFile}.html`);
        if (!res.ok) throw new Error(`No se encontró ${htmlFile}.html`);
        
        const html = await res.text();
        mainContent.innerHTML = html;

        loadPageCSS(cssFile);
        loadPageJS(jsFile);

    } catch (error) {
        console.error(error);
        mainContent.innerHTML = `<p style="text-align:center; color:red;">Error 404: No se encontró la vista ${htmlFile}.html</p>`;
    }
}

// =====================================================================
// DYNAMIC CSS LOADER
// =====================================================================
function loadPageCSS(cssFileName) {
    // Remove old specific CSS
    const oldCSS = document.getElementById('dynamic-css');
    if (oldCSS) oldCSS.remove();

    if (cssFileName) {
        const link = document.createElement('link');
        link.id = 'dynamic-css';
        link.rel = 'stylesheet';
        link.href = new URL(`${cssFileName}.css`, appCssBaseUrl).href;
        document.head.appendChild(link);
    }
}

// =====================================================================
//  DYNAMIC JS LOADER
// =====================================================================
function loadPageJS(jsFileName) {
    // Remove old specific JS
    const oldScript = document.getElementById('dynamic-script');
    if (oldScript) oldScript.remove();

    if (jsFileName) {
        const script = document.createElement('script');
        script.id = 'dynamic-script';
        script.src = `view/javascript/${jsFileName}.js`;
        script.onerror = () => console.error(`❌ ERROR 404: No se pudo cargar view/javascript/${jsFileName}.js`);
        document.body.appendChild(script);
    }
}

// =====================================================================
// NAVBAR UTILITIES
// =====================================================================
function toggleDropdown(event) {
    event.stopPropagation();
    document.getElementById('adminMenu').classList.toggle('show');
}
window.onclick = function(event) {
    const menu = document.getElementById('adminMenu');
    if (menu && menu.classList.contains('show')) menu.classList.remove('show');
}
async function logout() {
    await fetch('api/Login.php', { method: 'DELETE' });
    window.location.href = 'index.html?page=login';
}