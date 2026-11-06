// Node talks to the site's Redis container (host "redis") with no npm
// packages: it speaks the Redis protocol over a plain socket. Every page view
// increments a counter, which should survive redeploys.
require('./env')
const http = require('node:http')
const net = require('node:net')

const port = Number(process.env.PORT || 3000)

function redis(...commands) {
    return new Promise((resolve, reject) => {
        const socket = net.connect(Number(process.env.REDIS_PORT || 6379), process.env.REDIS_HOST || 'redis')
        const all = process.env.REDIS_PASSWORD ? [['AUTH', process.env.REDIS_PASSWORD], ...commands] : commands
        let buffer = ''

        socket.setTimeout(3000, () => socket.destroy(new Error('Redis timed out')))
        socket.on('error', reject)
        socket.on('connect', () => {
            socket.write(all.map((args) => `*${args.length}\r\n` + args.map((a) => `$${Buffer.byteLength(String(a))}\r\n${a}\r\n`).join('')).join(''))
        })
        socket.on('data', (chunk) => {
            buffer += chunk
            const replies = buffer.split('\r\n').filter((line) => /^[+\-:]/.test(line))
            if (replies.length < all.length) return
            socket.end()
            const error = replies.find((line) => line.startsWith('-'))
            if (error) return reject(new Error(error.slice(1)))
            resolve(replies.at(-1).slice(1))
        })
    })
}

http
    .createServer(async (req, res) => {
        try {
            if (req.url === '/health') {
                const pong = await redis(['PING'])
                res.writeHead(200, { 'Content-Type': 'application/json' })
                res.end(JSON.stringify({ app: '08-node-redis', ok: true, redis: pong }) + '\n')
                return
            }

            const hits = await redis(['INCR', 'demo:hits'])
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' })
            res.end(`<!doctype html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo 08 · Node + Redis</title>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
<h1>Node talks to Redis</h1>
<p>Redis counter: <strong>${hits}</strong>. Reload to count up; it should continue after a redeploy.</p>
</body>`)
        } catch (error) {
            const isHealth = req.url === '/health'
            res.writeHead(isHealth ? 503 : 500, { 'Content-Type': isHealth ? 'application/json' : 'text/plain' })
            res.end(isHealth ? JSON.stringify({ app: '08-node-redis', ok: false, redis: error.message }) + '\n' : `Could not reach Redis: ${error.message}\n`)
        }
    })
    .listen(port, '0.0.0.0', () => console.log(`Listening on ${port}`))
