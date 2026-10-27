<!-- LOGO TOP LEFT -->
<div class="brand-logo">
    <img src="assets/img/logo.svg" alt="Logo" class="logo-img">
    <div class="logo-text">r d e n z a</div>
</div>

<!-- YELLOW LIGHT -->
<div class="yellow-glow"></div>

<div class="login-wrapper">
    <div class="glass-circle">
        
        <form class="login-form" id="loginForm">
            <h1>Bienvenido</h1>
            <div class="subtitle">Inicia sesión en tu cuenta</div>

            <div class="input-box">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="usuario" placeholder="Usuario" autocomplete="off" required>
            </div>

            <div class="input-box">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" placeholder="Contraseña" autocomplete="off" required>
            </div>

            <div class="forgot">
                <div class="remember">
                    <div class="switch" id="rememberSwitch"></div>
                    Recordarme
                </div>
            </div>

            <!-- Note: overflow:hidden is needed on the button for the shimmer to work -->
            <button type="submit" id="btnLogin">Iniciar sesión</button>
        </form>

    </div>
</div>

<script>
    document.getElementById('rememberSwitch').addEventListener('click', function () {
        this.classList.toggle('on');
    });

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
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
</script>