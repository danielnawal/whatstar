// Configuracion del servicio de WhatsApp para pm2.
// Antes el arranque estaba solo en la linea de comandos y no quedaba escrito
// en ninguna parte; aqui queda a la vista y se puede cambiar sin adivinar.
//
// Arrancar:  cd /var/www/html/whatstar && pm2 start ecosystem.config.cjs && pm2 save
module.exports = {
    apps: [
        {
            name: 'whatstar',
            script: './app.js',            // es exactamente lo que hacia "npm start"
            cwd: '/var/www/html/whatstar',
            exec_mode: 'fork',
            autorestart: true,

            // RED DE SEGURIDAD: si la memoria se vuelve a disparar por cualquier
            // motivo, pm2 lo reinicia de forma ordenada ANTES de que el sistema lo
            // mate de golpe. Un reinicio ordenado guarda los datos; una muerte por
            // falta de memoria, no.
            max_memory_restart: '1200M',

            restart_delay: 5000,           // esperar 5 s entre reintentos
            max_restarts: 50,

            // Fecha y hora en cada linea del registro. Sin esto fue muy dificil
            // saber CUANDO se cayo: los registros no traian hora.
            time: true,

            out_file: '/root/.pm2/logs/whatstar-out.log',
            error_file: '/root/.pm2/logs/whatstar-error.log',
        },
    ],
}
