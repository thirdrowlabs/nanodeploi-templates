import { writeFileSync } from 'node:fs'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

const builtAt = new Date().toISOString()

export default defineConfig({
    plugins: [
        react(),
        {
            // dist/health.json changes on every deploy, so you can see the build really ran.
            name: 'health-json',
            writeBundle(options) {
                writeFileSync(`${options.dir}/health.json`, JSON.stringify({ app: '12-react-vite', ok: true, built_at: builtAt }) + '\n')
            },
        },
    ],
    define: { __BUILT_AT__: JSON.stringify(builtAt) },
})
