// A live chat over WebSockets with no npm packages: the server speaks the
// WebSocket protocol (RFC 6455) itself. Open the page in two browsers and
// messages appear in both instantly. Traefik passes the upgrade through as is.
require('./env')
const crypto = require('node:crypto')
const fs = require('node:fs')
const http = require('node:http')
const path = require('node:path')

const port = Number(process.env.PORT || 3000)
const page = fs.readFileSync(path.join(__dirname, 'public', 'index.html'))
const clients = new Set()
const history = []
const startedAt = new Date().toISOString()

// One WebSocket frame from the server: FIN + opcode, then the (unmasked) payload length and data.
function frame(opcode, payload = Buffer.alloc(0)) {
    const length = payload.length
    const header = length < 126 ? Buffer.from([0x80 | opcode, length]) : length < 65536 ? Buffer.alloc(4) : Buffer.alloc(10)
    if (length >= 126) {
        header[0] = 0x80 | opcode
        header[1] = length < 65536 ? 126 : 127
        if (length < 65536) header.writeUInt16BE(length, 2)
        else header.writeBigUInt64BE(BigInt(length), 2)
    }
    return Buffer.concat([header, payload])
}

function send(socket, message) {
    if (!socket.destroyed) socket.write(frame(0x1, Buffer.from(JSON.stringify(message))))
}

function broadcast(message) {
    for (const client of clients) send(client, message)
}

function presence() {
    broadcast({ type: 'presence', online: clients.size })
}

// Reads complete client frames from the buffer (clients always mask their payload).
function readFrames(state, chunk, onFrame) {
    state.buffer = Buffer.concat([state.buffer, chunk])
    while (state.buffer.length >= 2) {
        const opcode = state.buffer[0] & 0x0f
        const masked = (state.buffer[1] & 0x80) !== 0
        let length = state.buffer[1] & 0x7f
        let offset = 2
        if (length === 126) {
            if (state.buffer.length < 4) return
            length = state.buffer.readUInt16BE(2)
            offset = 4
        } else if (length === 127) {
            if (state.buffer.length < 10) return
            length = Number(state.buffer.readBigUInt64BE(2))
            offset = 10
        }
        if (length > 64 * 1024) return onFrame(0x8, Buffer.alloc(0))
        const total = offset + (masked ? 4 : 0) + length
        if (state.buffer.length < total) return
        const mask = masked ? state.buffer.subarray(offset, offset + 4) : null
        const payload = Buffer.from(state.buffer.subarray(total - length, total))
        if (mask) for (let i = 0; i < payload.length; i++) payload[i] ^= mask[i % 4]
        state.buffer = state.buffer.subarray(total)
        onFrame(opcode, payload)
    }
}

const server = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' })
        res.end(JSON.stringify({ app: '11-node-websockets', ok: true, websocket_clients: clients.size, messages: history.length, started_at: startedAt }) + '\n')
        return
    }
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' })
    res.end(page)
})

server.on('upgrade', (req, socket) => {
    const key = req.headers['sec-websocket-key']
    if (req.url !== '/ws' || !key || (req.headers.upgrade || '').toLowerCase() !== 'websocket') {
        socket.end('HTTP/1.1 400 Bad Request\r\n\r\n')
        return
    }

    const accept = crypto.createHash('sha1').update(key + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11').digest('base64')
    socket.write(['HTTP/1.1 101 Switching Protocols', 'Upgrade: websocket', 'Connection: Upgrade', `Sec-WebSocket-Accept: ${accept}`, '', ''].join('\r\n'))
    socket.setNoDelay(true)
    clients.add(socket)
    send(socket, { type: 'history', messages: history })
    presence()

    const state = { buffer: Buffer.alloc(0) }
    const close = () => {
        if (!clients.delete(socket)) return
        socket.destroy()
        presence()
    }

    socket.on('data', (chunk) =>
        readFrames(state, chunk, (opcode, payload) => {
            if (opcode === 0x8) {
                if (!socket.destroyed) socket.end(frame(0x8))
                return close()
            }
            if (opcode === 0x9) return socket.write(frame(0xa, payload))
            if (opcode !== 0x1) return

            let data
            try {
                data = JSON.parse(payload.toString('utf8'))
            } catch {
                return
            }
            const text = String(data.text || '').trim().slice(0, 500)
            if (!text) return
            const message = { type: 'message', name: String(data.name || 'Someone').trim().slice(0, 40) || 'Someone', text, at: new Date().toISOString() }
            history.push(message)
            if (history.length > 50) history.shift()
            broadcast(message)
        }),
    )
    socket.on('end', close)
    socket.on('error', close)
    socket.on('close', close)
})

// Pings keep idle connections open through proxies (Traefik closes idle ones after a few minutes).
setInterval(() => {
    for (const client of clients) if (!client.destroyed) client.write(frame(0x9))
}, 25_000).unref()

server.listen(port, '0.0.0.0', () => console.log(`WebSocket chat on :${port} (ws path /ws)`))
