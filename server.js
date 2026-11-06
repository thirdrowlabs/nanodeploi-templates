// A Node process listening on a port (runtime mode). Traefik sends traffic
// straight to it. GREETING comes from the site's environment settings, so
// changing it in NanoDeploi and redeploying should change the page.
require('./env')
const http = require('node:http')

const port = Number(process.env.PORT || 3000)
const startedAt = new Date().toISOString()

http
    .createServer((req, res) => {
        const greeting = process.env.GREETING || 'Hello from Node'

        if (req.url === '/health') {
            res.writeHead(200, { 'Content-Type': 'application/json' })
            res.end(JSON.stringify({ app: '07-node-web', ok: true, node: process.version, greeting, started_at: startedAt }) + '\n')
            return
        }

        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' })
        res.end(`<!doctype html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo 07 · Node web</title>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
<h1>${escapeHtml(greeting)}</h1>
<p>Node ${process.version}, listening on port ${port}, started ${startedAt}.</p>
<p>Set <code>GREETING</code> in the site's environment and redeploy to change the heading.</p>
</body>`)
    })
    .listen(port, '0.0.0.0', () => console.log(`Listening on ${port}`))

function escapeHtml(text) {
    return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])
}
