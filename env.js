// NanoDeploi writes the site's environment to ./.env (it is not passed to the
// Node container as variables), so load it here, like dotenv would.
const { readFileSync } = require('node:fs')
const { join } = require('node:path')

try {
    for (const line of readFileSync(join(__dirname, '.env'), 'utf8').split('\n')) {
        const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)\s*$/)
        if (match && process.env[match[1]] === undefined) {
            process.env[match[1]] = match[2].replace(/^(['"])(.*)\1$/, '$2')
        }
    }
} catch {
    // No .env file: use the container's own environment only.
}
