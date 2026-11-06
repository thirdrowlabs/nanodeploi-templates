// Tiny "build step" with no dependencies: renders dist/ from src/ and stamps
// the build time and commit, so you can see that each deploy really rebuilt.
import { mkdirSync, readFileSync, writeFileSync, existsSync } from 'node:fs'

function commit() {
    try {
        const head = readFileSync('.git/HEAD', 'utf8').trim()
        if (!head.startsWith('ref: ')) return head.slice(0, 7)
        const ref = readFileSync(`.git/${head.slice(5)}`, 'utf8').trim()
        return ref.slice(0, 7)
    } catch {
        return 'unknown'
    }
}

const builtAt = new Date().toISOString()
const sha = commit()

mkdirSync('dist', { recursive: true })

const template = readFileSync('src/index.html', 'utf8')
writeFileSync(
    'dist/index.html',
    template.replaceAll('{{BUILT_AT}}', builtAt).replaceAll('{{COMMIT}}', sha).replaceAll('{{NODE}}', process.version),
)
writeFileSync('dist/health.json', JSON.stringify({ app: '02-static-build', ok: true, built_at: builtAt, commit: sha }) + '\n')

if (!existsSync('dist/index.html')) {
    console.error('Build failed: dist/index.html missing')
    process.exit(1)
}
console.log(`Built dist/ at ${builtAt} (commit ${sha})`)
