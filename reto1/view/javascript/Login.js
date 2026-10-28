document.addEventListener('DOMContentLoaded', function() {
    
    // Remember switch toggle
    const rememberSwitch = document.getElementById('rememberSwitch');
    if (rememberSwitch) {
        rememberSwitch.addEventListener('click', function () {
            this.classList.toggle('on');
        });
    }

    // Login Form Submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnLogin');
            const usuario = document.getElementById('usuario').value;
            const password = document.getElementById('password').value;
            
            btn.textContent = "Cargando...";
            btn.disabled = true;

            try {
                const res = await fetch('api/Login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre_usuario: usuario, password: password })
                });
                
                const data = await res.json();
                
                if (res.ok) {
                    window.location.href = 'index.php?page=equipamientos';
                } else {
                    alert(data.error || "Usuario o contraseña incorrectos");
                    btn.textContent = "Iniciar sesión";
                    btn.disabled = false;
                }
            } catch (err) {
                alert("Error de conexión con el servidor");
                btn.textContent = "Iniciar sesión";
                btn.disabled = false;
            }
        });
    }
});
