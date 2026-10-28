document.addEventListener("DOMContentLoaded", async () => {
    const urlParams = new URLSearchParams(window.location.search);
    let page = urlParams.get('page') || 'login';

    try {
        const res = await fetch('api/Login.php', { method: 'GET' });
        const sessionData = await res.json();

        if (sessionData.autenticado === false) {
            if (page !== 'login') {
                window.location.href = 'index.html?page=login';
                return;
            }
            loadView('Login', 'login');
        } else {
            const user = sessionData.usuario;
            window.ROL = user.rol; 

            if (page === 'login') {
                window.location.href = 'index.html?page=equipamientos';
                return;
            }

            if ((page === 'usuarios' || page === 'ubicaciones') && user.rol !== 'ADMIN') {
                window.location.href = 'index.html?page=equipamientos';
                return;
            }

            document.getElementById('main-navbar').style.display = 'flex';
            document.getElementById('main-footer').style.display = 'block';
            document.getElementById('nav-username').textContent = user.nombre;
            document.getElementById('nav-avatar').textContent = user.nombre.substring(0, 2).toUpperCase();

            if (user.rol === 'ADMIN') {
                document.getElementById('nav-ubicaciones').style.display = 'block';
                document.getElementById('nav-usuarios').style.display = 'block';
            }

            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.dataset.page === page) link.classList.add('active');
                else link.classList.remove('active');
            });

            const capitalizedPage = page.charAt(0).toUpperCase() + page.slice(1);
            loadView(capitalizedPage, page);
        }
    } catch (error) {
        console.error("Error checking session:", error);
        document.getElementById('app-content').innerHTML = '<p style="text-align:center; color:red;">Error de conexión. Revisa la consola.</p>';
    }
});

async function loadView(fileName, pageName) {
    const mainContent = document.getElementById('app-content');
    mainContent.innerHTML = '<p style="text-align:center; margin-top:50px;">Cargando vista...</p>';

    try {
        const res = await fetch(`view/html/${fileName}.html`);
        if (!res.ok) throw new Error(`No se encontró ${fileName}.html`);
        
        const html = await res.text();
        mainContent.innerHTML = html;

        const oldScript = document.getElementById('dynamic-script');
        if (oldScript) oldScript.remove();

        let scriptPath = '';
        // 👇 CORRECT PATH 👇
        if (pageName === 'equipamientos') scriptPath = 'view/javascript/Equipamiento.js';
        if (pageName === 'historico') scriptPath = 'view/javascript/Historico.js';
        if (pageName === 'login') scriptPath = 'view/javascript/Login.js';

        if (scriptPath) {
            const script = document.createElement('script');
            script.id = 'dynamic-script';
            script.src = scriptPath;
            
            // Shows errors in console if the script fails to load
            script.onerror = () => console.error("❌ ERROR 404: No se pudo cargar", scriptPath);
            script.onload = () => console.log("✅ Script cargado:", scriptPath);
            
            document.body.appendChild(script);
        }
    } catch (error) {
        console.error(error);
        mainContent.innerHTML = `<p style="text-align:center; color:red;">Error 404: No se encontró la vista ${fileName}.html</p>`;
    }
}

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