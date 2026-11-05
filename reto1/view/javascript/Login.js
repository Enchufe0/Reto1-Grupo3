// 1. Get the form elements immediately
const loginForm = document.getElementById('loginForm');
const rememberSwitch = document.getElementById('rememberSwitch');

// 2. Toggle "Remember Me"
if (rememberSwitch) {
    rememberSwitch.addEventListener('click', function () {
        this.classList.toggle('on'); 
    });
}

// 3. Handle Login Submission
if (loginForm) {
    loginForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const btnLogin = document.getElementById('btnLogin');
        const inputUsuario = document.getElementById('usuario').value;
        const inputPassword = document.getElementById('password').value;
        
        btnLogin.textContent = "Cargando...";
        btnLogin.disabled = true;

        try {
            const respuesta = await fetch('api/Login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    nombre_usuario: inputUsuario, 
                    password: inputPassword 
                })
            });
            
            const datos = await respuesta.json();
            
            if (respuesta.ok) {
                window.location.href = 'index.html?page=equipamientos';
            } else {
                alert(datos.error || "Usuario o contraseña incorrectos");
                btnLogin.textContent = "Iniciar sesión";
                btnLogin.disabled = false;
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Error de conexión con el servidor.");
            btnLogin.textContent = "Iniciar sesión";
            btnLogin.disabled = false;
        }
    });
}