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
            window.USUARIO_ID = user.id_usuario;

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

            initializeNotificationDropdown(user.id_usuario);

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
        jsFile = 'Ubicaciones'; // JS - ADRIAN
    } 
    else if (pageName === 'notificaciones') {
        const showNotificationList = new URLSearchParams(window.location.search).get('vista') === 'lista';
        htmlFile = (role === 'ADMIN' && !showNotificationList) ? 'NotificacionesAdmin' : 'NotificacionesLista';
        if (htmlFile === 'NotificacionesLista') jsFile = 'Notificaciones';
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
        link.href = `view/css/${cssFileName}.css`;
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

let notificationUserId = null;
let notifications = [];

function notificationStorageKey() {
    return `ordenza-notificaciones-leidas-${notificationUserId}`;
}

function getReadNotificationIds() {
    const savedIds = localStorage.getItem(notificationStorageKey());
    if (savedIds === null) return new Set();

    const parsedIds = JSON.parse(savedIds);
    if (!Array.isArray(parsedIds)) throw new Error('El estado de lectura guardado no es válido.');
    return new Set(parsedIds.map(String));
}

function updateNotificationBadge() {
    const badge = document.getElementById('notificationBadge');
    const readIds = getReadNotificationIds();
    const unreadCount = notifications.filter(notification => !readIds.has(String(notification.id_notificacion))).length;

    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.hidden = unreadCount === 0;
}

function renderNotificationItems(items) {
    const list = document.getElementById('notificationList');
    list.replaceChildren();

    if (items.length === 0) {
        const message = document.createElement('p');
        message.className = 'notification-menu__message';
        message.textContent = 'No tienes notificaciones sin leer.';
        list.appendChild(message);
        return;
    }

    items.forEach(notification => {
        const item = document.createElement('article');
        item.className = 'notification-menu__item';

        const title = document.createElement('h3');
        title.textContent = notification.titulo;
        item.appendChild(title);

        const description = document.createElement('p');
        description.textContent = notification.descripcion;
        item.appendChild(description);

        const date = document.createElement('time');
        date.className = 'notification-menu__date';
        date.textContent = notification.fecha_creacion;
        date.dateTime = notification.fecha_creacion;
        item.appendChild(date);

        list.appendChild(item);
    });
}

async function fetchNotifications() {
    const response = await fetch('api/Notificaciones.php?mine=1');
    if (!response.ok) throw new Error(`No se pudieron cargar las notificaciones (${response.status}).`);

    const result = await response.json();
    if (!Array.isArray(result)) throw new Error('La respuesta de notificaciones no es válida.');

    notifications = result;
    updateNotificationBadge();
}

function initializeNotificationDropdown(userId) {
    notificationUserId = userId;
    fetchNotifications().catch(error => {
        console.error('Error al cargar las notificaciones:', error);
    });
}

async function toggleNotifications(event) {
    event.stopPropagation();
    const menu = document.getElementById('notificationMenu');
    const toggle = document.getElementById('notificationToggle');

    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
        return;
    }

    menu.classList.add('show');
    toggle.setAttribute('aria-expanded', 'true');
    const list = document.getElementById('notificationList');
    list.replaceChildren();
    const loadingMessage = document.createElement('p');
    loadingMessage.className = 'notification-menu__message';
    loadingMessage.textContent = 'Cargando notificaciones...';
    list.appendChild(loadingMessage);

    try {
        await fetchNotifications();
        const readIds = getReadNotificationIds();
        const unreadNotifications = notifications.filter(
            notification => !readIds.has(String(notification.id_notificacion))
        );

        renderNotificationItems(unreadNotifications);
        unreadNotifications.forEach(notification => readIds.add(String(notification.id_notificacion)));
        localStorage.setItem(notificationStorageKey(), JSON.stringify([...readIds]));
        updateNotificationBadge();
    } catch (error) {
        console.error('Error al abrir las notificaciones:', error);
        list.replaceChildren();
        const message = document.createElement('p');
        message.className = 'notification-menu__message';
        message.textContent = 'No se pudieron cargar las notificaciones. Inténtalo de nuevo.';
        list.appendChild(message);
    }
}

window.onclick = function(event) {
    const menu = document.getElementById('adminMenu');
    if (menu && menu.classList.contains('show')) menu.classList.remove('show');
    const notificationMenu = document.getElementById('notificationMenu');
    if (notificationMenu && notificationMenu.classList.contains('show')) {
        notificationMenu.classList.remove('show');
        document.getElementById('notificationToggle').setAttribute('aria-expanded', 'false');
    }
}
async function logout() {
    await fetch('api/Login.php', { method: 'DELETE' });
    window.location.href = 'index.html?page=login';
}