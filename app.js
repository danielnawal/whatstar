import 'dotenv/config'
import express from 'express'
import nodeCleanup from 'node-cleanup'
import routes from './routes.js'
import { init, cleanup } from './whatsapp.js'
import cors from 'cors'

// Silenciar errores de rotación de claves de Baileys (prekey bundle / GCM decrypt)
// — son transitorios, la sesión se recupera sola. Sin este handler PM2 reinicia el proceso.
process.on('uncaughtException', (err) => {
    const msg = err?.message ?? ''
    if (
        msg.includes('Unsupported state or unable to authenticate data') ||
        msg.includes('Closing stale open session') ||
        msg.includes('prekey bundle') ||
        msg.includes('decodeFrame')
    ) {
        console.warn('[baileys] crypto error silenciado (rotación de claves):', msg)
        return
    }
    console.error('[UNCAUGHT EXCEPTION]', err)
})

process.on('unhandledRejection', (reason) => {
    const msg = reason?.message ?? String(reason)
    if (
        msg.includes('Unsupported state or unable to authenticate data') ||
        msg.includes('decodeFrame')
    ) {
        console.warn('[baileys] rejection silenciada:', msg)
        return
    }
    console.warn('[UNHANDLED REJECTION]', reason)
})

const app = express()

const host = process.env.WA_SERVER_HOST || undefined
const port = parseInt(process.env.WA_SERVER_PORT ?? 8000)

app.use(cors())
app.use(express.urlencoded({ extended: true }))
app.use(express.json())
app.use('/', routes)

const listenerCallback = () => {
    init()
    console.log(`Server is listening on http://${host ? host : 'localhost'}:${port}`)
}

if (host) {
    app.listen(port, host, listenerCallback)
} else {
    app.listen(port, listenerCallback)
}

nodeCleanup(cleanup)

export default app
