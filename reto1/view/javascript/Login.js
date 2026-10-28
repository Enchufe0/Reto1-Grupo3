// Wait 200ms to ensure the HTML form is fully injected into the DOM
setTimeout(() => {
    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const rememberSwitch = document.getElementById('rememberSwitch');

    // 1. Toggle "Remember Me"
    if (rememberSwitch) {
        rememberSwitch.addEventListener('click', function () {
            this.classList.toggle('on'); 
        });
    }

    // 2. Handle Login
    if (loginForm) {
        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
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
                
                // Read raw text first to catch PHP errors
                const text = await respuesta.text();
                let datos;
                
                try {
                    datos = JSON.parse(text);
                } catch (e) {
                    throw new Error("El servidor no devolvió JSON. Posible error en PHP.");
                }
                
                if (respuesta.ok) {
                    // Success! Redirect
                    window.location.href = 'index.html?page=equipamientos';
                } else {
                    // Logic error (like wrong password)
                    alert(datos.error || "Usuario o contraseña incorrectos");
                    btnLogin.textContent = "Iniciar sesión";
                    btnLogin.disabled = false;
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Error de conexión: " + error.message);
                btnLogin.textContent = "Iniciar sesión";
                btnLogin.disabled = false;
            }
        });
    } else {
        console.error("No se encontró el formulario con id 'loginForm'");
    }
}, 200);